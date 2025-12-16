@extends('emails.layout')

@section('content')
    <div class="email-greeting">
        Bonjour {{ $assignedUser->name }},
    </div>
    
    <div class="email-body">
        <p>🎉 <strong>Félicitations !</strong> Votre tâche a été validée avec succès.</p>
        
        <div class="info-box">
            <div class="info-box-title">✨ Tâche validée</div>
            <div class="info-item">
                <span class="info-label">Titre :</span>
                <span class="info-value"><strong>{{ $task->title }}</strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Point de vente :</span>
                <span class="info-value">{{ $pointOfSale->nom_point }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Validée par :</span>
                <span class="info-value">{{ $task->validator->name ?? 'Administrateur' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date de validation :</span>
                <span class="info-value">{{ $task->validated_at ? $task->validated_at->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i') }}</span>
            </div>
        </div>
        
        <div style="background-color: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; border-radius: 8px; margin: 24px 0;">
            <p style="color: #065f46; font-weight: 600; margin: 0;">
                ✓ Excellent travail ! La tâche a été approuvée et est maintenant archivée.
            </p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url') }}/pdv/{{ $pointOfSale->id }}" class="btn">
                Voir le PDV
            </a>
        </div>
        
        <div class="divider"></div>
        
        <p style="font-size: 14px; color: #718096;">
            Merci pour votre travail de qualité. Continuez ainsi ! 💪
        </p>
    </div>
@endsection
