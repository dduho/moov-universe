<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Services\HuaweiApiException;
use App\Services\HuaweiCpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Solde et dernières transactions du compte Flooz d'un PDV (API Huawei CPS, lecture seule),
 * interrogées avec le shortcode du PDV.
 */
class PdvAccountController extends Controller
{
    /** Résultat Huawei : l'identité (shortcode) n'existe pas ou n'est pas interrogeable */
    private const RESULT_UNKNOWN_IDENTITY = '3004';

    public function show(Request $request, int $id, HuaweiCpsService $huawei): JsonResponse
    {
        $user = $request->user();

        // Solde et transactions du compte : réservés aux administrateurs et aux propriétaires de dealer
        if (!$user->isAdmin() && !$user->isDealerOwner()) {
            return response()->json(['message' => 'Accès réservé aux administrateurs et aux propriétaires de dealer.'], 403);
        }

        $pdv = PointOfSale::findOrFail($id);

        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        $shortcode = trim((string) $pdv->shortcode);
        if ($shortcode === '' || strtoupper($shortcode) === 'N/A' || !preg_match('/^\d{3,15}$/', $shortcode)) {
            return response()->json([
                'message' => "Ce PDV n'a pas de shortcode valide renseigné.",
                'reason' => 'no_shortcode',
            ], 422);
        }

        $days = (int) $request->query('days', 7);
        $days = max(1, min(31, $days ?: 7));
        $cacheKey = "huawei:account:{$shortcode}:{$days}";
        $ttl = max(0, (int) config('services.huawei.cache_ttl', 60));

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $cached = $ttl > 0 && Cache::has($cacheKey);

        try {
            $account = $cached
                ? Cache::get($cacheKey)
                : $huawei->fetchAccount($shortcode, $days);

            // Un résultat partiel (historique indisponible) n'est jamais mis en cache : le prochain appel réessaie
            if (!$cached && $ttl > 0 && empty($account['partial'])) {
                Cache::put($cacheKey, $account, $ttl);
            }
        } catch (HuaweiApiException $e) {
            return $this->errorResponse($e);
        }

        return response()->json($this->paginate($account, $request) + ['cached' => $cached]);
    }

    /**
     * Découpe l'historique en pages (10 par défaut) : la liste complète reste en cache,
     * changer de page n'appelle donc pas à nouveau la passerelle Huawei.
     */
    private function paginate(array $account, Request $request): array
    {
        $perPage = max(5, min(50, (int) $request->query('per_page', 10) ?: 10));
        $all = $account['transactions'];
        $total = count($all);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($lastPage, (int) $request->query('page', 1)));
        $offset = ($page - 1) * $perPage;

        $account['transactions'] = array_slice($all, $offset, $perPage);
        $account['pagination'] = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
            // Plus de transactions que ce que l'on conserve (compte très actif)
            'truncated' => ($account['transactions_total'] ?? $total) > $total,
        ];

        return $account;
    }

    private function errorResponse(HuaweiApiException $e): JsonResponse
    {
        if ($e->reason === 'not_configured') {
            return response()->json(['message' => "L'interface Huawei n'est pas configurée sur ce serveur.", 'reason' => 'not_configured'], 503);
        }

        if ($e->reason === 'unreachable') {
            return response()->json(['message' => 'Le service Huawei est injoignable pour le moment. Réessayez dans un instant.', 'reason' => 'unreachable'], 503);
        }

        if ($e->resultCode === self::RESULT_UNKNOWN_IDENTITY || $e->resultCode === 'no_account') {
            return response()->json(['message' => 'Aucun compte Flooz trouvé pour ce shortcode.', 'reason' => 'unknown_shortcode'], 404);
        }

        return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason], 502);
    }
}
