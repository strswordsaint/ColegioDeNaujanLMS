<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'active_courses' => Course::count(),
            'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }

    public function users()
    {
        // Return all necessary fields for the search and segregation to work
        return Inertia::render('Admin/UserManagement', [
            'users' => User::select('id', 'name', 'email', 'role', 'school_id', 'created_at')
                ->orderBy('name')
                ->get()
        ]);
    }

    public function toggleUserStatus(User $user)
    {
        // Toggle between 'suspended' and their previous/default role
        $newRole = ($user->role === 'suspended') ? 'student' : 'suspended';
        $user->update(['role' => $newRole]);

        return back()->with('success', "User account has been {$newRole}.");
    }

    public function approveTeacher(User $user)
    {
        // Only allow approval if they are actually in the pending_teacher state
        if ($user->role === 'pending_teacher') {
            $user->update(['role' => 'teacher']);
            return back()->with('success', 'Teacher account approved successfully.');
        }

        return back()->with('error', 'This user did not request a teacher account.');
    }

    public function courses()
    {
        return Inertia::render('Admin/CourseOversight', [
            'courses' => Course::with('teacher:id,name')
                ->withCount('enrollments')
                ->latest()
                ->get()
        ]);
    }

    public function rejectTeacher(User $user)
    {
        // Security check: only allow rejection if the role is actually pending_teacher
        if ($user->role === 'pending_teacher') {
            $user->delete();
            return back()->with('success', 'Teacher request rejected and account removed.');
        }

        return back()->with('error', 'Only pending teacher requests can be rejected.');
    }
}