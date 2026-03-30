<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Notifications\EnrollmentApproved;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    // 1. Show the "Create Course" Form
    public function create()
    {
        return Inertia::render('Teacher/CourseCreate');
    }

    // Save the new Course to the Database
    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 6));
        } while (Course::where('enrollment_code', $code)->exists());

        return $code;
    }

    public function store(Request $request)
    {
        // Added 'final' to the difficulty_level to represent 4th year
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,final',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        // Handle File Upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        Course::create([
            'teacher_id' => Auth::id(),
            'enrollment_code' => $this->generateUniqueCode(),
            'title' => $request->title,
            'description' => $request->description,
            'difficulty_level' => $request->difficulty_level,
            'thumbnail' => $thumbnailPath ? '/storage/' . $thumbnailPath : null,
            'is_published' => false,
        ]);

        return redirect()->route('teacher.courses.index');
    }

    public function index()
    {
        $courses = Course::where('teacher_id', Auth::id())
                        ->orderBy('created_at', 'desc')
                        ->get();

        return Inertia::render('Teacher/CourseList', [
            'courses' => $courses
        ]);
    }
    
    // GOD MODE ENABLED: Admins bypass ownership checks
    public function show(Course $course)
    {
        $user = Auth::user();

        if ($user->role === 'student') {
            $enrollment = $user->enrolledCourses()->where('course_id', $course->id)->first();
            if (!$enrollment || $enrollment->pivot->status !== 'approved') {
                abort(403, 'Not enrolled or pending approval.');
            }
        } 
        // If user is a teacher (and NOT an admin), check course ownership
        elseif ($user->role === 'teacher' && $course->teacher_id !== $user->id) {
            abort(403, 'Unauthorized access to another teacher\'s course.');
        }
        // If user is admin, they bypass all checks automatically!

        $course->load([
            'assignments', 
            'lessons' => function ($query) use ($user) {
                if ($user->role === 'student') {
                    $query->where('approval_status', 'approved');
                }
            }, 
            'announcements.user',
            'announcements.comments.user',
            'enrollments.user' 
        ]); 

        if ($user->role === 'student') {
            return Inertia::render('Student/CourseShow', ['course' => $course]);
        }
        
        return Inertia::render('Teacher/CourseManage', ['course' => $course]);
    }

    public function approveStudent(Request $request, Course $course, $userId)
    {
        // Allow course owner OR admin
        if ($course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $enrollment = $course->enrollments()->where('user_id', $userId)->firstOrFail();
        $enrollment->update(['status' => 'approved']);

        $student = User::findOrFail($userId);
        $student->notify(new EnrollmentApproved($course));

        return back()->with('success', 'Student approved and notified!');
    }

    public function removeStudent(Request $request, Course $course, $userId)
    {
        // Allow course owner OR admin
        if ($course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $enrollment = $course->enrollments()->where('user_id', $userId)->firstOrFail();
        $enrollment->delete(); 

        return back()->with('success', 'Student removed from class.');
    }

    public function edit(Request $request, Course $course)
    {
        // Allow course owner OR admin
        if ($course->teacher_id !== Auth::id() && Auth::user()->role !== 'admin') abort(403);

        $backUrl = $request->query('source') === 'manage'
            ? route('teacher.courses.show', $course->id)
            : route('teacher.courses.index');

        return Inertia::render('Teacher/CourseEdit', [
            'course' => $course,
            'backUrl' => $backUrl
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $user = Auth::user();
        
        // Allow course owner OR admin
        if ($course->teacher_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,final',
            'teacher_id' => 'nullable|exists:users,id', 
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['title', 'description', 'difficulty_level', 'teacher_id']);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = '/storage/' . $path;
        }

        $course->update(array_filter($data)); 

        return back()->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $user = Auth::user();

        // Allow course owner OR admin
        if ($course->teacher_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($course->thumbnail) {
            $path = str_replace('/storage/', '', $course->thumbnail);
            Storage::disk('public')->delete($path);
        }

        $course->delete();

        return back()->with('success', 'Course deleted successfully.');
    }

    // GRADEBOOK LOGIC
    public function gradebook(Request $request, ?Course $course = null)
    {
        $teacherId = Auth::id();
        $user = Auth::user();
        
        // Admins can see all courses, Teachers see their own
        if ($user->role === 'admin') {
            $allCourses = Course::select('id', 'title')->orderBy('created_at', 'desc')->get();
        } else {
            $allCourses = Course::where('teacher_id', $teacherId)->select('id', 'title')->orderBy('created_at', 'desc')->get();
        }

        if ($allCourses->isEmpty()) {
            return Inertia::render('Teacher/Gradebook', [
                'course' => null, 'courses' => [], 'assignments' => [], 'students' => []
            ]);
        }

        if (!$course || ($course->teacher_id !== $teacherId && $user->role !== 'admin')) {
            $course = Course::find($allCourses->first()->id);
        }

        $assignments = $course->assignments()->orderBy('created_at')->get();

        $students = User::whereHas('enrolledCourses', function($query) use ($course) {
            $query->where('course_id', $course->id)->where('enrollments.status', 'approved');
        })->with(['submissions' => function($query) use ($course) {
            $query->whereHas('assignment', function($q) use ($course) {
                $q->where('course_id', $course->id);
            });
        }])->orderBy('name')->get();

        return Inertia::render('Teacher/Gradebook', [
            'course' => $course,
            'courses' => $allCourses, 
            'assignments' => $assignments,
            'students' => $students
        ]);
    }
}