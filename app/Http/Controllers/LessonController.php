<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Notifications\MaterialApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class LessonController extends Controller
{
    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        // UPDATED: Max 15MB (15360 KB) and all fields are strictly required
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:15360', 
            'available_from' => 'required|date',
            'available_until' => 'required|date|after_or_equal:available_from',
        ], [
            'file.max' => 'The uploaded file must not exceed 15MB.',
            'available_from.required' => 'The "Show From" date is required.',
            'available_until.required' => 'The "Archive On" date is required.',
        ]);

        $path = $request->file('file')->store('lessons', 'public');

        $requireApproval = Setting::where('key', 'require_material_approval')->value('value') ?? 'true';
        $initialStatus = ($requireApproval === 'true') ? 'pending' : 'approved';

        $course->lessons()->create([
            'title' => $request->title,
            'attachment_path' => $path, 
            'approval_status' => $initialStatus,
            'available_from' => $request->available_from, 
            'available_until' => $request->available_until, 
        ]);

        $message = $initialStatus === 'pending' 
            ? 'Material uploaded and is waiting for admin approval.' 
            : 'Material uploaded and is instantly active.';

        return back()->with('success', $message);
    }
    
    public function approve(Lesson $lesson)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $lesson->update(['approval_status' => 'approved']);
        
        // Notify the teacher who owns the course
        $lesson->course->teacher->notify(new MaterialApproved($lesson));

        return back()->with('success', 'Material has been approved and is now viewable by students.');
    }

    public function destroy(Lesson $lesson)
    {
        if ($lesson->course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        if ($lesson->attachment_path) {
            Storage::disk('public')->delete($lesson->attachment_path);
        }

        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }

    public function archive(Lesson $lesson)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $lesson->update([
            'available_until' => now(),
            'approval_status' => 'approved' 
        ]);
        
        return back()->with('success', 'Material archived successfully.');
    }

    public function bulkApprove(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'lesson_ids' => 'required|array',
            'lesson_ids.*' => 'exists:lessons,id'
        ]);

        $lessons = Lesson::whereIn('id', $request->lesson_ids)->get();

        foreach ($lessons as $lesson) {
            /** @var \App\Models\Lesson $lesson */
            $lesson->update(['approval_status' => 'approved']);
            
            // Notify the teacher
            $lesson->course->teacher->notify(new MaterialApproved($lesson));
        }

        return back()->with('success', count($request->lesson_ids) . ' materials have been approved.');
    }

    // ADMIN: Reject / Disapprove a material with feedback
    public function reject(Request $request, Lesson $lesson)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $lesson->update([
            'approval_status' => 'rejected',
            'rejection_note' => $request->reason
        ]);
        
        return back()->with('success', 'Material has been rejected with feedback.');
    }

    // ADMIN: Instantly Unarchive a material
    public function unarchive(Lesson $lesson)
    {
        if (Auth::user()->role !== 'admin') abort(403);
        
        // Remove the expiration date to make it active again
        $lesson->update(['available_until' => null]);
        return back()->with('success', 'Material unarchived and is active again.');
    }

    // TEACHER: Request to Unarchive a material
    public function teacherUnarchive(Lesson $lesson)
    {
        if ($lesson->course->teacher_id !== Auth::id()) abort(403);
        
        // Remove expiration date, but set back to pending so admin must approve
        $lesson->update([
            'available_until' => null,
            'approval_status' => 'pending'
        ]);
        
        return back()->with('success', 'Unarchive requested. Waiting for admin approval.');
    }

   public function resubmit(Request $request, Lesson $lesson)
    {
        if ($lesson->course->teacher_id !== Auth::id()) abort(403);
        
        $request->validate([
            'file' => 'required|file|max:15360',
        ]);

        if ($lesson->attachment_path) {
            Storage::disk('public')->delete($lesson->attachment_path);
        }

        $path = $request->file('file')->store('lessons', 'public');

        $lesson->update([
            'attachment_path' => $path,
            'approval_status' => 'pending',
            'rejection_note' => null
        ]);
        
        return back()->with('success', 'New material uploaded and resubmitted for approval.');
    }
    public function update(Request $request, Lesson $lesson)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'available_from' => 'required|date',
            'available_until' => 'required|date|after_or_equal:available_from',
        ]);

        $lesson->update($request->only('title', 'available_from', 'available_until'));

        return back()->with('success', 'Material details updated successfully.');
    }
}