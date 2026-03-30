<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Notifications\AssignmentGraded;
use App\Notifications\NewAssignmentPosted;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class AssignmentController extends Controller
{
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
        if ($course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $backUrl = $request->query('source') === 'global' 
            ? route('teacher.assignments.index') 
            : route('teacher.courses.show', $course->id);

        return Inertia::render('Teacher/AssignmentCreate', [
            'course' => $course,
            'source' => $request->query('source'),
            'backUrl' => $backUrl
        ]);
    }

    public function store(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:assignment,activity,performance_task', 
            'description' => 'nullable|string',
            'points' => 'required|integer|min:1',
            'due_date' => 'required|date',
            'closing_date' => 'nullable|date|after_or_equal:due_date',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $data = $request->only(['title', 'type', 'description', 'points', 'due_date', 'closing_date']);

        if ($request->hasFile('files')) {
            $paths = [];
            foreach ($request->file('files') as $file) {
                $paths[] = $file->store('assignments', 'public');
            }
            $data['attachment_paths'] = json_encode($paths);
        }

        $assignment = $course->assignments()->create($data);
        
        $students = User::whereHas('enrolledCourses', function($query) use ($course) {
            $query->where('course_id', $course->id)->where('enrollments.status', 'approved');
        })->get();

        Notification::send($students, new NewAssignmentPosted($assignment));
    
        $redirectRoute = $request->source === 'global' 
            ? route('teacher.assignments.index') 
            : route('teacher.courses.show', $course->id);

        return redirect($redirectRoute)->with('success', 'Assignment created successfully.');
    }

    public function show(Request $request, Assignment $assignment)
    {
        if ($assignment->course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

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
        if ($assignment->course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:assignment,activity,performance_task', 
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'closing_date' => 'nullable|date|after_or_equal:due_date',
            'points' => 'required|integer|min:0',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $data = $request->only(['title', 'type', 'description', 'due_date', 'closing_date', 'points']);
        
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
        if ($assignment->course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);
        
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
        if ($submission->assignment->course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'grade' => 'required|integer|min:0|max:' . $submission->assignment->points,
            'feedback' => 'nullable|string'
        ]);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        $submission->student->notify(new AssignmentGraded($submission));

        return back()->with('success', 'Grade saved.');
    }
}