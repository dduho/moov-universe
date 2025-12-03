<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Services\XmlExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    protected $xmlService;

    public function __construct(XmlExportService $xmlService)
    {
        $this->xmlService = $xmlService;
    }

    public function exportXml(Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::where('status', 'validated')
            ->with(['organization', 'creator', 'validator']);

        // Filter based on user role
        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        // Apply optional filters
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        if ($request->has('prefecture')) {
            $query->where('prefecture', $request->prefecture);
        }

        if ($request->has('date_from')) {
            $query->where('validated_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('validated_at', '<=', $request->date_to);
        }

        $xml = $this->xmlService->exportToXml($query->get());

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="pdv_export_' . date('Y-m-d_His') . '.xml"');
    }

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::where('status', 'validated')
            ->with(['organization', 'creator']);

        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        $pdvs = $query->get();

        $csv = "ID,Numero,Dealer,Numero Flooz,Nom Point,Region,Prefecture,Commune,Latitude,Longitude,Status,Created At\n";
        
        foreach ($pdvs as $pdv) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $pdv->id,
                $pdv->numero,
                $pdv->dealer_name,
                $pdv->numero_flooz,
                $pdv->nom_point,
                $pdv->region,
                $pdv->prefecture,
                $pdv->commune,
                $pdv->latitude,
                $pdv->longitude,
                $pdv->status,
                $pdv->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="pdv_export_' . date('Y-m-d_His') . '.csv"');
    }
}

