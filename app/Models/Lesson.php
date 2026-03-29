<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id', 
        'title', 
        'content', 
        'video_url', 
        'attachment_path', 
        'priority_order',
        'approval_status',
        'available_from',
        'available_until'
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function sections()
    {
        return $this->hasMany(DocumentSection::class);
    }
}