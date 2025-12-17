<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Récupérer tous les paramètres
     */
    public function index()
    {
        $settings = Setting::all();
        
        // Formater les valeurs selon leur type
        $formattedSettings = $settings->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => Setting::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        });

        return response()->json($formattedSettings);
    }

    /**
     * Récupérer un paramètre spécifique
     */
    public function show($key)
    {
        $value = Setting::get($key);
        
        if ($value === null) {
            return response()->json(['error' => 'Paramètre non trouvé'], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Mettre à jour un paramètre
     */
    public function update(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        $setting = Setting::set($key, $validated['value']);

        return response()->json([
            'message' => 'Paramètre mis à jour avec succès',
            'key' => $key,
            'value' => Setting::get($key),
        ]);
    }
}
