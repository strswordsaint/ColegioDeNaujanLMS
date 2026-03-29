<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewAnnouncementPosted;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'video_link' => 'nullable|url|max:255',
            'content' => 'required|string'
        ]);

        $announcement = $course->announcements()->create([
            'user_id' => Auth::id(),
            'title' => $request->input('title'),
            'video_link' => $request->input('video_link'),
            'content' => $request->input('content'), 
        ]);

        $students = User::whereHas('enrolledCourses', function($query) use ($course) {
            $query->where('course_id', $course->id)->where('enrollments.status', 'approved');
        })->get();

        Notification::send($students, new NewAnnouncementPosted($announcement));

        return back()->with('success', 'Update posted.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->course->teacher_id !== Auth::id()) abort(403);
        $announcement->delete();
        return back()->with('success', 'Update deleted.');
    }
}