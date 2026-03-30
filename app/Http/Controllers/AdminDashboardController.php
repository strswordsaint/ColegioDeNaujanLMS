<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Setting;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalCourses = Course::count();
        $totalEnrollments = Enrollment::count();
        $pendingMaterials = Lesson::where('approval_status', 'pending')->count();

        $teachers = User::where('role', 'teacher')->count();
        $students = User::where('role', 'student')->count();
        $admins = User::where('role', 'admin')->count();

        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $enrollmentsOverTime = Enrollment::where('created_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->created_at)->format('M Y');
            })
            ->map(function($group) {
                return $group->count();
            });

        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('M Y');
            $labels[] = $month;
            $data[] = $enrollmentsOverTime->has($month) ? $enrollmentsOverTime[$month] : 0;
        }

        $recentCourses = Course::with('teacher')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalCourses' => $totalCourses,
                'totalEnrollments' => $totalEnrollments,
                'pendingMaterials' => $pendingMaterials,
            ],
            'demographics' => [
                'labels' => ['Students', 'Teachers', 'Admins'],
                'data' => [$students, $teachers, $admins]
            ],
            'enrollmentTrend' => [
                'labels' => $labels,
                'data' => $data
            ],
            'recentCourses' => $recentCourses
        ]);
    }

    public function users()
    {
        return Inertia::render('Admin/UserManagement', [
            'users' => User::select('id', 'name', 'email', 'role', 'status', 'suspension_reason', 'school_id', 'created_at')
                ->orderBy('name')
                ->get()
        ]);
    }

    public function toggleUserStatus(Request $request, User $user)
    {
        if ($user->status === 'suspended') {
            $user->update([
                'status' => 'active', 
                'suspension_reason' => null
            ]);
            return back()->with('success', "User account has been reactivated.");
        } else {
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

        $teachers = User::where('role', 'teacher')->select('id', 'name')->get();

        return Inertia::render('Admin/CourseOversight', [
            'courses' => $courses,
            'teachers' => $teachers
        ]);
    }

    public function rejectTeacher(User $user)
    {
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
            'program' => 'nullable|string|max:100',
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
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        return back()->with('success', ucfirst($request->role) . ' account created successfully. A verification email has been sent to them.');
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,final',
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

        $requireApproval = Setting::where('key', 'require_material_approval')->value('value') ?? 'true';

        return Inertia::render('Admin/MaterialApproval', [
            'materials' => $materials,
            'requireApproval' => $requireApproval === 'true'
        ]);
    }

    public function toggleMaterialApproval(Request $request)
    {
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

    // UPDATED GRADES OVERVIEW: Now calculates Assignments, Activities, and PTs individually
    public function gradesOverview()
    {
        $courses = Course::with(['teacher:id,name', 'assignments'])
            ->with(['enrollments' => function($q) {
                $q->where('status', 'approved')->with(['user' => function($userQ) {
                    $userQ->with(['submissions' => function($subQ) {
                        $subQ->with('assignment:id,type'); // Needed to categorize scores
                    }]);
                }]);
            }])
            ->latest()
            ->get();

        $formattedData = $courses->map(function ($course) {
            
            $assignments = $course->assignments;
            $totalCoursePoints = $assignments->sum('points');
            
            // Calculate Maximums per category
            $maxAssignment = $assignments->where('type', 'assignment')->sum('points');
            $maxActivity = $assignments->where('type', 'activity')->sum('points');
            $maxPT = $assignments->where('type', 'performance_task')->sum('points');

            $studentsData = $course->enrollments->map(function ($enrollment) use ($course, $totalCoursePoints) {
                $student = $enrollment->user;
                $studentTotal = 0;
                $assignmentScore = 0;
                $activityScore = 0;
                $ptScore = 0;

                if ($student && $course->assignments->isNotEmpty()) {
                     $courseAssignmentIds = $course->assignments->pluck('id')->toArray();
                     $submissions = $student->submissions->whereIn('assignment_id', $courseAssignmentIds);

                     foreach($submissions as $sub) {
                         $grade = (float)$sub->grade;
                         $type = $sub->assignment ? $sub->assignment->type : null;
                         
                         $studentTotal += $grade;
                         if ($type === 'assignment') $assignmentScore += $grade;
                         elseif ($type === 'activity') $activityScore += $grade;
                         elseif ($type === 'performance_task') $ptScore += $grade;
                     }
                }

                $percentage = $totalCoursePoints > 0 ? round(($studentTotal / $totalCoursePoints) * 100, 1) : 0;

                return [
                    'id' => $student->id ?? 0,
                    'name' => $student->name ?? 'Unknown',
                    'email' => $student->email ?? '',
                    'total_score' => $studentTotal,
                    'assignment_score' => $assignmentScore,
                    'activity_score' => $activityScore,
                    'pt_score' => $ptScore,
                    'percentage' => $percentage
                ];
            });

            return [
                'id' => $course->id,
                'title' => $course->title,
                'enrollment_code' => $course->enrollment_code,
                'teacher' => $course->teacher->name ?? 'Unassigned',
                'difficulty_level' => $course->difficulty_level,
                'total_points' => $totalCoursePoints,
                'max_assignment' => $maxAssignment,
                'max_activity' => $maxActivity,
                'max_pt' => $maxPT,
                'students' => $studentsData->sortByDesc('percentage')->values()->toArray() 
            ];
        });

        return Inertia::render('Admin/GradesOverview', [
            'courses' => $formattedData
        ]);
    }
}