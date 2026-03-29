<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $query = Assignment::with('course:id,title');

        if ($user->role === 'student') {
            $query->whereIn('course_id', $user->enrolledCourses()->pluck('course_id'));
        } elseif ($user->role === 'teacher') {
            $query->whereIn('course_id', $user->courses()->pluck('id'));
        }

        $activities = $query->get()->map(function ($activity) {
            return [
                'key' => 'activity-' . $activity->id,
                'customData' => [
                    'id' => $activity->id,         
                    'course_id' => $activity->course_id, 
                    'title' => $activity->title,
                    'type' => $activity->type,
                    'class' => $activity->type === 'quiz' ? 'bg-red-500' : 'bg-blue-500',
                ],
                'dates' => $activity->due_date, 
            ];
        });

        return Inertia::render('Calendar/Index', [
            'attributes' => $activities
        ]);
    }
}