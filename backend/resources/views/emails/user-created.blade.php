@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $user->name }},
    </div>
    
    <div class="email-body">
        <p>Bienvenue sur <strong>Moov Universe</strong> ! Votre compte a été créé avec succès.</p>
        
        <div class="info-box">
            <div class="info-box-title">👤 Vos informations de compte</div>
            <div class="info-item">
                <span class="info-label">Nom :</span>
                <span class="info-value"><strong>{{ $user->name }}</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email :</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Rôle :</span>
                <span class="info-value">
                    @if($user->role)
                        {{ $user->role->name === 'admin' ? 'Administrateur' : ($user->role->name === 'dealer_owner' ? 'Propriétaire' : 'Agent commercial') }}
                    @else
                        Non défini
                    @endif
                </span>
            </div>
            @if($user->organization)
            <div class="info-item">
                <span class="info-label">Organisation :</span>
                <span class="info-value">{{ $user->organization->name }}</span>
            </div>
            @endif
        </div>
        
        @if($temporaryPassword)
        <div style="margin: 24px 0;">
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 8px;">
                <p style="color: #856404; margin-bottom: 8px; font-weight: 600;">🔐 Mot de passe temporaire</p>
                <p style="color: #856404; margin-bottom: 12px;">Votre mot de passe temporaire est :</p>
                <p style="background-color: #ffffff; padding: 12px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; color: #1a1a1a; text-align: center; letter-spacing: 2px;">
                    {{ $temporaryPassword }}
                </p>
                <p style="color: #856404; margin-top: 12px; font-size: 13px;">
                    ⚠️ Pour des raisons de sécurité, veuillez changer ce mot de passe lors de votre première connexion.
                </p>
            </div>
        </div>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/login" class="btn">
                Se connecter
            </a>
        </div>
        
        <div class="divider"></div>
        
        <div class="tip">
            <p style="margin: 0;">💡 <strong>Astuce :</strong> Explorez les fonctionnalités de la plateforme et n'hésitez pas à contacter l'équipe si vous avez besoin d'aide.</p>
        </div>
    </div>
@endsection
