<?php

namespace Tests\Feature;

use App\Services\HuaweiApiException;
use App\Services\HuaweiCpsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PdvAccountTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.huawei' => [
            'url' => 'https://10.81.19.203:30003/payment/services/SYNCAPIRequestMgrService',
            'tls_host' => 'huawei-cps.moov-africa.tg',
            'verify_ssl' => true,
            'third_party_id' => 'TESTCALLER',
            'identifier' => 'test_api',
            'caller_password' => 'cGFzc3dvcmQ=',
            'security_credential' => 'Y3JlZGVudGlhbA==',
            'result_url' => 'https://example.test/result',
            'timeout' => 5,
            'cache_ttl' => 60,
        ]]);
        Cache::flush();
    }

    // --- Réponses simulées (même structure que l'API réelle, données anonymisées) -----------------------

    private function wrap(string $resultBody): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:res="http://cps.huawei.com/synccpsinterface/result">'
            . '<soapenv:Body><res:Result><res:Header><res:Version>1.0</res:Version></res:Header>'
            . '<res:Body>' . $resultBody . '</res:Body></res:Result></soapenv:Body></soapenv:Envelope>';
    }

    private function ok(string $payload): string
    {
        return $this->wrap('<res:ResultType>0</res:ResultType><res:ResultCode>0</res:ResultCode>'
            . '<res:ResultDesc>Process service request successfully.</res:ResultDesc>' . $payload);
    }

    private function balanceXml(string $available = '343349.00'): string
    {
        return $this->ok('<res:QueryOrganizationBalanceResult><res:BOCompletedTime>20261007082308</res:BOCompletedTime>'
            . '<res:AccountBalanceData><res:AccountBalanceItem>'
            . '<res:AccountHolderID>8932019</res:AccountHolderID>'
            . '<res:AccountHolderPublicName>8932019 - SPEED-MAIN-DIEU 01</res:AccountHolderPublicName>'
            . '<res:AccountTypeName>Org Main Account</res:AccountTypeName>'
            . '<res:AccountStatus>Active</res:AccountStatus><res:Currency>XOF</res:Currency>'
            . "<res:AvailableBalance>{$available}</res:AvailableBalance><res:ReservedBalance>0.00</res:ReservedBalance>"
            . "<res:UnclearedBalance>12.50</res:UnclearedBalance><res:CurrentBalance>{$available}</res:CurrentBalance>"
            . '</res:AccountBalanceItem></res:AccountBalanceData></res:QueryOrganizationBalanceResult>');
    }

    private function item(string $receipt, string $type, string $amount, string $time, string $details): string
    {
        return '<res:TransactionItem><res:ReceiptNumber>' . $receipt . '</res:ReceiptNumber>'
            . '<res:TransactionStatus>Completed</res:TransactionStatus><res:TxnType>' . $type . '</res:TxnType>'
            . '<res:InitiatorName>SPEED-MAIN-DIEU 01\\22898197756</res:InitiatorName><res:Currency>XOF</res:Currency>'
            . '<res:Amount>' . $amount . '</res:Amount><res:InitiatedTime>' . $time . '</res:InitiatedTime>'
            . '<res:CompletedTime>' . $time . '</res:CompletedTime><res:Details>' . $details . '</res:Details></res:TransactionItem>';
    }

    private function txnXml(array $items = null): string
    {
        $items ??= [
            // Volontairement dans le désordre : le service doit trier du plus récent au plus ancien
            $this->item('R-OLD', 'CashIn', '1000.00', '20261001160308', 'Cash In at POS(8932019 - SPEED-MAIN-DIEU 01) to Customer(22899485661 - KOFFI MAWULAWOE DACKEY)'),
            $this->item('R-NEW', 'COMT', '3580.00', '20261001193152', 'Transfer from Commission account to Main account'),
            $this->item('R-MID', 'CashOut', '90000.00', '20261001165426', 'Cash Out at POS(8932019 - SPEED-MAIN-DIEU 01) to Customer(22897449807 - YAO SALIFOU) Main Account'),
        ];

        $count = count($items);

        return $this->ok('<res:SyncQueryOrgTxnResult><res:BOCompletedTime>20261007082308</res:BOCompletedTime>'
            . '<res:TransactionListData>' . implode('', $items)
            . "<res:NbrOfReturned>{$count}</res:NbrOfReturned><res:NbrOfTotal>{$count}</res:NbrOfTotal>"
            . '</res:TransactionListData></res:SyncQueryOrgTxnResult>');
    }

    private function fakeHuawei(?string $balance = null, ?string $txn = null): void
    {
        // Http::fake() empile les simulations (la première qui correspond gagne) : repartir d'un client neuf
        Http::swap(new \Illuminate\Http\Client\Factory());

        Http::fake(function (HttpRequest $request) use ($balance, $txn) {
            return Http::response(
                str_contains($request->body(), 'QueryOrganizationBalance') ? ($balance ?? $this->balanceXml()) : ($txn ?? $this->txnXml()),
                200
            );
        });
    }

    private function pdvWithShortcode(?string $shortcode, $org = null, $creator = null)
    {
        $org ??= $this->organization();
        $creator ??= $this->user('admin');

        return $this->pdv($org, $creator, ['shortcode' => $shortcode]);
    }

    // --- Service ----------------------------------------------------------------------------------------

    public function test_fetch_account_normalizes_balance_and_transactions(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->fakeHuawei();

        $account = app(HuaweiCpsService::class)->fetchAccount('8932019', 7);

        $this->assertSame('SPEED-MAIN-DIEU 01', $account['holder_name']);
        $this->assertSame('Active', $account['status']);
        $this->assertSame(343349.0, $account['balance']['available']);
        $this->assertSame(12.5, $account['balance']['uncleared']);
        $this->assertSame(3, $account['transactions_total']);
        $this->assertSame(['R-NEW', 'R-MID', 'R-OLD'], array_column($account['transactions'], 'receipt'));
        $this->assertSame('2026-10-01 19:31:52', $account['transactions'][0]['completed_at']);
        $this->assertSame(3580.0, $account['transactions'][0]['amount']);
        $this->assertSame(['from' => '2026-10-01', 'to' => '2026-10-07', 'days' => 7], $account['period']);

        Carbon::setTestNow();
    }

    public function test_customer_names_and_numbers_are_masked(): void
    {
        $this->fakeHuawei();

        $account = app(HuaweiCpsService::class)->fetchAccount('8932019', 7);
        $descriptions = implode(' | ', array_column($account['transactions'], 'description'));

        $this->assertStringContainsString('Customer(••••5661)', $descriptions);
        $this->assertStringContainsString('POS(8932019 - SPEED-MAIN-DIEU 01)', $descriptions); // le PDV lui-même reste visible
        $this->assertStringNotContainsString('22899485661', $descriptions);
        $this->assertStringNotContainsString('KOFFI', $descriptions);
        $this->assertStringNotContainsString('YAO SALIFOU', $descriptions);
        // Le numéro de l'opérateur (InitiatorName) n'est pas exposé du tout
        $this->assertStringNotContainsString('22898197756', json_encode($account));
    }

    public function test_request_envelope_targets_shortcode_with_credentials_and_tls_host(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->fakeHuawei();

        app(HuaweiCpsService::class)->fetchAccount('8932019', 3);

        Http::assertSentCount(2);
        Http::assertSent(function (HttpRequest $request) {
            $body = $request->body();

            return str_contains($body, '<req:CommandID>QueryOrganizationBalance</req:CommandID>')
                && str_contains($body, '<req:ThirdPartyID>TESTCALLER</req:ThirdPartyID>')
                && str_contains($body, '<req:Password>cGFzc3dvcmQ=</req:Password>')
                && str_contains($body, '<req:Identifier>test_api</req:Identifier>')
                && str_contains($body, '<req:IdentifierType>4</req:IdentifierType><req:Identifier>8932019</req:Identifier>')
                // La validation TLS passe par le nom du certificat wildcard, résolu vers l'IP de la passerelle
                && $request->url() === 'https://huawei-cps.moov-africa.tg:30003/payment/services/SYNCAPIRequestMgrService';
        });
        Http::assertSent(function (HttpRequest $request) {
            $body = $request->body();

            return str_contains($body, '<req:CommandID>SyncQueryOrgTxn</req:CommandID>')
                && str_contains($body, '<req:StartDateTime>20261005000000</req:StartDateTime>')
                && str_contains($body, '<req:EndDateTime>20261007100000</req:EndDateTime>')
                && str_contains($body, '<req:TransactionStatus>Completed</req:TransactionStatus>');
        });

        Carbon::setTestNow();
    }

    public function test_single_transaction_and_empty_list_are_handled(): void
    {
        $this->fakeHuawei(null, $this->txnXml([$this->item('R-1', 'CashIn', '500.00', '20261002154956', 'Cash In at POS(8932019 - X) to Customer(22899990287 - A B)')]));
        $one = app(HuaweiCpsService::class)->fetchAccount('8932019');
        $this->assertCount(1, $one['transactions']);

        $this->fakeHuawei(null, $this->ok('<res:SyncQueryOrgTxnResult><res:TransactionListData><res:NbrOfReturned>0</res:NbrOfReturned><res:NbrOfTotal>0</res:NbrOfTotal></res:TransactionListData></res:SyncQueryOrgTxnResult>'));
        $none = app(HuaweiCpsService::class)->fetchAccount('8932019');
        $this->assertSame([], $none['transactions']);
        $this->assertSame(0, $none['transactions_total']);
    }

    public function test_history_is_paginated_ten_per_page_from_the_cached_list(): void
    {
        $items = [];
        for ($i = 1; $i <= 25; $i++) {
            $items[] = $this->item("R{$i}", 'CashIn', '100.00', sprintf('202610%02d120000', $i), 'Cash In');
        }
        $this->fakeHuawei(null, $this->txnXml($items));
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $first = $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=31")->assertOk();
        $first->assertJsonCount(10, 'transactions')
            ->assertJsonPath('transactions.0.receipt', 'R25')
            ->assertJsonPath('transactions.9.receipt', 'R16')
            ->assertJsonPath('pagination', ['page' => 1, 'per_page' => 10, 'total' => 25, 'last_page' => 3, 'from' => 1, 'to' => 10, 'truncated' => false]);
        Http::assertSentCount(2);

        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=31&page=3")
            ->assertOk()
            ->assertJsonCount(5, 'transactions')
            ->assertJsonPath('transactions.0.receipt', 'R5')
            ->assertJsonPath('pagination.from', 21)
            ->assertJsonPath('pagination.to', 25)
            ->assertJsonPath('cached', true);
        Http::assertSentCount(2); // changer de page n'appelle pas Huawei

        // Page hors limites : ramenée à la dernière page ; page invalide : première page
        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=31&page=99")->assertJsonPath('pagination.page', 3);
        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=31&page=-4")->assertJsonPath('pagination.page', 1);
    }

    public function test_empty_history_still_has_a_valid_pagination(): void
    {
        $this->fakeHuawei(null, $this->ok('<res:SyncQueryOrgTxnResult><res:TransactionListData><res:NbrOfReturned>0</res:NbrOfReturned><res:NbrOfTotal>0</res:NbrOfTotal></res:TransactionListData></res:SyncQueryOrgTxnResult>'));
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")
            ->assertOk()
            ->assertJsonPath('pagination', ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1, 'from' => 0, 'to' => 0, 'truncated' => false])
            ->assertJsonPath('activity.level', 'inactive')
            ->assertJsonPath('activity.last_transaction_at', null);
    }

    public function test_activity_level_follows_the_last_customer_transaction(): void
    {
        Carbon::setTestNow('2026-10-07 12:00:00');
        $tx = fn (string $type, string $at, float $amount = 1000) => ['receipt' => $type . $at, 'type' => $type, 'status' => 'Completed',
            'amount' => $amount, 'currency' => 'XOF', 'completed_at' => $at, 'description' => ''];

        // Dépôt client hier : actif ; l'approvisionnement (GIVE) et les commissions (COMT) plus récents ne comptent pas
        $active = HuaweiCpsService::summarizeActivity([
            $tx('COMT', '2026-10-07 09:00:00', 50), $tx('GIVE', '2026-10-07 08:00:00', 80000),
            $tx('CashIn', '2026-10-06 10:00:00', 600), $tx('CashOut', '2026-10-05 10:00:00', 400),
        ], 7);
        $this->assertSame('active', $active['level']);
        $this->assertSame('2026-10-06 10:00:00', $active['last_transaction_at']);
        $this->assertSame(1, $active['days_since_last']);
        $this->assertSame(2, $active['customer_transactions']);
        $this->assertSame(1000.0, $active['customer_volume']);
        $this->assertSame(['count' => 1, 'amount' => 600.0], $active['cash_in']);
        $this->assertSame(['count' => 1, 'amount' => 400.0], $active['cash_out']);

        $this->assertSame('low', HuaweiCpsService::summarizeActivity([$tx('CashIn', '2026-10-03 11:00:00')], 7)['level']);   // 4 jours
        $this->assertSame('inactive', HuaweiCpsService::summarizeActivity([$tx('CashIn', '2026-09-20 11:00:00')], 30)['level']); // 17 jours
        $this->assertSame('inactive', HuaweiCpsService::summarizeActivity([$tx('GIVE', '2026-10-07 08:00:00')], 7)['level']);    // aucune transaction client
        $this->assertNull(HuaweiCpsService::summarizeActivity([], 7)['days_since_last']);

        Carbon::setTestNow();
    }

    public function test_balance_is_kept_when_only_the_history_fails(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->body(), 'QueryOrganizationBalance')) {
                return Http::response($this->balanceXml(), 200);
            }
            throw new ConnectionException('cURL error 28: timed out');
        });
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=30")
            ->assertOk()
            ->assertJsonPath('balance.available', 343349)
            ->assertJsonPath('partial', true)
            ->assertJsonPath('activity', null)
            ->assertJsonCount(0, 'transactions')
            ->assertJsonPath('pagination.total', 0);
        $this->assertStringContainsString('7 jours', $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=30")->json('transactions_error'));

        // Résultat partiel jamais mis en cache : dès que la passerelle répond, l'historique revient
        $this->fakeHuawei();
        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=30")
            ->assertOk()
            ->assertJsonPath('partial', false)
            ->assertJsonPath('pagination.total', 3);
    }

    public function test_masking_and_timestamp_helpers(): void
    {
        $this->assertSame('Transfer (••••1234)', HuaweiCpsService::maskPersonalData('Transfer (22812341234 - Jean Dupont)'));
        $this->assertSame('POS(1311244 - ETS)', HuaweiCpsService::maskPersonalData('POS(1311244 - ETS)'));
        $this->assertSame('2026-10-01 19:31:52', HuaweiCpsService::parseTimestamp('20261001193152'));
        $this->assertNull(HuaweiCpsService::parseTimestamp('n/a'));
        $this->assertNull(HuaweiCpsService::parseTimestamp(null));
        $this->assertSame('SPEED-MAIN-DIEU 01', HuaweiCpsService::cleanHolderName('8932019 - SPEED-MAIN-DIEU 01', '8932019'));
    }

    // --- Erreurs ----------------------------------------------------------------------------------------

    public function test_rejected_authentication_is_reported(): void
    {
        Http::fake(['*' => Http::response(
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><res:Result xmlns:res="x"><res:Body>'
            . '<res:ResultCode>1003</res:ResultCode><res:ResultDesc>Failed to authenticate the request parameter.</res:ResultDesc>'
            . '</res:Body></res:Result></soapenv:Body></soapenv:Envelope>', 200)]);

        try {
            app(HuaweiCpsService::class)->fetchAccount('8932019');
            $this->fail('HuaweiApiException attendue');
        } catch (HuaweiApiException $e) {
            $this->assertSame('rejected', $e->reason);
            $this->assertSame('1003', $e->resultCode);
            // Le message ne contient jamais les identifiants
            $this->assertStringNotContainsString('cGFzc3dvcmQ=', $e->getMessage());
        }
    }

    public function test_invalid_shortcode_never_reaches_the_api(): void
    {
        Http::fake();

        $this->expectException(HuaweiApiException::class);
        try {
            app(HuaweiCpsService::class)->fetchAccount('12<script>');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_non_xml_response_is_reported_as_bad_response(): void
    {
        Http::fake(['*' => Http::response('<html>502 Bad Gateway</html', 502)]);

        try {
            app(HuaweiCpsService::class)->fetchAccount('8932019');
            $this->fail('HuaweiApiException attendue');
        } catch (HuaweiApiException $e) {
            $this->assertSame('bad_response', $e->reason);
        }
    }

    // --- Endpoint ---------------------------------------------------------------------------------------

    public function test_admin_gets_account_for_pdv_with_shortcode(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00'); // dernière transaction simulée : 1er octobre => 19 jours
        $this->fakeHuawei();
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")
            ->assertOk()
            ->assertJsonPath('shortcode', '8932019')
            ->assertJsonPath('holder_name', 'SPEED-MAIN-DIEU 01')
            ->assertJsonPath('balance.available', 343349)
            ->assertJsonPath('cached', false)
            ->assertJsonCount(3, 'transactions')
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('activity.level', 'inactive') // les transactions simulées datent d'octobre 2026, pas d'aujourd'hui
            ->assertJsonMissingPath('transactions.0.initiator');

        Carbon::setTestNow();
    }

    public function test_response_is_cached_and_refresh_bypasses_the_cache(): void
    {
        $this->fakeHuawei();
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")->assertOk()->assertJsonPath('cached', false);
        Http::assertSentCount(2);

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")->assertOk()->assertJsonPath('cached', true);
        Http::assertSentCount(2); // aucun nouvel appel Huawei

        $this->getJson("/api/point-of-sales/{$pdv->id}/account?refresh=1")->assertOk()->assertJsonPath('cached', false);
        Http::assertSentCount(4);
    }

    public function test_dealer_owner_can_only_see_own_organization(): void
    {
        $this->fakeHuawei();
        $mine = $this->organization();
        $other = $this->organization();
        $ownPdv = $this->pdvWithShortcode('8932019', $mine);
        $otherPdv = $this->pdvWithShortcode('8951993', $other);

        Sanctum::actingAs($this->user('dealer_owner', $mine));

        $this->getJson("/api/point-of-sales/{$ownPdv->id}/account")->assertOk();
        $this->getJson("/api/point-of-sales/{$otherPdv->id}/account")->assertForbidden();
    }

    public function test_dealer_agent_cannot_see_account_even_for_own_pdv(): void
    {
        Http::fake();
        $org = $this->organization();
        $agent = $this->user('dealer_agent', $org);
        $pdv = $this->pdvWithShortcode('8932019', $org, $agent);

        Sanctum::actingAs($agent);

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_requires_authentication(): void
    {
        $pdv = $this->pdvWithShortcode('8932019');

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")->assertUnauthorized();
    }

    public function test_pdv_without_valid_shortcode_returns_422_without_calling_the_api(): void
    {
        Http::fake();
        Sanctum::actingAs($this->user('admin'));

        foreach ([null, '', 'N/A', 'abc'] as $shortcode) {
            $pdv = $this->pdvWithShortcode($shortcode);

            $this->getJson("/api/point-of-sales/{$pdv->id}/account")
                ->assertStatus(422)
                ->assertJsonPath('reason', 'no_shortcode');
        }

        Http::assertNothingSent();
    }

    public function test_unknown_shortcode_returns_404(): void
    {
        Http::fake(['*' => Http::response(
            '<e:Envelope xmlns:e="http://schemas.xmlsoap.org/soap/envelope/"><e:Body><r:Result xmlns:r="x"><r:Body>'
            . '<r:ResultCode>3004</r:ResultCode><r:ResultDesc>The identity does not exist or the security credential authentication fails.</r:ResultDesc>'
            . '</r:Body></r:Result></e:Body></e:Envelope>', 200)]);
        $pdv = $this->pdvWithShortcode('1234567');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")
            ->assertNotFound()
            ->assertJsonPath('reason', 'unknown_shortcode');
    }

    public function test_unreachable_service_returns_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")
            ->assertStatus(503)
            ->assertJsonPath('reason', 'unreachable');

        // Les erreurs ne sont jamais mises en cache
        $this->fakeHuawei();
        $this->getJson("/api/point-of-sales/{$pdv->id}/account")->assertOk();
    }

    public function test_missing_configuration_returns_503_without_calling_the_api(): void
    {
        config(['services.huawei.caller_password' => null]);
        Http::fake();
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account")
            ->assertStatus(503)
            ->assertJsonPath('reason', 'not_configured');
        Http::assertNothingSent();
    }

    public function test_days_parameter_is_clamped(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->fakeHuawei();
        $pdv = $this->pdvWithShortcode('8932019');
        Sanctum::actingAs($this->user('admin'));

        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=9999")->assertOk()->assertJsonPath('period.days', 31);
        $this->getJson("/api/point-of-sales/{$pdv->id}/account?days=0&refresh=1")->assertOk()->assertJsonPath('period.days', 7);

        Carbon::setTestNow();
    }
}
