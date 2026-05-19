<?php

namespace App\Http\Controllers;

use App\Services\OutlookGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class OutlookOAuthController extends Controller
{
    protected OutlookGraphService $outlookService;

    public function __construct(OutlookGraphService $outlookService)
    {
        $this->outlookService = $outlookService;
    }

    /**
     * Return Microsoft OAuth authorization URL as JSON.
     * The frontend will redirect the browser to this URL.
     * Uses cache (not session) so it works in API routes.
     * GET /api/oauth/authorize
     */
    public function authorize()
    {
        $state = bin2hex(random_bytes(16));
        // Store state in cache for 10 minutes (survives the browser redirect round-trip)
        cache()->put('oauth_state_' . $state, true, now()->addMinutes(10));

        $url = $this->outlookService->getAuthorizationUrl($state);

        return response()->json(['url' => $url]);
    }

    /**
     * Handle redirect from Microsoft after user authorization.
     * Microsoft redirects the browser here with ?code=...&state=...
     * GET /api/oauth/callback
     */
    public function callback(Request $request)
    {
        $frontendBase = rtrim(config('app.url'), '/');

        try {
            // Verify state to prevent CSRF (using cache, not session)
            $state = $request->query('state');

            if (!$state || !cache()->has('oauth_state_' . $state)) {
                Log::warning('OAuth state mismatch during callback');
                return redirect($frontendBase . '/settings?oauth=error&reason=invalid_state');
            }

            cache()->forget('oauth_state_' . $state);

            // Check for error from Microsoft
            if ($request->has('error')) {
                Log::warning('OAuth authorization denied', [
                    'error' => $request->query('error'),
                    'error_description' => $request->query('error_description'),
                ]);
                return redirect($frontendBase . '/settings?oauth=error&reason=' . urlencode($request->query('error')));
            }

            // Get authorization code
            $code = $request->query('code');
            if (!$code) {
                return redirect($frontendBase . '/settings?oauth=error&reason=missing_code');
            }

            // Exchange code for token and store it
            $token = $this->outlookService->handleAuthorizationCallback($code);

            Log::info('OAuth token obtained successfully', ['mailbox' => $token->mailbox]);

            // Redirect back to the Vue SPA settings page with success flag
            return redirect($frontendBase . '/settings?oauth=success&mailbox=' . urlencode($token->mailbox));
        } catch (Exception $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $frontendBase = rtrim(config('app.url'), '/');
            return redirect($frontendBase . '/settings?oauth=error&reason=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Check current OAuth token status
     * GET /api/oauth/status (admin only)
     */
    public function status()
    {
        try {
            $token = \App\Models\OAuthToken::where('provider', 'outlook')
                ->where('mailbox', config('services.outlook.mailbox'))
                ->first();

            if (!$token) {
                return response()->json([
                    'status' => 'not_configured',
                    'message' => 'No OAuth token configured',
                ]);
            }

            return response()->json([
                'status' => 'configured',
                'mailbox' => $token->mailbox,
                'access_token_valid' => $token->isAccessTokenValid(),
                'refresh_token_valid' => $token->isRefreshTokenValid(),
                'access_token_expires_at' => $token->access_token_expires_at,
                'last_used_at' => $token->last_used_at,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to check OAuth status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to check OAuth status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke OAuth token
     * DELETE /api/oauth/token (admin only)
     */
    public function revoke()
    {
        try {
            $token = \App\Models\OAuthToken::where('provider', 'outlook')
                ->where('mailbox', config('services.outlook.mailbox'))
                ->first();

            if (!$token) {
                return response()->json([
                    'error' => 'No token to revoke',
                ], 404);
            }

            $token->delete();

            Log::info('OAuth token revoked', ['mailbox' => $token->mailbox]);

            return response()->json([
                'success' => true,
                'message' => 'OAuth token revoked',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to revoke OAuth token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to revoke token',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
