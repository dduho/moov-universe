@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $assignedUser->name }},
    </div>
    
    <div class="email-body">
        <p>Une nouvelle tâche vous a été assignée sur la plateforme Moov Universe.</p>
        
        <div class="info-box">
            <div class="info-box-title">📋 Détails de la tâche</div>
            <div class="info-item">
                <span class="info-label">Titre :</span>
                <span class="info-value"><strong>{{ $task->title }}</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Point de vente :</span>
                <span class="info-value">{{ $pointOfSale->nom_point }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Shortcode :</span>
                <span class="info-value">{{ $pointOfSale->shortcode }}</span>
            </div>
            @if($pointOfSale->numero_flooz)
            <div class="info-item">
                <span class="info-label">Numéro Flooz :</span>
                <span class="info-value">{{ $pointOfSale->numero_flooz }}</span>
            </div>
            @endif
            <div class="info-item">
                <span class="info-label">Assignée par :</span>
                <span class="info-value">{{ $task->creator->name ?? 'Administrateur' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date :</span>
                <span class="info-value">{{ $task->created_at->format('d/m/Y à H:i') }}</span>
            </div>
        </div>
        
        @if($task->description)
        <div style="margin: 24px 0;">
            <p style="font-weight: 600; color: #2d3748; margin-bottom: 8px;">Description :</p>
            <p style="color: #4a5568; background-color: #f7fafc; padding: 16px; border-radius: 8px; border-left: 3px solid #FF6B00;">
                {{ $task->description }}
            </p>
        </div>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/pdv/{{ $pointOfSale->id }}" class="btn">
                Voir la tâche
            </a>
        </div>
        
        <div class="divider"></div>
        
        <p style="font-size: 14px; color: #718096;">
            💡 <strong>Astuce :</strong> Connectez-vous à la plateforme pour consulter tous les détails et commencer à travailler sur cette tâche.
        </p>
    </div>
@endsection
