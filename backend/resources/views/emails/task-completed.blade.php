@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $admin->name }},
    </div>
    
    <div class="email-body">
        <p>Une tâche a été marquée comme complétée et nécessite votre validation.</p>
        
        <div class="info-box">
            <div class="info-box-title">✅ Tâche complétée</div>
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
            <div class="info-item">
                <span class="info-label">Complétée par :</span>
                <span class="info-value">{{ $task->assignedUser->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date :</span>
                <span class="info-value">{{ $task->completed_at ? $task->completed_at->format('d/m/Y à H:i') : 'N/A' }}</span>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/pdv/{{ $pointOfSale->id }}" class="btn">
                Vérifier et valider
            </a>
        </div>
        
        <div class="divider"></div>
        
        <p style="font-size: 14px; color: #718096;">
            ⚠️ <strong>Action requise :</strong> Veuillez vérifier le travail effectué et valider ou demander une révision si nécessaire.
        </p>
    </div>
@endsection
