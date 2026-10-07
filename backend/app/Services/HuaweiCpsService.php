<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client de l'API SOAP synchrone Huawei CPS (compte "organisation" d'un PDV, identifié par son shortcode).
 *
 * Uniquement des commandes de LECTURE : QueryOrganizationBalance et SyncQueryOrgTxn.
 * Les identifiants viennent de config('services.huawei') (.env), ils ne sont jamais journalisés.
 */
class HuaweiCpsService
{
    /** IdentifierType du ReceiverParty : 4 = shortcode d'organisation */
    private const RECEIVER_TYPE_SHORTCODE = 4;

    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('services.huawei', []);
    }

    public function isConfigured(): bool
    {
        foreach (['url', 'third_party_id', 'identifier', 'caller_password', 'security_credential'] as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Solde du compte principal de l'organisation.
     *
     * @return array réponse décodée (espaces de noms retirés)
     */
    public function queryOrganizationBalance(string $shortcode): array
    {
        $body = '<req:QueryOrganizationBalanceRequest><req:AccountType>Org Main Account</req:AccountType></req:QueryOrganizationBalanceRequest>';

        return $this->call('QueryOrganizationBalance', $shortcode, $body);
    }

    /**
     * Transactions terminées du compte principal sur la période.
     */
    public function queryOrganizationTransactions(string $shortcode, Carbon $start, Carbon $end): array
    {
        $body = '<req:SyncQueryOrgTxnRequest>'
            . '<req:AccountType>Org Main Account</req:AccountType>'
            . '<req:StartDateTime>' . $start->format('YmdHis') . '</req:StartDateTime>'
            . '<req:EndDateTime>' . $end->format('YmdHis') . '</req:EndDateTime>'
            . '<req:TransactionStatus>Completed</req:TransactionStatus>'
            . '<req:Currency>XOF</req:Currency>'
            . '</req:SyncQueryOrgTxnRequest>';

        return $this->call('SyncQueryOrgTxn', $shortcode, $body);
    }

    /** Types de transactions qui ne reflètent pas l'activité auprès des clients (approvisionnement, commissions) */
    private const NON_CUSTOMER_TYPES = ['COMT', 'GIVE'];

    /** Jours sans transaction client : au plus 3 => actif, au plus 7 => peu actif, au-delà => inactif */
    private const ACTIVE_WITHIN_DAYS = 3;
    private const LOW_ACTIVITY_WITHIN_DAYS = 7;

    /** Plafond de transactions conservées en mémoire/cache pour un compte très actif */
    private const MAX_TRANSACTIONS = 3000;

    /**
     * Solde + historique des transactions d'un PDV sur la période, normalisés pour l'affichage.
     * La liste est complète (la pagination se fait côté contrôleur, sur le résultat mis en cache).
     *
     * Si seul l'historique échoue (compte très actif, passerelle lente), le solde est quand même
     * renvoyé avec 'transactions_error' et 'partial' => true.
     *
     * @throws HuaweiApiException si le solde lui-même est indisponible
     */
    public function fetchAccount(string $shortcode, int $days = 7): array
    {
        $days = max(1, min(31, $days));
        $end = now();
        $start = now()->subDays($days - 1)->startOfDay();

        $balanceTree = $this->queryOrganizationBalance($shortcode);
        $items = self::asList(self::find($balanceTree, 'AccountBalanceItem'));
        // Compte principal en priorité (l'API peut renvoyer plusieurs comptes)
        $account = collect($items)->first(fn ($item) => is_array($item) && ($item['AccountTypeName'] ?? '') === 'Org Main Account')
            ?? ($items[0] ?? null);

        if (!is_array($account)) {
            throw new HuaweiApiException('Aucun compte trouvé pour ce shortcode.', 'rejected', resultCode: 'no_account');
        }

        $transactions = [];
        $total = 0;
        $transactionsError = null;

        try {
            $txTree = $this->queryOrganizationTransactions($shortcode, $start, $end);
            $list = self::find($txTree, 'TransactionListData');
            $list = is_array($list) ? $list : [];

            $transactions = collect(self::asList($list['TransactionItem'] ?? null))
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => [
                    'receipt' => (string) ($item['ReceiptNumber'] ?? ''),
                    'type' => (string) ($item['TxnType'] ?? ''),
                    'status' => (string) ($item['TransactionStatus'] ?? ''),
                    'amount' => (float) ($item['Amount'] ?? 0),
                    'currency' => (string) ($item['Currency'] ?? 'XOF'),
                    'completed_at' => self::parseTimestamp($item['CompletedTime'] ?? $item['InitiatedTime'] ?? null),
                    'description' => self::maskPersonalData((string) ($item['Details'] ?? '')),
                ])
                ->sortByDesc('completed_at')
                ->take(self::MAX_TRANSACTIONS)
                ->values()
                ->all();

            $total = max((int) ($list['NbrOfTotal'] ?? 0), count($transactions));
        } catch (HuaweiApiException $e) {
            // Le solde reste affiché ; seul l'historique est indisponible
            $transactionsError = $e->reason === 'unreachable'
                ? "L'historique n'a pas pu être chargé à temps (compte très actif ou service lent). Essayez la période 7 jours."
                : "L'historique des transactions est indisponible : " . $e->getMessage();
        }

        return [
            'shortcode' => $shortcode,
            'holder_name' => self::cleanHolderName($account['AccountHolderPublicName'] ?? null, $shortcode),
            'status' => $account['AccountStatus'] ?? null,
            'currency' => (string) ($account['Currency'] ?? 'XOF'),
            'balance' => [
                'available' => (float) ($account['AvailableBalance'] ?? 0),
                'current' => (float) ($account['CurrentBalance'] ?? 0),
                'reserved' => (float) ($account['ReservedBalance'] ?? 0),
                'uncleared' => (float) ($account['UnclearedBalance'] ?? 0),
            ],
            'activity' => $transactionsError ? null : self::summarizeActivity($transactions, $days),
            'transactions' => $transactions,
            'transactions_total' => $total,
            'transactions_error' => $transactionsError,
            'partial' => $transactionsError !== null,
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString(), 'days' => $days],
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Synthèse de l'activité du PDV à partir de son historique : transactions avec les clients
     * (hors approvisionnement GIVE et commissions COMT), dernière activité et niveau d'activité.
     *
     * @param array<int, array> $transactions triées de la plus récente à la plus ancienne
     */
    public static function summarizeActivity(array $transactions, int $windowDays): array
    {
        $customer = array_values(array_filter(
            $transactions,
            fn ($tx) => !in_array($tx['type'], self::NON_CUSTOMER_TYPES, true) && $tx['completed_at'] !== null
        ));

        $sum = fn (string $type) => [
            'count' => count(array_filter($customer, fn ($tx) => $tx['type'] === $type)),
            'amount' => round(array_sum(array_map(fn ($tx) => $tx['amount'], array_filter($customer, fn ($tx) => $tx['type'] === $type))), 2),
        ];

        $lastAt = $customer[0]['completed_at'] ?? null;
        $daysSince = $lastAt ? (int) floor(\Carbon\Carbon::parse($lastAt)->diffInDays(now(), false)) : null;
        $daysSince = $daysSince !== null ? max(0, $daysSince) : null;

        $level = match (true) {
            $daysSince === null => 'inactive',
            $daysSince <= self::ACTIVE_WITHIN_DAYS => 'active',
            $daysSince <= self::LOW_ACTIVITY_WITHIN_DAYS => 'low',
            default => 'inactive',
        };

        return [
            'level' => $level,
            'last_transaction_at' => $lastAt,
            'days_since_last' => $daysSince,
            'window_days' => $windowDays,
            'customer_transactions' => count($customer),
            'customer_volume' => round(array_sum(array_map(fn ($tx) => $tx['amount'], $customer)), 2),
            'cash_in' => $sum('CashIn'),
            'cash_out' => $sum('CashOut'),
            'thresholds' => ['active' => self::ACTIVE_WITHIN_DAYS, 'low' => self::LOW_ACTIVITY_WITHIN_DAYS],
        ];
    }

    /**
     * "20261001193152" => "2026-10-01 19:31:52" (heure de Lomé = UTC, pas de conversion).
     */
    public static function parseTimestamp(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{14}$/', $value)) {
            return null;
        }

        return \DateTime::createFromFormat('YmdHis', $value)?->format('Y-m-d H:i:s');
    }

    /**
     * Les libellés de transaction contiennent le nom et le numéro des clients, que l'on n'affiche pas :
     * "(22899990287 - Kossi DEGBE)" devient "(client ••••0287)". Le PDV lui-même ("POS(1311244 - NOM)",
     * shortcode de 7 chiffres) reste visible.
     */
    public static function maskPersonalData(string $details): string
    {
        return trim(preg_replace_callback(
            '/\((\d{9,15})\s*-\s*[^)]*\)/u',
            fn ($m) => '(••••' . substr($m[1], -4) . ')',
            $details
        ));
    }

    /**
     * "8932019 - SPEED-MAIN-DIEU 01" => "SPEED-MAIN-DIEU 01"
     */
    public static function cleanHolderName(mixed $name, string $shortcode): ?string
    {
        if (!is_string($name) || trim($name) === '') {
            return null;
        }

        return trim(preg_replace('/^' . preg_quote($shortcode, '/') . '\s*-\s*/', '', $name));
    }

    /**
     * @throws HuaweiApiException
     */
    private function call(string $commandId, string $shortcode, string $requestBody): array
    {
        if (!$this->isConfigured()) {
            throw new HuaweiApiException('Interface Huawei non configurée sur ce serveur.', 'not_configured');
        }

        if (!preg_match('/^\d{3,15}$/', $shortcode)) {
            throw new HuaweiApiException('Shortcode invalide.', 'invalid_shortcode');
        }

        $xml = $this->envelope($commandId, $shortcode, $requestBody);

        [$url, $options] = $this->transportOptions();

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 20))
                ->connectTimeout(8)
                ->withOptions($options)
                ->withBody($xml, 'text/html')
                ->post($url);
        } catch (ConnectionException $e) {
            Log::warning('Huawei CPS injoignable', ['command' => $commandId, 'shortcode' => $shortcode, 'error' => $e->getMessage()]);
            throw new HuaweiApiException('Le service Huawei ne répond pas.', 'unreachable', previous: $e);
        }

        $parsed = $this->parse($response->body());

        if ($parsed === null) {
            Log::warning('Huawei CPS réponse illisible', ['command' => $commandId, 'shortcode' => $shortcode, 'http' => $response->status()]);
            throw new HuaweiApiException('Réponse illisible du service Huawei (HTTP ' . $response->status() . ').', 'bad_response');
        }

        $this->assertSuccess($parsed, $commandId, $shortcode);

        return $parsed;
    }

    /**
     * URL et options de transport. La passerelle est appelée par son IP alors que son certificat
     * (wildcard) est émis pour un nom de domaine : avec HUAWEI_TLS_HOST, on appelle ce nom et on le
     * résout vers l'IP configurée, ce qui garde la validation TLS complète.
     *
     * @return array{0: string, 1: array}
     */
    private function transportOptions(): array
    {
        $url = $this->config['url'];
        $options = ['verify' => (bool) ($this->config['verify_ssl'] ?? true)];

        $tlsHost = $this->config['tls_host'] ?? null;
        $parts = parse_url($url);

        if ($tlsHost && !empty($parts['host']) && ($parts['scheme'] ?? '') === 'https') {
            $port = $parts['port'] ?? 443;
            $options['curl'] = [CURLOPT_RESOLVE => ["{$tlsHost}:{$port}:{$parts['host']}"]];
            $url = 'https://' . $tlsHost . ':' . $port . ($parts['path'] ?? '/');
        }

        return [$url, $options];
    }

    private function envelope(string $commandId, string $shortcode, string $requestBody): string
    {
        $e = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"'
            . ' xmlns:api="http://cps.huawei.com/synccpsinterface/api_requestmgr"'
            . ' xmlns:req="http://cps.huawei.com/synccpsinterface/request"'
            . ' xmlns:com="http://cps.huawei.com/synccpsinterface/common">'
            . '<soapenv:Header/><soapenv:Body><api:Request>'
            . '<req:Header>'
            . '<req:Version>1.0</req:Version>'
            . '<req:CommandID>' . $e($commandId) . '</req:CommandID>'
            . '<req:OriginatorConversationID>' . $e('MU' . bin2hex(random_bytes(10))) . '</req:OriginatorConversationID>'
            . '<req:Caller>'
            . '<req:CallerType>2</req:CallerType>'
            . '<req:ThirdPartyID>' . $e($this->config['third_party_id']) . '</req:ThirdPartyID>'
            . '<req:Password>' . $e($this->config['caller_password']) . '</req:Password>'
            . '<req:ResultURL>' . $e($this->config['result_url'] ?? '') . '</req:ResultURL>'
            . '</req:Caller>'
            . '<req:KeyOwner>1</req:KeyOwner>'
            . '<req:Timestamp>' . gmdate('YmdHis') . '</req:Timestamp>'
            . '</req:Header>'
            . '<req:Body>'
            . '<req:Identity>'
            . '<req:Initiator>'
            . '<req:IdentifierType>14</req:IdentifierType>'
            . '<req:Identifier>' . $e($this->config['identifier']) . '</req:Identifier>'
            . '<req:SecurityCredential>' . $e($this->config['security_credential']) . '</req:SecurityCredential>'
            . '</req:Initiator>'
            . '<req:ReceiverParty>'
            . '<req:IdentifierType>' . self::RECEIVER_TYPE_SHORTCODE . '</req:IdentifierType>'
            . '<req:Identifier>' . $e($shortcode) . '</req:Identifier>'
            . '</req:ReceiverParty>'
            . '</req:Identity>'
            . $requestBody
            . '</req:Body>'
            . '</api:Request></soapenv:Body></soapenv:Envelope>';
    }

    /**
     * Décode la réponse SOAP en tableau (noms d'éléments sans espace de noms). null si ce n'est pas du XML.
     */
    public function parse(string $xml): ?array
    {
        if (trim($xml) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $loaded = $doc->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$doc->documentElement) {
            return null;
        }

        return [$this->localName($doc->documentElement) => $this->nodeToArray($doc->documentElement)];
    }

    private function localName(\DOMNode $node): string
    {
        return $node->localName ?? $node->nodeName;
    }

    private function nodeToArray(\DOMNode $node): array|string
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child;
            }
        }

        if (!$children) {
            return trim($node->textContent);
        }

        // Éléments de même nom regroupés ; un nom unique reste une valeur simple (voir asList() côté appelant)
        $grouped = [];
        foreach ($children as $child) {
            $grouped[$this->localName($child)][] = $this->nodeToArray($child);
        }

        $result = [];
        foreach ($grouped as $name => $values) {
            $result[$name] = count($values) === 1 ? $values[0] : $values;
        }

        return $result;
    }

    /**
     * Normalise un nœud décodé en liste (absent => [], élément unique => [élément], répété => tel quel).
     */
    public static function asList(mixed $node): array
    {
        if ($node === null || $node === '' || $node === []) {
            return [];
        }

        return (is_array($node) && array_is_list($node)) ? $node : [$node];
    }

    /**
     * Cherche la première valeur d'un élément (par nom local) n'importe où dans le tableau décodé.
     */
    public static function find(array|string|null $tree, string $name): mixed
    {
        if (!is_array($tree)) {
            return null;
        }

        if (array_key_exists($name, $tree)) {
            return $tree[$name];
        }

        foreach ($tree as $value) {
            if (is_array($value)) {
                $found = self::find($value, $name);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function assertSuccess(array $parsed, string $commandId, string $shortcode): void
    {
        // SOAP Fault (identifiants refusés, XML invalide...)
        $fault = self::find($parsed, 'Fault');
        if ($fault !== null) {
            $reason = is_array($fault) ? (self::find($fault, 'faultstring') ?? self::find($fault, 'Text') ?? 'SOAP Fault') : 'SOAP Fault';
            Log::warning('Huawei CPS SOAP fault', ['command' => $commandId, 'shortcode' => $shortcode, 'fault' => is_string($reason) ? $reason : json_encode($reason)]);
            throw new HuaweiApiException('Requête refusée par le service Huawei : ' . (is_string($reason) ? $reason : 'erreur SOAP'), 'soap_fault');
        }

        $code = self::find($parsed, 'ResponseCode') ?? self::find($parsed, 'ResultCode');
        if ($code !== null && (string) $code !== '0') {
            $desc = self::find($parsed, 'ResponseDesc') ?? self::find($parsed, 'ResultDesc') ?? '';
            Log::info('Huawei CPS refus', ['command' => $commandId, 'shortcode' => $shortcode, 'code' => $code, 'desc' => is_string($desc) ? $desc : '']);
            throw new HuaweiApiException(
                'Le service Huawei a répondu : ' . (is_string($desc) && $desc !== '' ? $desc : 'code ' . $code),
                'rejected',
                resultCode: (string) $code
            );
        }
    }
}
