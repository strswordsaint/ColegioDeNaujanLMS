<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    // ... index, create methods remain the same ...
    public function index()
    {
        $courses = Course::where('teacher_id', Auth::id())
            ->with(['assignments' => function($query) {
                $query->orderBy('due_date', 'asc');
            }])
            ->withCount(['assignments as ungraded_count' => function ($query) {
                $query->whereHas('submissions', function ($subQuery) {
                    $subQuery->whereNull('grade');
                });
            }])
            ->get();

        return Inertia::render('Teacher/AssignmentList', [
            'courses' => $courses
        ]);
    }

    public function create(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        // Calculate fallback URL if history back fails
        $backUrl = $request->query('source') === 'global' 
            ? route('teacher.assignments.index') 
            : route('teacher.courses.show', $course->id);

        return Inertia::render('Teacher/AssignmentCreate', [
            'course' => $course,
            'source' => $request->query('source'),
            'backUrl' => $backUrl
        ]);
    }

    // --- UPDATED STORE METHOD ---
    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date', // Strict Validation
            'points' => 'required|integer|min:0',
            'files.*' => 'file|max:20480', 
        ]);

        $paths = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $paths[] = $file->store('assignments', 'public');
            }
        }

        $course->assignments()->create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'points' => $request->points,
            'attachment_paths' => !empty($paths) ? json_encode($paths) : null,
        ]);

        // "Exit the page" -> Redirect to the correct parent page
        if ($request->input('source') === 'global') {
            return redirect()->route('teacher.assignments.index')->with('success', 'Assignment created successfully.');
        }

        return redirect()->route('teacher.courses.show', $course->id)->with('success', 'Assignment created successfully.');
    }

    // ... show, update, destroy, gradeSubmission methods ...
    public function show(Request $request, Assignment $assignment)
    {
        if ($assignment->course->teacher_id !== Auth::id()) abort(403);

        $assignment->load(['submissions.student']);

        $backUrl = $request->query('source') === 'global'
            ? route('teacher.assignments.index')
            : route('teacher.courses.show', $assignment->course_id);

        return Inertia::render('Teacher/AssignmentManage', [
            'assignment' => $assignment,
            'course' => $assignment->course,
            'toBeGraded' => $assignment->submissions->whereNull('grade')->values(),
            'graded' => $assignment->submissions->whereNotNull('grade')->values(),
            'backUrl' => $backUrl
        ]);
    }

    public function update(Request $request, Assignment $assignment)
    {
        if ($assignment->course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'points' => 'required|integer|min:0',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $data = $request->only(['title', 'description', 'due_date', 'points']);

        if ($request->hasFile('files')) {
            if ($assignment->attachment_paths) {
                $oldPaths = json_decode($assignment->attachment_paths, true);
                if (is_array($oldPaths)) {
                    foreach ($oldPaths as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
            $newPaths = [];
            foreach ($request->file('files') as $file) {
                $newPaths[] = $file->store('assignments', 'public');
            }
            $data['attachment_paths'] = json_encode($newPaths);
        }

        $assignment->update($data);

        return back()->with('success', 'Assignment updated.');
    }

    public function destroy(Assignment $assignment)
    {
        if ($assignment->course->teacher_id !== Auth::id()) abort(403);
        $courseId = $assignment->course_id;

        if ($assignment->attachment_paths) {
            $paths = json_decode($assignment->attachment_paths, true);
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
        $assignment->delete();
        return redirect()->route('teacher.courses.show', $courseId)->with('success', 'Assignment deleted.');
    }

    public function gradeSubmission(Request $request, Submission $submission)
    {
        if ($submission->assignment->course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'grade' => 'required|integer|min:0|max:' . $submission->assignment->points,
            'feedback' => 'nullable|string'
        ]);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'graded_at' => now(),
        ]);

        return back()->with('success', 'Grade saved.');
    }
}