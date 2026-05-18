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
     * Redirect user to Microsoft for authorization
     * GET /oauth/authorize
     */
    public function authorize()
    {
        $state = bin2hex(random_bytes(16));
        session(['oauth_state' => $state]);

        $url = $this->outlookService->getAuthorizationUrl($state);

        return redirect($url);
    }

    /**
     * Handle redirect from Microsoft after user authorization
     * GET /oauth/callback?code=...&state=...
     */
    public function callback(Request $request)
    {
        try {
            // Verify state to prevent CSRF
            $state = $request->query('state');
            $sessionState = session('oauth_state');

            if (!$state || $state !== $sessionState) {
                Log::warning('OAuth state mismatch during callback');
                return response()->json([
                    'error' => 'Invalid state parameter (CSRF protection)',
                ], 400);
            }

            // Check for error from Microsoft
            if ($request->has('error')) {
                Log::warning('OAuth authorization denied', [
                    'error' => $request->query('error'),
                    'error_description' => $request->query('error_description'),
                ]);

                return response()->json([
                    'error' => 'Authorization denied',
                    'error_description' => $request->query('error_description'),
                ], 400);
            }

            // Get authorization code
            $code = $request->query('code');
            if (!$code) {
                return response()->json([
                    'error' => 'Missing authorization code',
                ], 400);
            }

            // Exchange code for token
            $token = $this->outlookService->handleAuthorizationCallback($code);

            // Clear state from session
            session()->forget('oauth_state');

            // Return success (can redirect to admin panel or show confirmation)
            return response()->json([
                'success' => true,
                'message' => 'OAuth token obtained successfully',
                'mailbox' => $token->mailbox,
                'token_expires_at' => $token->access_token_expires_at,
            ]);
        } catch (Exception $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to obtain OAuth token',
                'message' => $e->getMessage(),
            ], 500);
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
