<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
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
            ->subject('Tâche complétée - En attente de validation')
            ->line('Une tâche a été marquée comme complétée et nécessite votre validation.')
            ->line('**Titre:** ' . $this->task->title)
            ->line('**Point de vente:** ' . $this->task->pointOfSale->nom_point)
            ->line('**Commercial:** ' . $this->task->assignedUser->name)
            ->action('Valider la tâche', url('/pdv/' . $this->task->point_of_sale_id))
            ->line('Veuillez vérifier et valider cette tâche.');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'La tâche "' . $this->task->title . '" a été complétée par ' . $this->task->assignedUser->name . ' et attend votre validation.',
            'point_of_sale_id' => $this->task->point_of_sale_id,
            'point_of_sale_name' => $this->task->pointOfSale->nom_point ?? null,
            'assigned_to' => $this->task->assignedUser->name,
            'url' => '/pdv/' . $this->task->point_of_sale_id,
            'type' => 'task_completed'
        ];
    }
}
