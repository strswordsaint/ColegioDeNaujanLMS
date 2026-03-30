<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Announcement;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StudentController extends Controller
{
    public function dashboard(GeminiService $ai)
    {
        $user = Auth::user();
        
        $remedialItems = [];
        
        // Get courses
        $enrolledCourses = $user->enrolledCourses()
            ->wherePivot('status', 'approved')
            ->with([
                'assignments', 
                'assignments.submissions' => function($q) use ($user) {
                    $q->where('student_id', $user->id);
                }, 
                'lessons' => function($q) {
                    $q->where('approval_status', 'approved');
                }
            ])
            ->get();

        foreach ($enrolledCourses as $course) {
            $totalPoints = 0;
            $earnedPoints = 0;
            $hasGradedWork = false;
            $now = now();

            foreach ($course->assignments as $assign) {
                $submission = $assign->submissions->first();
                
                if ($submission && $submission->grade !== null) {
                    $totalPoints += $assign->points;
                    $earnedPoints += $submission->grade;
                    $hasGradedWork = true;
                } elseif (!$submission && $assign->due_date < $now) {
                    $totalPoints += $assign->points;
                    $hasGradedWork = true;
                }
            }

            // Calculate Grade
            $average = ($hasGradedWork && $totalPoints > 0) ? ($earnedPoints / $totalPoints) * 100 : 100;
            
            // if Grade is Below 75%
            if ($average < 75) {
                $pendingAssignments = $course->assignments->filter(fn($a) => !$a->submissions->first())->values()->take(3);
                $availableLessons = $course->lessons->take(3);

                $cacheKey = "remedial_plan_{$user->id}_{$course->id}";

                $aiSuggestions = Cache::remember($cacheKey, 3600, function () use ($ai, $course, $average, $pendingAssignments, $availableLessons) {
                    return $ai->recommendStudyPlan($course->title, round($average), $pendingAssignments, $availableLessons);
                });

                if ($aiSuggestions) {
                    foreach ($aiSuggestions as $suggestion) {
                        $item = ($suggestion['type'] === 'assignment') 
                            ? $pendingAssignments->firstWhere('id', $suggestion['id']) 
                            : $availableLessons->firstWhere('id', $suggestion['id']);

                        if ($item) {
                            $item->remedial_type = $suggestion['type'];
                            $item->remedial_tip = $suggestion['tip'];
                            $item->course_title = $course->title;
                            $remedialItems[] = $item;
                        }
                    }
                } else {
                    if ($assign = $pendingAssignments->first()) {
                        $assign->remedial_type = 'assignment';
                        $assign->remedial_tip = 'Complete this to improve your grade.';
                        $assign->course_title = $course->title;
                        $remedialItems[] = $assign;
                    }
                }
            }
        }

        // --- 2. STANDARD DASHBOARD DATA (No Changes Here) ---
        $approvedCourseIds = $enrolledCourses->pluck('id');
        
        $pendingAssignments = Assignment::whereIn('course_id', $approvedCourseIds)
            ->whereDoesntHave('submissions', function($q) use ($user) { $q->where('student_id', $user->id); })
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->with('course:id,title')
            ->get();

        $recentAnnouncements = Announcement::whereIn('course_id', $approvedCourseIds)
            ->where('created_at', '>=', now()->subDays(7)) // NEW: Only show updates from the last 7 days
            ->latest()
            ->take(5) // Increased from 3 to 5 since they auto-delete quickly
            ->with('course:id,title', 'user:id,name')
            ->get();

        return Inertia::render('Student/Dashboard', [
            'stats' => [
                'courses' => $approvedCourseIds->count(), 
                'pending' => $pendingAssignments->count()
            ],
            'upcoming' => $pendingAssignments,
            'announcements' => $recentAnnouncements,
            'remedial' => $remedialItems,
        ]);
    }

    public function assignments()
    {
        $user = Auth::user();
        $courses = $user->enrolledCourses()
            ->wherePivot('status', 'approved') 
            ->with(['assignments' => function($query) use ($user) {
                $query->orderBy('due_date', 'asc')
                      ->with(['submissions' => function($subQuery) use ($user) {
                          $subQuery->where('student_id', $user->id);
                      }]);
            }])
            ->get();

        return Inertia::render('Student/AssignmentList', [
            'courses' => $courses
        ]);
    }

    public function courses()
    {
        $user = Auth::user();
        $joinedCourses = $user->enrolledCourses()
            ->select('courses.*')
            ->withPivot('status')
            ->get();

        return Inertia::render('Student/CourseList', [
            'joinedCourses' => $joinedCourses
        ]);
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:courses,enrollment_code',
        ], [
            'code.exists' => 'Invalid class code.'
        ]);

        $course = Course::where('enrollment_code', $request->code)->first();

        $exists = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Already joined.']);
        }

        Enrollment::create([
            'user_id' => Auth::id(),
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'pending' 
        ]);

        return redirect()->route('student.courses')->with('success', 'Joined! Waiting for approval.');
    }

    public function leave(Course $course)
    {
        $enrollment = Enrollment::where('user_id', Auth::id())
                                ->where('course_id', $course->id)
                                ->first();

        if ($enrollment) {
            $enrollment->delete();
            return redirect()->route('student.courses')->with('success', 'You have left the class.');
        }

        return back()->with('error', 'You are not enrolled in this class.');
    }

    public function show(Course $course)
    {
        $user = Auth::user();
        
        $enrollment = $user->enrolledCourses()->where('course_id', $course->id)->first();
        if (!$enrollment) abort(403);

        if ($enrollment->pivot->status !== 'approved') {
            return redirect()->route('student.courses')->with('error', 'Your enrollment is still pending approval.');
        }

        $course->load([
            'lessons' => function($query) {
                $query->where('approval_status', 'approved');
            },
            'announcements.user',          
            'announcements.comments.user', 
            'assignments.submissions' => function($q) use ($user) {
                $q->where('student_id', $user->id);
            }
        ]);

        return Inertia::render('Student/CourseShow', [
            'course' => $course
        ]);
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $user = Auth::user();
        $isApproved = $user->enrolledCourses()
                           ->where('course_id', $assignment->course_id)
                           ->wherePivot('status', 'approved')
                           ->exists();
                           
        if (!$isApproved) abort(403, 'You are not approved in this course.');

        // NEW: Check if Hard Deadline (Closing Date) has passed
        if ($assignment->closing_date && now()->greaterThan($assignment->closing_date)) {
            return back()->with('error', 'This task is locked. The final deadline has passed and no further submissions are accepted.');
        }

        $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,jpg,jpeg,png|max:10240', 
            'text_content' => 'nullable|string'
        ], [
            'files.*.mimes' => 'Files must be a document, image, or zip archive.'
        ]);

        // Ensure they submit at least one format
        if (!$request->hasFile('files') && empty($request->text_content)) {
            return back()->with('error', 'Please provide a file or text answer.');
        }

        $paths = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $paths[] = $file->store('submissions', 'public');
            }
        }

        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => Auth::id()],
            [
                'file_paths' => !empty($paths) ? json_encode($paths) : null, // Ensure json_encode for arrays
                'text_content' => $request->text_content,
                'submitted_at' => now()
            ]
        );

        return back()->with('success', 'Submitted!');
    }
    
    public function unsubmit(Assignment $assignment)
    {
        // NEW: Check if Hard Deadline (Closing Date) has passed
        if ($assignment->closing_date && now()->greaterThan($assignment->closing_date)) {
            return back()->with('error', 'This task is locked. You cannot undo a submission after the final deadline has passed.');
        }

        $submission = Submission::where('assignment_id', $assignment->id)
                                ->where('student_id', Auth::id())
                                ->first();

        if ($submission && is_null($submission->grade)) {
            $submission->delete();
            return back()->with('success', 'Submission undone. You can now edit and resubmit.');
        }

        return back()->with('error', 'Cannot undo submission that has already been graded.');
    }
}