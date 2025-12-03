<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        return SystemSetting::all();
    }

    public function show($key)
    {
        $setting = SystemSetting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        return response()->json([
            'key' => $setting->key,
            'value' => SystemSetting::getValue($key),
            'type' => $setting->type,
            'description' => $setting->description,
        ]);
    }

    public function update(Request $request, $key)
    {
        $request->validate([
            'value' => 'required',
        ]);

        $setting = SystemSetting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        SystemSetting::setValue(
            $key,
            $request->value,
            $setting->type,
            $setting->description
        );

        return response()->json([
            'message' => 'Setting updated successfully',
            'value' => SystemSetting::getValue($key),
        ]);
    }
}
