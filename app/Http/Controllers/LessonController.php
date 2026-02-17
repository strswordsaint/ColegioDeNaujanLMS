<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $path = $request->file('file')->store('lessons', 'public');

        $course->lessons()->create([
            'title' => $request->title,
            // FIX: Change 'file_path' to 'attachment_path' to match your database
            'attachment_path' => $path, 
        ]);

        return back()->with('success', 'Lesson added successfully.');
    }

    public function destroy(Lesson $lesson)
    {
        if ($lesson->course->teacher_id !== Auth::id()) abort(403);

        // FIX: Change 'file_path' to 'attachment_path'
        if ($lesson->attachment_path) {
            Storage::disk('public')->delete($lesson->attachment_path);
        }

        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }
}