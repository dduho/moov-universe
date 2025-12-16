@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $user->name }},
    </div>
    
    <div class="email-body">
        <p>Vos informations de compte sur <strong>Moov Universe</strong> ont été mises à jour.</p>
        
        <div class="info-box">
            <div class="info-box-title">🔄 Informations mises à jour</div>
            
            @if(in_array('name', $updatedFields))
            <div class="info-item">
                <span class="info-label">Nom :</span>
                <span class="info-value"><strong>{{ $user->name }}</strong></span>
            </div>
            @endif
            
            @if(in_array('email', $updatedFields))
            <div class="info-item">
                <span class="info-label">Email :</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            @endif
            
            @if(in_array('role_id', $updatedFields))
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
            @endif
            
            @if(in_array('organization_id', $updatedFields))
            <div class="info-item">
                <span class="info-label">Organisation :</span>
                <span class="info-value">{{ $user->organization ? $user->organization->name : 'Aucune' }}</span>
            </div>
            @endif
            
            @if(in_array('is_active', $updatedFields))
            <div class="info-item">
                <span class="info-label">Statut :</span>
                <span class="info-value">
                    @if($user->is_active)
                        <span style="color: #10b981;">✓ Actif</span>
                    @else
                        <span style="color: #ef4444;">✗ Inactif</span>
                    @endif
                </span>
            </div>
            @endif
            
            <div class="info-item">
                <span class="info-label">Date de mise à jour :</span>
                <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
            </div>
        </div>
        
        <div style="margin: 24px 0;">
            <div style="background-color: #e0f2fe; border-left: 4px solid #0284c7; padding: 16px; border-radius: 8px;">
                <p style="color: #075985; margin: 0;">
                    ℹ️ <strong>Note :</strong> Si vous n'êtes pas à l'origine de ces modifications, veuillez contacter immédiatement un administrateur.
                </p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/login" class="btn">
                Accéder à mon compte
            </a>
        </div>
        
        <div class="divider"></div>
        
        <div class="tip">
            <p style="margin: 0;">🔒 <strong>Sécurité :</strong> Vos informations sont importantes. Assurez-vous de garder vos identifiants confidentiels.</p>
        </div>
    </div>
@endsection
