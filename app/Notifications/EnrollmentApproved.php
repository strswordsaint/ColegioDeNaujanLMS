<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EnrollmentApproved extends Notification
{
    use Queueable;

    public $course;

    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Enrollment Approved',
            'message' => "You have been officially enrolled in {$this->course->title}!",
            'url' => route('student.courses.show', $this->course->id),
            'icon' => 'check-badge',
            'color' => 'text-emerald-500'
        ];
    }
}