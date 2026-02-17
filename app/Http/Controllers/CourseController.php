<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
            $code = strtoupper(substr(md5(uniqid()), 0, 6)); // Generates "A1B2C3"
        } while (Course::where('enrollment_code', $code)->exists());

        return $code;
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate Image
        ]);

        // Handle File Upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        Course::create([
            'teacher_id' => Auth::id(),
            'enrollment_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
            'title' => $request->title,
            'description' => $request->description,
            'difficulty_level' => $request->difficulty_level,
            'thumbnail' => $thumbnailPath ? '/storage/' . $thumbnailPath : null, // Save path
            'is_published' => false,
        ]);

        return redirect()->route('teacher.courses.index');
    }

    public function index()
    {
        // Get courses created by THIS teacher
        $courses = Course::where('teacher_id', Auth::id())
                        ->orderBy('created_at', 'desc')
                        ->get();

        return Inertia::render('Teacher/CourseList', [
            'courses' => $courses
        ]);
    }
    
    // 1. UPDATE SHOW METHOD to load Enrollments
    public function show(Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $course->load([
            'assignments', 
            'lessons', 
            'announcements.user',
            'announcements.comments.user',
            // Load Enrollments with Student Data
            'enrollments.user' 
        ]); 

        return Inertia::render('Teacher/CourseManage', [
            'course' => $course
        ]);
    }

    // 2. ADD APPROVE METHOD
    public function approveStudent(Request $request, Course $course, $userId)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $enrollment = $course->enrollments()->where('user_id', $userId)->firstOrFail();
        $enrollment->update(['status' => 'approved']);

        return back()->with('success', 'Student approved.');
    }

    // 3. ADD REMOVE/REJECT METHOD
    public function removeStudent(Request $request, Course $course, $userId)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        $enrollment = $course->enrollments()->where('user_id', $userId)->firstOrFail();
        $enrollment->delete(); // Permanently remove. Change to update status='rejected' if you want to keep history.

        return back()->with('success', 'Student removed from class.');
    }
    // 2. EDIT SETTINGS (Title, Description, Delete)
    public function edit(Request $request, Course $course)
    {
        if ($course->teacher_id !== Auth::id()) abort(403);

        // Determine Back URL: 'manage' goes to Course View, default goes to Course List
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
        if ($course->teacher_id !== Auth::id()) abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['title', 'description', 'difficulty_level']);

        // Handle New File Upload
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $data['thumbnail'] = '/storage/' . $path;
        }

        $course->update($data);

        return redirect()->route('teacher.courses.index'); // Redirect to list
    }

    // 3. Delete the Course
    public function destroy(Course $course)
    {
        if ($course->teacher_id !== Auth::id()) {
            abort(403);
        }

        $course->delete();

        return redirect()->route('teacher.courses.index');
    }
}