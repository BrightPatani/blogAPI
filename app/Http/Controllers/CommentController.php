<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Http\Requests\StoreCommentRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    /**
     * Store a new comment for a post (API)
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated()['content'],
        ]);

        return response()->json([
            'message' => 'Comment added successfully!',
            'comment' => $comment,
        ], 201);
    }

    /**
     * Delete a comment (API)
     */
    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully!',
        ]);
    }
}

