<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->withCount('pointOfSales')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_firstname' => 'required|string|max:255',
            'contact_lastname' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
        ]);

        // Générer automatiquement le code dealer
        $prefix = 'DLR';
        $lastOrg = Organization::orderBy('id', 'desc')->first();
        $number = $lastOrg ? $lastOrg->id + 1 : 1;
        $validated['code'] = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
        
        // Utiliser les infos du contact pour phone et email de l'organization
        $validated['phone'] = $validated['contact_phone'];
        $validated['email'] = $validated['contact_email'];
        $validated['is_active'] = true;

        $organization = Organization::create($validated);

        return response()->json($organization, 201);
    }

    public function show($id)
    {
        $organization = Organization::withCount('pointOfSales')
            ->with('users')
            ->findOrFail($id);

        return response()->json($organization);
    }

    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_firstname' => 'sometimes|string|max:255',
            'contact_lastname' => 'sometimes|string|max:255',
            'contact_phone' => 'sometimes|string|max:20',
            'contact_email' => 'sometimes|email|max:255',
        ]);

        // Si les infos de contact sont mises à jour, mettre à jour aussi phone et email
        if (isset($validated['contact_phone'])) {
            $validated['phone'] = $validated['contact_phone'];
        }
        if (isset($validated['contact_email'])) {
            $validated['email'] = $validated['contact_email'];
        }

        $organization->update($validated);

        return response()->json($organization);
    }

    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();

        return response()->json(['message' => 'Organization deleted successfully']);
    }
}

