<?php

namespace App\Services;

use App\Models\OAuthToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class OutlookGraphService
{
    private const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0';
    private const OUTLOOK_AUTH_URL = 'https://login.microsoftonline.com';

    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->tenantId = config('services.outlook.tenant_id');
        $this->clientId = config('services.outlook.client_id');
        $this->clientSecret = config('services.outlook.client_secret');
        $this->redirectUri = config('services.outlook.redirect_uri');
    }

    /**
     * Get authorization URL for user consent
     */
    public function getAuthorizationUrl($state = null): string
    {
        $state = $state ?? bin2hex(random_bytes(16));

        return sprintf(
            '%s/%s/oauth2/v2.0/authorize?client_id=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s',
            self::OUTLOOK_AUTH_URL,
            $this->tenantId,
            urlencode($this->clientId),
            urlencode($this->redirectUri),
            urlencode('Mail.Read Mail.ReadWrite offline_access'),
            $state
        );
    }

    /**
     * Exchange authorization code for tokens
     */
    public function handleAuthorizationCallback($code): OAuthToken
    {
        try {
            $response = Http::asForm()->post(
                sprintf('%s/%s/oauth2/v2.0/token', self::OUTLOOK_AUTH_URL, $this->tenantId),
                [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                    'scope' => 'Mail.Read Mail.ReadWrite offline_access',
                ]
            );

            if (!$response->successful()) {
                throw new Exception('Token exchange failed: ' . $response->body());
            }

            $data = $response->json();

            // Get user email
            $userEmail = $this->getUserEmail($data['access_token']);

            // Store or update token
            $token = OAuthToken::updateOrCreate(
                ['provider' => 'outlook', 'mailbox' => $userEmail],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
                    'scope' => explode(' ', $data['scope'] ?? ''),
                    'metadata' => [
                        'token_type' => $data['token_type'] ?? 'Bearer',
                        'user_email' => $userEmail,
                    ],
                ]
            );

            Log::info('Outlook OAuth token obtained', ['mailbox' => $userEmail]);

            return $token;
        } catch (Exception $e) {
            Log::error('Outlook OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user email from access token
     */
    private function getUserEmail($accessToken): string
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(self::GRAPH_API_URL . '/me?$select=userPrincipalName');

            if ($response->successful()) {
                return $response->json()['userPrincipalName'];
            }

            throw new Exception('Failed to get user email');
        } catch (Exception $e) {
            Log::error('Failed to get user email', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get valid access token (refresh if needed)
     */
    public function getAccessToken($mailbox): string
    {
        $token = OAuthToken::forMailbox('outlook', $mailbox);

        if (!$token) {
            throw new Exception("No OAuth token found for mailbox: {$mailbox}");
        }

        // If token is still valid, use it
        if ($token->isAccessTokenValid()) {
            $token->markAsUsed();
            return $token->access_token;
        }

        // If token expired, refresh it
        if (!$token->isRefreshTokenValid()) {
            throw new Exception("Refresh token expired for {$mailbox}. Please re-authorize.");
        }

        return $this->refreshAccessToken($token);
    }

    /**
     * Refresh access token using refresh token
     */
    private function refreshAccessToken(OAuthToken $token): string
    {
        try {
            $response = Http::asForm()->post(
                sprintf('%s/%s/oauth2/v2.0/token', self::OUTLOOK_AUTH_URL, $this->tenantId),
                [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $token->refresh_token,
                    'grant_type' => 'refresh_token',
                    'scope' => 'Mail.Read Mail.ReadWrite offline_access',
                ]
            );

            if (!$response->successful()) {
                throw new Exception('Token refresh failed: ' . $response->body());
            }

            $data = $response->json();

            // Update token
            $token->updateToken(
                $data['access_token'],
                $data['expires_in'] ?? 3600,
                $data['refresh_token'] ?? null
            );

            Log::info('Outlook access token refreshed', ['mailbox' => $token->mailbox]);

            return $token->access_token;
        } catch (Exception $e) {
            Log::error('Outlook token refresh failed', [
                'mailbox' => $token->mailbox,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get messages from mailbox folder
     */
    public function getMessages($mailbox, $folderName, $subjectFilter = null, $unreadOnly = false)
    {
        try {
            $accessToken = $this->getAccessToken($mailbox);

            // Get folder ID
            $folderId = $this->getFolderId($mailbox, $folderName, $accessToken);

            // Build filter
            $filters = ["parentFolderId eq '{$folderId}'"];

            if ($unreadOnly) {
                $filters[] = "isRead eq false";
            }

            if ($subjectFilter) {
                // Simple wildcard matching: "Agents consolidated reporting" matches subjects containing it
                $filters[] = "contains(subject, '" . addslashes($subjectFilter) . "')";
            }

            $filterQuery = implode(' and ', $filters);

            // Fetch messages
            $response = Http::withToken($accessToken)->get(
                self::GRAPH_API_URL . '/me/messages',
                [
                    '$filter' => $filterQuery,
                    '$orderby' => 'receivedDateTime desc',
                    '$top' => 100,
                    '$select' => 'id,subject,from,receivedDateTime,hasAttachments,isRead',
                ]
            );

            if (!$response->successful()) {
                throw new Exception('Failed to fetch messages: ' . $response->body());
            }

            return $response->json()['value'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to fetch messages from Outlook', [
                'mailbox' => $mailbox,
                'folder' => $folderName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get folder ID by name
     */
    private function getFolderId($mailbox, $folderName, $accessToken): string
    {
        try {
            // Handle nested folders (e.g., "Inbox/Imports")
            $parts = explode('/', $folderName);
            $folderId = null;

            foreach ($parts as $part) {
                if ($folderId) {
                    // Get child folder
                    $response = Http::withToken($accessToken)->get(
                        self::GRAPH_API_URL . "/me/mailFolders/{$folderId}/childFolders",
                        ['$filter' => "displayName eq '{$part}'"]
                    );
                } else {
                    // Get top-level folder
                    $response = Http::withToken($accessToken)->get(
                        self::GRAPH_API_URL . '/me/mailFolders',
                        ['$filter' => "displayName eq '{$part}'"]
                    );
                }

                if (!$response->successful()) {
                    throw new Exception("Folder not found: {$part}");
                }

                $folders = $response->json()['value'] ?? [];
                if (empty($folders)) {
                    throw new Exception("Folder not found: {$part}");
                }

                $folderId = $folders[0]['id'];
            }

            Log::info('Folder ID resolved', ['mailbox' => $mailbox, 'folder' => $folderName, 'id' => $folderId]);

            return $folderId;
        } catch (Exception $e) {
            Log::error('Failed to get folder ID', [
                'mailbox' => $mailbox,
                'folder' => $folderName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get attachments from message
     */
    public function getAttachments($mailbox, $messageId)
    {
        try {
            $accessToken = $this->getAccessToken($mailbox);

            $response = Http::withToken($accessToken)->get(
                self::GRAPH_API_URL . "/me/messages/{$messageId}/attachments",
                ['$select' => 'id,name,contentType,size,isInline']
            );

            if (!$response->successful()) {
                throw new Exception('Failed to fetch attachments: ' . $response->body());
            }

            return $response->json()['value'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to fetch attachments', [
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($mailbox, $messageId, $attachmentId)
    {
        try {
            $accessToken = $this->getAccessToken($mailbox);

            $response = Http::withToken($accessToken)->get(
                self::GRAPH_API_URL . "/me/messages/{$messageId}/attachments/{$attachmentId}"
            );

            if (!$response->successful()) {
                throw new Exception('Failed to download attachment: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Failed to download attachment', [
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead($mailbox, $messageId): void
    {
        try {
            $accessToken = $this->getAccessToken($mailbox);

            $response = Http::withToken($accessToken)->patch(
                self::GRAPH_API_URL . "/me/messages/{$messageId}",
                ['isRead' => true]
            );

            if (!$response->successful()) {
                Log::warning('Failed to mark message as read', [
                    'mailbox' => $mailbox,
                    'message_id' => $messageId,
                    'response' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to mark message as read', [
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Move message to folder
     */
    public function moveToFolder($mailbox, $messageId, $targetFolderName): void
    {
        try {
            $accessToken = $this->getAccessToken($mailbox);
            $folderId = $this->getFolderId($mailbox, $targetFolderName, $accessToken);

            $response = Http::withToken($accessToken)->post(
                self::GRAPH_API_URL . "/me/messages/{$messageId}/move",
                ['destinationId' => $folderId]
            );

            if (!$response->successful()) {
                Log::warning('Failed to move message', [
                    'mailbox' => $mailbox,
                    'message_id' => $messageId,
                    'target_folder' => $targetFolderName,
                    'response' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to move message', [
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'target_folder' => $targetFolderName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
