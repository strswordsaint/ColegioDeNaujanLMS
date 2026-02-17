<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AIChatController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'status' => session('status'), // Pass status to the welcome page
    ]);
});

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::middleware(['auth'])->group(function () {
    Route::get('/register/onboarding', [GoogleAuthController::class, 'onboarding'])->name('register.onboarding');
    Route::post('/register/complete', [GoogleAuthController::class, 'completeRegistration'])->name('register.complete');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        
        if ($role === 'admin') return redirect()->route('admin.dashboard');
        if ($role === 'teacher') return redirect()->route('teacher.dashboard');
        
        // Redirect pending teachers to welcome page
        if ($role === 'pending_teacher') {
            return redirect('/')->with('status', 'Your teacher account is pending administrator approval.');
        }
        
        if ($role === 'pending' || empty(auth()->user()->school_id)) {
            return redirect()->route('register.onboarding');
        }
        
        return redirect()->route('student.dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/announcements/{announcement}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    
    Route::post('/ai/chat', [AIChatController::class, 'chat'])->name('ai.chat');
});

// STUDENT ROUTES
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/courses', [StudentController::class, 'courses'])->name('courses');
    Route::post('/courses/join', [StudentController::class, 'join'])->name('courses.join');
    Route::get('/courses/{course}', [StudentController::class, 'show'])->name('courses.show');
    Route::delete('/courses/{course}/leave', [StudentController::class, 'leave'])->name('courses.leave');
    Route::get('/my-assignments', [StudentController::class, 'assignments'])->name('assignments');
});

// SHARED SUBMISSION ROUTES
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/assignments/{assignment}/submit', [StudentController::class, 'submit'])->name('assignments.submit');
    Route::post('/assignments/{assignment}/unsubmit', [StudentController::class, 'unsubmit'])->name('assignments.unsubmit');
});

// TEACHER ROUTES
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::resource('courses', CourseController::class);
    Route::patch('/courses/{course}/enrollments/{user}', [CourseController::class, 'approveStudent'])->name('courses.enrollments.approve');
    Route::delete('/courses/{course}/enrollments/{user}', [CourseController::class, 'removeStudent'])->name('courses.enrollments.destroy');
    Route::post('/courses/{course}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/courses/{course}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::patch('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'gradeSubmission'])->name('submissions.grade');
    Route::post('/courses/{course}/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

// ADMIN ROUTES
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users.index');
    Route::get('/courses', [AdminDashboardController::class, 'courses'])->name('courses.index');
    Route::patch('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/approve-teacher', [AdminDashboardController::class, 'approveTeacher'])->name('users.approve-teacher');
    Route::delete('/users/{user}/reject-teacher', [AdminDashboardController::class, 'rejectTeacher'])->name('users.reject-teacher');

});

require __DIR__.'/auth.php';