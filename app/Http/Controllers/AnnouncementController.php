<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $request->validate(['content' => 'required|string']);

        $course->announcements()->create([
            'user_id' => Auth::id(),
            // FIX: Use input('content') instead of ->content
            'content' => $request->input('content'), 
        ]);

        return back()->with('success', 'Update posted.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->course->teacher_id !== Auth::id()) abort(403);
        $announcement->delete();
        return back()->with('success', 'Update deleted.');
    }
}