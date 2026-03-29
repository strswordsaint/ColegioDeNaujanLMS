<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $google_id
 * @property string|null $school_id
 * @property string|null $contact_number
 * @property string|null $avatar
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'suspension_reason',
        'google_id',     
        'school_id',      
        'contact_number', 
        'avatar', 
        'program',  
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'user_id', 'course_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }
}