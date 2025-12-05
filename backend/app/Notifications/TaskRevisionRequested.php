<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskRevisionRequested extends Notification
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
            ->subject('Révision demandée pour votre tâche')
            ->line('Une révision a été demandée pour votre tâche.')
            ->line('**Titre:** ' . $this->task->title)
            ->line('**Point de vente:** ' . $this->task->pointOfSale->nom_point)
            ->line('**Feedback:** ' . $this->task->admin_feedback)
            ->action('Voir la tâche', url('/pdv/' . $this->task->point_of_sale_id))
            ->line('Veuillez apporter les corrections nécessaires.');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'Révision demandée pour la tâche "' . $this->task->title . '". Feedback : ' . $this->task->admin_feedback,
            'point_of_sale_id' => $this->task->point_of_sale_id,
            'point_of_sale_name' => $this->task->pointOfSale->nom_point ?? null,
            'feedback' => $this->task->admin_feedback,
            'url' => '/pdv/' . $this->task->point_of_sale_id,
            'type' => 'task_revision_requested'
        ];
    }
}
