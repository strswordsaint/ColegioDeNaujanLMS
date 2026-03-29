<?php

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaterialApproved extends Notification
{
    use Queueable;

    public $lesson;

    public function __construct(Lesson $lesson)
    {
        $this->lesson = $lesson;
    }

    // We only want to save this to the database for the bell icon
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Material Approved',
            'message' => 'Your material "' . $this->lesson->title . '" has been approved by an admin.',
            'url' => route('teacher.courses.show', $this->lesson->course_id),
            'icon' => 'check-circle',
            'color' => 'text-emerald-500'
        ];
    }
}