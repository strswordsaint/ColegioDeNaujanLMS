<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'enrollment_code',
        'title',
        'description',
        'difficulty_level',
        'thumbnail',
        'is_published',
    ];

    // 1. Relationship: A Course belongs to one Teacher (User)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // 2. Relationship: A Course has many Lessons
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('priority_order');
    }

    // 3. Relationship: A Course has many Enrolled Students
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // 4. Relationship: A Course has many Assignments (THIS WAS MISSING)
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
    public function announcements()
    {
        // Load announcements newest first
        return $this->hasMany(Announcement::class)->orderBy('created_at', 'desc');
    }
}