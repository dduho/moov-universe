<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OAuthToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider',
        'mailbox',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'scope',
        'metadata',
        'last_used_at',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'scope' => 'json',
        'metadata' => 'json',
    ];

    /**
     * Scope: Get token for a specific provider and mailbox
     */
    public function scopeForMailbox($query, $provider, $mailbox)
    {
        return $query->where('provider', $provider)
                     ->where('mailbox', $mailbox)
                     ->first();
    }

    /**
     * Check if access token is still valid
     */
    public function isAccessTokenValid(): bool
    {
        if (!$this->access_token_expires_at) {
            return false;
        }

        return Carbon::now()->lt($this->access_token_expires_at->subMinutes(5));
    }

    /**
     * Check if refresh token is still valid
     */
    public function isRefreshTokenValid(): bool
    {
        if (!$this->refresh_token_expires_at) {
            // Outlook refresh tokens don't expire by default, consider always valid
            return !empty($this->refresh_token);
        }

        return Carbon::now()->lt($this->refresh_token_expires_at);
    }

    /**
     * Update token and mark as used
     */
    public function updateToken(
        $accessToken,
        $expiresIn,
        $refreshToken = null,
        $refreshTokenExpiresIn = null
    ): void {
        $this->access_token = $accessToken;
        $this->access_token_expires_at = Carbon::now()->addSeconds($expiresIn);
        $this->last_used_at = Carbon::now();

        if ($refreshToken) {
            $this->refresh_token = $refreshToken;
            if ($refreshTokenExpiresIn) {
                $this->refresh_token_expires_at = Carbon::now()->addSeconds($refreshTokenExpiresIn);
            }
        }

        $this->save();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => Carbon::now()]);
    }
}
