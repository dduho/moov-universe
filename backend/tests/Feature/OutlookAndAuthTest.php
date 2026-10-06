<?php

namespace Tests\Feature;

use App\Models\OAuthToken;
use App\Services\OutlookGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutlookAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_call_without_json_header_returns_401(): void
    {
        // Avant : 500 "Route [login] not defined" si le client n'envoyait pas Accept: application/json
        $this->get('/api/me')->assertUnauthorized();
    }

    public function test_missing_outlook_token_gives_explicit_error(): void
    {
        $this->expectExceptionMessage('No OAuth token found for mailbox: someone@moov-africa.tg');

        app(OutlookGraphService::class)->getAccessToken('someone@moov-africa.tg');
    }

    public function test_stored_outlook_token_is_found(): void
    {
        OAuthToken::create([
            'provider' => 'outlook',
            'mailbox' => 'someone@moov-africa.tg',
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'access_token_expires_at' => now()->addHour(),
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $this->assertSame('access', app(OutlookGraphService::class)->getAccessToken('someone@moov-africa.tg'));
    }
}
