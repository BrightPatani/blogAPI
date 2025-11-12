<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * Display recent published posts (homepage-like data).
     */
    public function home(): JsonResponse
    {
        $posts = Post::with('user')
            ->published()
            ->latest('published_at')
            ->take(6)
            ->get();

        return response()->json([
            'message' => 'Latest published posts fetched successfully.',
            'posts' => $posts,
        ]);
    }

    /**
     * Display paginated published posts.
     */
    public function index(): JsonResponse
    {
        $posts = Post::with('user')
            ->published()
            ->latest('published_at')
            ->paginate(10);

        return response()->json([
            'message' => 'Posts fetched successfully.',
            'posts' => $posts,
        ]);
    }

    /**
     * Store a new post.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->validated());

        return response()->json([
            'message' => 'Post created successfully!',
            'post' => $post,
        ], 201);
    }

    /**
     * Display a single post (published or owned by user).
     */
    public function show(Post $post): JsonResponse
    {
        // Allow viewing unpublished posts only for the author
        if (!$post->published && (!auth()->check() || auth()->id() !== $post->user_id)) {
            return response()->json([
                'message' => 'Post not found or not accessible.',
            ], 404);
        }

        $post->load(['user', 'comments.user']);

        return response()->json([
            'message' => 'Post fetched successfully.',
            'post' => $post,
        ]);
    }

    /**
     * Update a post.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);

        $post->update($request->validated());

        return response()->json([
            'message' => 'Post updated successfully!',
            'post' => $post,
        ]);
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully!',
        ]);
    }
}
