@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $assignedUser->name }},
    </div>
    
    <div class="email-body">
        <p>Une révision a été demandée pour votre tâche.</p>
        
        <div class="info-box">
            <div class="info-box-title">🔄 Révision demandée</div>
            <div class="info-item">
                <span class="info-label">Titre :</span>
                <span class="info-value"><strong>{{ $task->title }}</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Point de vente :</span>
                <span class="info-value">{{ $pointOfSale->nom_point }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Demandée par :</span>
                <span class="info-value">{{ $task->validator->name ?? 'Administrateur' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date :</span>
                <span class="info-value">{{ now()->format('d/m/Y à H:i') }}</span>
            </div>
        </div>
        
        @if($task->feedback)
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 24px 0;">
            <p style="font-weight: 600; color: #92400e; margin-bottom: 8px;">📝 Commentaires de révision :</p>
            <p style="color: #78350f; margin: 0;">{{ $task->feedback }}</p>
        </div>
        @endif
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/pdv/{{ $pointOfSale->id }}" class="btn">
                Réviser la tâche
            </a>
        </div>
        
        <div class="divider"></div>
        
        <p style="font-size: 14px; color: #718096;">
            💡 <strong>Action requise :</strong> Veuillez prendre en compte les commentaires et apporter les corrections nécessaires.
        </p>
    </div>
@endsection
