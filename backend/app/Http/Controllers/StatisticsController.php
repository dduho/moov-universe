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

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'validated' => (clone $query)->where('status', 'validated')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        // By region
        $byRegion = (clone $query)
            ->select('region', DB::raw('count(*) as count'))
            ->groupBy('region')
            ->get();

        // By organization (only for admins)
        $byOrganization = null;
        if ($user->isAdmin()) {
            $byOrganization = Organization::withCount('pointOfSales')
                ->orderBy('point_of_sales_count', 'desc')
                ->limit(10)
                ->get();
        }

        // Recent PDVs
        $recentPdvs = (clone $query)
            ->with(['organization', 'creator'])
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

