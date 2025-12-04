<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        $query = PointOfSale::query();
        
        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        // Stats actuelles
        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'validated' => (clone $query)->where('status', 'validated')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'validated_today' => (clone $query)->where('status', 'validated')->whereDate('validated_at', today())->count(),
            'created_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'created_this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        // Stats du mois dernier pour calculer les trends
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        
        $lastMonthStats = [
            'total' => (clone $query)->whereDate('created_at', '<=', $lastMonthEnd)->count(),
            'pending' => (clone $query)->where('status', 'pending')->whereDate('created_at', '<=', $lastMonthEnd)->count(),
            'validated' => (clone $query)->where('status', 'validated')->whereDate('validated_at', '<=', $lastMonthEnd)->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->whereDate('updated_at', '<=', $lastMonthEnd)->count(),
        ];

        // Calculer les trends (pourcentage de changement)
        // Si pas de données le mois dernier et qu'on a des données maintenant, afficher +100%
        $stats['total_trend'] = $lastMonthStats['total'] > 0 
            ? round((($stats['total'] - $lastMonthStats['total']) / $lastMonthStats['total']) * 100, 1) 
            : ($stats['total'] > 0 ? 100 : null);
        $stats['pending_trend'] = $lastMonthStats['pending'] > 0 
            ? round((($stats['pending'] - $lastMonthStats['pending']) / $lastMonthStats['pending']) * 100, 1) 
            : ($stats['pending'] > 0 ? 100 : null);
        $stats['validated_trend'] = $lastMonthStats['validated'] > 0 
            ? round((($stats['validated'] - $lastMonthStats['validated']) / $lastMonthStats['validated']) * 100, 1) 
            : ($stats['validated'] > 0 ? 100 : null);
        $stats['rejected_trend'] = $lastMonthStats['rejected'] > 0 
            ? round((($stats['rejected'] - $lastMonthStats['rejected']) / $lastMonthStats['rejected']) * 100, 1) 
            : ($stats['rejected'] > 0 ? 100 : null);

        // By region - Enrichi avec détails par statut
        $byRegion = (clone $query)
            ->select(
                'region',
                DB::raw('count(*) as total'),
                DB::raw('SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
            )
            ->groupBy('region')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($region) use ($query) {
                // Ajouter les dealers pour cette région
                $dealers = (clone $query)
                    ->select('organization_id', DB::raw('count(*) as count'))
                    ->where('region', $region->region)
                    ->groupBy('organization_id')
                    ->with('organization:id,name,code')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->organization_id,
                            'name' => $item->organization->name ?? 'N/A',
                            'code' => $item->organization->code ?? 'N/A',
                            'count' => $item->count
                        ];
                    })
                    ->sortByDesc('count')
                    ->values();
                
                $region->dealers = $dealers;
                return $region;
            });

        // By organization (only for admins)
        $byOrganization = null;
        if ($user->isAdmin()) {
            $byOrganization = Organization::withCount([
                'pointOfSales',
                'pointOfSales as validated_count' => function ($query) {
                    $query->where('status', 'validated');
                },
                'pointOfSales as pending_count' => function ($query) {
                    $query->where('status', 'pending');
                },
                'pointOfSales as rejected_count' => function ($query) {
                    $query->where('status', 'rejected');
                }
            ])
            ->orderBy('point_of_sales_count', 'desc')
            ->limit(10)
            ->get();
        }

        // Recent PDVs - Optimisé avec select spécifique
        $recentPdvs = (clone $query)
            ->select([
                'id', 'organization_id', 'nom_point', 'numero_flooz', 
                'status', 'region', 'prefecture', 'created_by', 'created_at'
            ])
            ->with(['organization:id,name', 'creator:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'by_region' => $byRegion,
            'by_organization' => $byOrganization,
            'recent_pdvs' => $recentPdvs,
        ]);
    }

    public function byRegion(Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::query();

        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        $data = $query->select('region', 'status', DB::raw('count(*) as count'))
            ->groupBy('region', 'status')
            ->get()
            ->groupBy('region');

        return response()->json($data);
    }

    public function byOrganization(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = Organization::withCount([
            'pointOfSales',
            'pointOfSales as pending_count' => function ($query) {
                $query->where('status', 'pending');
            },
            'pointOfSales as validated_count' => function ($query) {
                $query->where('status', 'validated');
            },
            'pointOfSales as rejected_count' => function ($query) {
                $query->where('status', 'rejected');
            },
        ])->get();

        return response()->json($data);
    }

    public function timeline(Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::query();

        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        $days = $request->get('days', 30);

        $data = $query
            ->select(
                DB::raw('DATE(created_at) as date'),
                'status',
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        return response()->json($data);
    }

    public function validation(Request $request)
    {
        $user = $request->user();

        // Get today's validated and rejected count
        $validatedToday = PointOfSale::where('status', 'validated')
            ->whereDate('updated_at', today())
            ->count();

        $rejectedToday = PointOfSale::where('status', 'rejected')
            ->whereDate('updated_at', today())
            ->count();

        // Calculate average validation time (in hours)
        $avgTime = PointOfSale::whereNotNull('validated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, validated_at)) as avg_hours')
            ->value('avg_hours');

        $averageTime = $avgTime ? round($avgTime) . 'h' : '0h';

        return response()->json([
            'validatedToday' => $validatedToday,
            'rejectedToday' => $rejectedToday,
            'averageTime' => $averageTime,
        ]);
    }
}

