<?php

namespace App\Http\Controllers;

use App\Models\GeographicHierarchy;
use Illuminate\Http\Request;

class GeographyController extends Controller
{
    public function getRegions()
    {
        $regions = GeographicHierarchy::getRegions();
        return response()->json($regions);
    }

    public function getPrefectures(Request $request)
    {
        $request->validate([
            'region' => 'required|string',
        ]);

        $prefectures = GeographicHierarchy::getPrefecturesByRegion($request->region);
        return response()->json($prefectures);
    }

    public function getCommunes(Request $request)
    {
        $request->validate([
            'prefecture' => 'required|string',
        ]);

        $communes = GeographicHierarchy::getCommunesByPrefecture($request->prefecture);
        return response()->json($communes);
    }

    public function getCantons(Request $request)
    {
        $request->validate([
            'commune' => 'required|string',
        ]);

        $cantons = GeographicHierarchy::getCantonsByCommune($request->commune);
        return response()->json($cantons);
    }

    public function getHierarchy()
    {
        $hierarchy = GeographicHierarchy::all()
            ->groupBy('region')
            ->map(function ($items) {
                return $items->groupBy('prefecture')->map(function ($prefItems) {
                    return $prefItems->pluck('commune')->unique()->values();
                });
            });

        return response()->json($hierarchy);
    }
}

