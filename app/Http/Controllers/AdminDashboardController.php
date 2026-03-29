<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson; // Added this import for the materials method
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Setting;

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
        // Include 'status' and 'suspension_reason' in the select
        return Inertia::render('Admin/UserManagement', [
            'users' => User::select('id', 'name', 'email', 'role', 'status', 'suspension_reason', 'school_id', 'created_at')
                ->orderBy('name')
                ->get()
        ]);
    }

    public function toggleUserStatus(Request $request, User $user)
    {
        if ($user->status === 'suspended') {
            // Unsuspend the user
            $user->update([
                'status' => 'active', 
                'suspension_reason' => null
            ]);
            return back()->with('success', "User account has been reactivated.");
        } else {
            // Suspend the user and save the reason
            $request->validate(['reason' => 'required|string|max:500']);
            $user->update([
                'status' => 'suspended', 
                'suspension_reason' => $request->reason
            ]);
            return back()->with('success', "User account has been suspended.");
        }
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
        $courses = Course::with(['teacher:id,name', 'enrollments'])
            ->withCount('lessons', 'assignments')
            ->latest()
            ->get();

        // Fetch teachers so the Admin can assign them to a new course
        $teachers = User::where('role', 'teacher')->select('id', 'name')->get();

        return Inertia::render('Admin/CourseOversight', [
            'courses' => $courses,
            'teachers' => $teachers
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

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:admin,teacher,student',
            'school_id' => 'nullable|string|max:50',
            'program' => 'nullable|string|max:100', // Only used if student
            'contact_number' => 'nullable|string|max:20',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'school_id' => $request->school_id,
            'program' => $request->program,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
            'email_verified_at' => null, // Forces them to verify the email
        ]);

        $user->sendEmailVerificationNotification();

        return back()->with('success', ucfirst($request->role) . ' account created successfully. A verification email has been sent to them.');
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'teacher_id' => 'required|exists:users,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        Course::create([
            'teacher_id' => $request->teacher_id,
            'enrollment_code' => strtoupper(substr(md5(uniqid()), 0, 6)),
            'title' => $request->title,
            'description' => $request->description,
            'difficulty_level' => $request->difficulty_level,
            'thumbnail' => $thumbnailPath ? '/storage/' . $thumbnailPath : null,
            'is_published' => false,
        ]);

        return back()->with('success', 'Course created and assigned successfully.');
    }

    public function materials()
    {
        $materials = Lesson::with(['course:id,title,teacher_id', 'course.teacher:id,name'])
            ->latest()
            ->get();

        // Fetch the global setting (defaults to true if it doesn't exist yet)
        $requireApproval = Setting::where('key', 'require_material_approval')->value('value') ?? 'true';

        return Inertia::render('Admin/MaterialApproval', [
            'materials' => $materials,
            'requireApproval' => $requireApproval === 'true'
        ]);
    }

    public function toggleMaterialApproval(Request $request)
    {
        // Validate password
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'The provided password does not match our records.']);
        }

        $setting = Setting::firstOrCreate(['key' => 'require_material_approval'], ['value' => 'true']);
        $newValue = $setting->value === 'true' ? 'false' : 'true';
        $setting->update(['value' => $newValue]);
        
        $status = $newValue === 'true' ? 'enabled' : 'disabled';
        return back()->with('success', "Material approval system is now {$status}.");
    }
}