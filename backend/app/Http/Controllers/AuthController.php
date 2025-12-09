<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est désactivé.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['role', 'organization']),
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'organization']);
        
        return response()->json([
            'user' => $user,
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Changer le mot de passe (première connexion ou volontaire)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        // Règles de validation du mot de passe sécurisé
        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ], [
            'current_password.required' => 'Le mot de passe actuel est requis.',
            'new_password.required' => 'Le nouveau mot de passe est requis.',
            'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'new_password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if (Hash::check($request->new_password, $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien.'],
            ]);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
            'user' => $user->fresh()->load(['role', 'organization']),
        ]);
    }

    /**
     * Obtenir les règles de validation du mot de passe
     */
    public function getPasswordRules()
    {
        return response()->json([
            'rules' => [
                ['id' => 'length', 'description' => 'Au moins 8 caractères', 'regex' => '.{8,}'],
                ['id' => 'lowercase', 'description' => 'Au moins une lettre minuscule', 'regex' => '[a-z]'],
                ['id' => 'uppercase', 'description' => 'Au moins une lettre majuscule', 'regex' => '[A-Z]'],
                ['id' => 'number', 'description' => 'Au moins un chiffre', 'regex' => '[0-9]'],
                ['id' => 'special', 'description' => 'Au moins un caractère spécial (!@#$%^&*...)', 'regex' => '[!@#$%^&*(),.?":{}|<>_\\-+=\\[\\]\\\\;\'`~]'],
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'organization_id' => $request->organization_id,
            'is_active' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['role', 'organization']),
        ], 201);
    }
}

