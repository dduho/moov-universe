<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        // Filters
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get activity logs from point_of_sales table
        $query = PointOfSale::query()
            ->with(['creator', 'validator', 'organization'])
            ->orderBy('updated_at', 'desc');

        // Apply filters
        if ($userId) {
            $query->where(function($q) use ($userId) {
                $q->where('created_by', $userId)
                  ->orWhere('validated_by', $userId);
            });
        }

        if ($action) {
            switch ($action) {
                case 'create':
                    $query->whereNotNull('created_at');
                    break;
                case 'validate':
                    $query->where('status', 'validated');
                    break;
                case 'reject':
                    $query->where('status', 'rejected');
                    break;
            }
        }

        if ($startDate) {
            $query->where('updated_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('updated_at', '<=', $endDate . ' 23:59:59');
        }

        $logs = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform data to activity log format
        $transformedData = $logs->map(function($pdv) {
            $action = 'created';
            $user = $pdv->creator;
            $timestamp = $pdv->created_at;

            if ($pdv->status === 'validated' && $pdv->validated_at) {
                $action = 'validated';
                $user = $pdv->validator;
                $timestamp = $pdv->validated_at;
            } elseif ($pdv->status === 'rejected' && $pdv->rejected_at) {
                $action = 'rejected';
                $user = $pdv->validator;
                $timestamp = $pdv->rejected_at;
            }

            return [
                'id' => $pdv->id,
                'action' => $action,
                'resource_type' => 'pdv',
                'resource_id' => $pdv->id,
                'resource_name' => $pdv->nom_point,
                'user' => [
                    'id' => $user?->id,
                    'name' => $user?->name,
                    'email' => $user?->email,
                ],
                'user_id' => $user?->id,
                'point_of_sale' => [
                    'id' => $pdv->id,
                    'nom_point' => $pdv->nom_point,
                    'numero_flooz' => $pdv->numero_flooz,
                    'status' => $pdv->status,
                ],
                'organization' => [
                    'id' => $pdv->organization?->id,
                    'name' => $pdv->organization?->name,
                ],
                'changes' => null,
                'ip_address' => null,
                'details' => $pdv->rejection_reason ?? null,
                'timestamp' => $timestamp,
                'created_at' => $pdv->created_at,
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]);
    }
}
