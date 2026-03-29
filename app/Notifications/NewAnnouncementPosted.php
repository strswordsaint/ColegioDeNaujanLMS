<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAnnouncementPosted extends Notification
{
    use Queueable;

    public $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Announcement',
            'message' => "{$this->announcement->course->teacher->name} posted an announcement in {$this->announcement->course->title}.",
            'url' => route('student.courses.show', ['course' => $this->announcement->course_id]),
            'icon' => 'megaphone',
            'color' => 'text-purple-500'
        ];
    }
}