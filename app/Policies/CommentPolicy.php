<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        // Users can delete their own comments or comments on their posts
        return $user->id === $comment->user_id || $user->id === $comment->post->user_id;
    }
}