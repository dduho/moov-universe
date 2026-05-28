<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
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

        // --- OTP 2FA ---
        if ($user->phone) {
            // Invalidate any previous unused OTP for this user
            OtpCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            // Generate a 6-digit OTP
            $plainCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $otp = OtpCode::create([
                'user_id'    => $user->id,
                'token'      => Str::uuid()->toString(),
                'code'       => Hash::make($plainCode),
                'expires_at' => now()->addMinutes(5),
            ]);

            // Send via SMPP
            $appName = config('app.name', 'INAM');
            $message = "{$appName} - Votre code de vérification : {$plainCode}. Valide 5 min. Ne le partagez pas.";

            $sent = app(SmppService::class)->sendSms($user->phone, $message);

            if (!$sent) {
                Log::error('OTP SMS delivery failed', ['user_id' => $user->id]);
                // Still return otp_required so the user knows to check, but log the failure
            }

            return response()->json([
                'otp_required' => true,
                'otp_token'    => $otp->token,
                'phone_hint'   => $this->maskPhone($user->phone),
            ]);
        }

        // No phone → direct login (backward compatibility)
        return $this->issueToken($user);
    }

    /**
     * Verify the OTP code submitted by the user after credential validation.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_token' => 'required|string',
            'code'      => 'required|string|size:6',
        ]);

        $otp = OtpCode::where('token', $request->otp_token)->first();

        if (!$otp || !$otp->isValid()) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expiré. Veuillez vous reconnecter.'],
            ]);
        }

        // Increment attempt counter first to prevent timing attacks
        $otp->increment('attempts');

        if (!Hash::check($request->code, $otp->code)) {
            $remaining = 3 - $otp->attempts;
            $msg = $remaining > 0
                ? "Code incorrect. {$remaining} tentative(s) restante(s)."
                : 'Trop de tentatives. Veuillez vous reconnecter.';

            throw ValidationException::withMessages(['code' => [$msg]]);
        }

        // Mark OTP as used
        $otp->update(['used_at' => now()]);

        return $this->issueToken($otp->user);
    }

    /**
     * Resend a fresh OTP for an existing otp_token (same login session).
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['otp_token' => 'required|string']);

        $previous = OtpCode::where('token', $request->otp_token)
            ->whereNull('used_at')
            ->first();

        if (!$previous || $previous->expires_at->addMinutes(5)->isPast()) {
            // Session too old — force re-login
            return response()->json([
                'message' => 'Session expirée. Veuillez vous reconnecter.',
            ], 422);
        }

        // Invalidate previous OTP
        $previous->update(['used_at' => now()]);

        $user      = $previous->user;
        $plainCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'user_id'    => $user->id,
            'token'      => Str::uuid()->toString(),
            'code'       => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(5),
        ]);

        $appName = config('app.name', 'INAM');
        $message = "{$appName} - Votre code de vérification : {$plainCode}. Valide 5 min. Ne le partagez pas.";
        app(SmppService::class)->sendSms($user->phone, $message);

        return response()->json([
            'otp_token'  => $otp->token,
            'phone_hint' => $this->maskPhone($user->phone),
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
            'user'                 => $user,
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Changer le mot de passe (première connexion ou volontaire)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password'     => [
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
            'new_password.required'     => 'Le nouveau mot de passe est requis.',
            'new_password.confirmed'    => 'La confirmation du mot de passe ne correspond pas.',
            'new_password.min'          => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        if (Hash::check($request->new_password, $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien.'],
            ]);
        }

        $user->update([
            'password'             => Hash::make($request->new_password),
            'must_change_password' => false,
            'password_changed_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
            'user'    => $user->fresh()->load(['role', 'organization']),
        ]);
    }

    /**
     * Obtenir les règles de validation du mot de passe
     */
    public function getPasswordRules()
    {
        return response()->json([
            'rules' => [
                ['id' => 'length',    'description' => 'Au moins 8 caractères',                          'regex' => '.{8,}'],
                ['id' => 'lowercase', 'description' => 'Au moins une lettre minuscule',                  'regex' => '[a-z]'],
                ['id' => 'uppercase', 'description' => 'Au moins une lettre majuscule',                  'regex' => '[A-Z]'],
                ['id' => 'number',    'description' => 'Au moins un chiffre',                            'regex' => '[0-9]'],
                ['id' => 'special',   'description' => 'Au moins un caractère spécial (!@#$%^&*...)',    'regex' => '[!@#$%^&*(),.?":{}|<>_\\-+=\\[\\]\\\\;\'`~]'],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a Sanctum token and return the standard auth response.
     */
    private function issueToken(User $user): \Illuminate\Http\JsonResponse
    {
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token'                => $token,
            'user'                 => $user->load(['role', 'organization']),
            'must_change_password' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Mask a phone number for display: 22891234567 → +228 ** *** 567
     */
    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $len    = strlen($digits);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 3) . substr($digits, -3);
    }
}

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

