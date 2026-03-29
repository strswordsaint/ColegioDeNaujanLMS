<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    // ADDED title and video_link here
    protected $fillable = ['course_id', 'user_id', 'title', 'video_link', 'content'];

    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function comments() { return $this->hasMany(Comment::class)->orderBy('created_at', 'asc'); }
}