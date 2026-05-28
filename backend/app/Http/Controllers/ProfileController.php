<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role')
        ]);
    }

    /**
     * Update the authenticated user's profile.
     * Requires current password verification when the email address is changed.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $emailChanging = $request->email && $request->email !== $user->email;

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ];

        if ($emailChanging) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($emailChanging && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors'  => ['current_password' => ['Le mot de passe actuel est incorrect']]
            ], 422);
        }

        $user->update($validator->safe()->only(['name', 'email']));

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user'    => $user->fresh()->load('role')
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect',
                'errors' => [
                    'current_password' => ['Le mot de passe actuel est incorrect']
                ]
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }
}
