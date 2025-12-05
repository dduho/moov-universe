<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_of_sale_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'admin_feedback',
        'completed_at',
        'validated_at',
        'validated_by'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function pointOfSale()
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Marquer la tâche comme complétée
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // Mettre à jour les tags du PDV
        $this->pointOfSale->removeTag('en_revision');
        $this->pointOfSale->addTag('taches_a_valider');
    }

    /**
     * Valider la tâche
     */
    public function validate($validatorId)
    {
        $this->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => $validatorId
        ]);

        // Si toutes les tâches du PDV sont validées, marquer comme complètes
        $pdv = $this->pointOfSale;
        $pendingTasks = $pdv->tasks()->whereNotIn('status', ['validated'])->count();
        
        $pdv->removeTag('taches_a_valider');
        
        if ($pendingTasks === 0) {
            $pdv->removeTag('en_revision');
            $pdv->addTag('taches_completes');
        }
    }

    /**
     * Demander une révision
     */
    public function requestRevision($feedback)
    {
        $this->update([
            'status' => 'revision_requested',
            'admin_feedback' => $feedback
        ]);

        // Remettre le tag "en_revision"
        $this->pointOfSale->removeTag('taches_a_valider');
        $this->pointOfSale->addTag('en_revision');
    }
}
