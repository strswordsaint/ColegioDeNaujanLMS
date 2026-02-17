<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    // RENAMED from 'show' to 'index' to match routes/web.php
    public function index()
    {
        $teacherId = Auth::id();

        // 1. Calculate Real Stats
        $activeCourses = Course::where('teacher_id', $teacherId)->count();
        
        // Get total students across all my courses
        $totalStudents = Course::where('teacher_id', $teacherId)
            ->withCount('enrollments')
            ->get()
            ->sum('enrollments_count');

        // Count ungraded submissions
        $pendingSubmissions = Submission::whereHas('assignment.course', function($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })->whereNull('grade')->count();

        // 2. Dummy Chart Data (Placeholder for now)
        $chartData = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'received' => [12, 19, 3, 5, 2],
            'graded' => [8, 15, 2, 4, 1]
        ];

        // 3. Priority Queue (Assignments needing grading)
        $gradingQueue = Submission::whereHas('assignment.course', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })
            ->whereNull('grade')
            ->with(['assignment', 'assignment.course', 'student'])
            ->limit(5)
            ->get()
            ->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'task' => $sub->assignment->title,
                    'course' => $sub->assignment->course->title,
                    'priority' => 'high', 
                    'count' => 1 
                ];
            });

        return Inertia::render('Teacher/Dashboard', [
            'stats' => [
                'active_courses' => $activeCourses,
                'total_students' => $totalStudents,
                'pending_submissions' => $pendingSubmissions,
                'average_grade' => 88, // Placeholder
            ],
            'chart_data' => $chartData,
            'grading_queue' => $gradingQueue
        ]);
    }
}