<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nouvelle tâche assignée')
            ->line('Une nouvelle tâche vous a été assignée.')
            ->line('**Titre:** ' . $this->task->title)
            ->line('**Point de vente:** ' . $this->task->pointOfSale->nom_point)
            ->line('**Description:** ' . $this->task->description)
            ->action('Voir la tâche', url('/pdv/' . $this->task->point_of_sale_id))
            ->line('Merci de la traiter dans les plus brefs délais.');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'Nouvelle tâche assignée : "' . $this->task->title . '" pour le PDV ' . ($this->task->pointOfSale->nom_point ?? ''),
            'point_of_sale_id' => $this->task->point_of_sale_id,
            'point_of_sale_name' => $this->task->pointOfSale->nom_point ?? null,
            'url' => '/pdv/' . $this->task->point_of_sale_id,
            'type' => 'task_assigned'
        ];
    }
}
