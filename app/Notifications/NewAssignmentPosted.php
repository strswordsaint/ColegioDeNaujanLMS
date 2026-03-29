<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAssignmentPosted extends Notification
{
    use Queueable;

    public $assignment;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Assignment',
            'message' => "A new assignment '{$this->assignment->title}' was posted in {$this->assignment->course->title}.",
            'url' => route('student.courses.show', ['course' => $this->assignment->course_id, 'tab' => 'assignments']),
            'icon' => 'clipboard-document',
            'color' => 'text-blue-500'
        ];
    }
}