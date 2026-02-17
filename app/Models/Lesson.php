<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    // Ensure 'attachment_path' is in this list!
    protected $fillable = ['course_id', 'title', 'content', 'video_url', 'attachment_path', 'priority_order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function sections()
    {
        return $this->hasMany(DocumentSection::class);
    }
}