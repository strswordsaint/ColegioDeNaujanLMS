<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSection extends Model
{
    protected $connection = 'supabase'; 

    protected $fillable = ['lesson_id', 'content', 'embedding'];
    protected $casts = [
        'embedding' => 'array',
    ];
}