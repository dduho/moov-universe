<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskValidated extends Notification
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
            ->subject('Tâche validée')
            ->line('Votre tâche a été validée avec succès !')
            ->line('**Titre:** ' . $this->task->title)
            ->line('**Point de vente:** ' . $this->task->pointOfSale->nom_point)
            ->action('Voir la tâche', url('/pdv/' . $this->task->point_of_sale_id))
            ->line('Félicitations pour votre travail !');
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'Votre tâche "' . $this->task->title . '" a été validée. Félicitations !',
            'point_of_sale_id' => $this->task->point_of_sale_id,
            'point_of_sale_name' => $this->task->pointOfSale->nom_point ?? null,
            'url' => '/pdv/' . $this->task->point_of_sale_id,
            'type' => 'task_validated'
        ];
    }
}
