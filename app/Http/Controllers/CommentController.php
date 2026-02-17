<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, Announcement $announcement)
    {
        $request->validate(['content' => 'required|string']);

        $announcement->comments()->create([
            'user_id' => Auth::id(),
            // FIX: Use input('content') instead of ->content
            'content' => $request->input('content'),
        ]);

        return back(); 
    }

    public function destroy(Comment $comment)
    {
        $isAuthor = $comment->user_id === Auth::id();
        $isTeacher = $comment->announcement->course->teacher_id === Auth::id();

        if (!$isAuthor && !$isTeacher) abort(403);

        $comment->delete();

        return back();
    }
}