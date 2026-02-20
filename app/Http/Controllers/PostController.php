<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;



class PostController extends Controller
{
    public function __construct()
    {
        // Require authentication for store, update, and destroy only
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = Post::with(['user', 'categories'])
            ->published()
            ->latest('published_at');

        // Filter by category if provided
        if ($request->has('category')) {
            $category = $request->query('category');
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        $posts = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        // Default to published unless explicitly set to false
        if (!array_key_exists('published', $data)) {
            $data['published'] = true;
            $data['published_at'] = now();
        } elseif ($data['published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // upload image
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts/images', 'public');
            $data['media_type'] = 'image';
        }

        // upload video
        if ($request->hasFile('video')) {
            $data['video'] = $request->file('video')->store('posts/videos', 'public');
            $data['media_type'] = 'video';
        }

        $post = $request->user()->posts()->create($data);

        if ($request->has('categories')) {
            $post->categories()->attach($request->categories);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    public function show(Post $post)
    {
        // Check if post is published OR if the user is the author
       if (!$post->isPublished() && (!Auth::check() || Auth::id() !== $post->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $post->load(['user', 'comments.user', 'categories']);

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        // Authorization: Only the post author can update
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this post'
            ], 403);
        }

        $data = $request->validated();

        // If publishing via update, ensure published_at is set
        if (array_key_exists('published', $data) && $data['published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('image')) {
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }

            $data['image'] = $request->file('image')->store('posts/images', 'public');
            $data['media_type'] = 'image';
            $data['video'] = null;
        }

        if ($request->hasFile('video')) {
            if ($post->video && Storage::disk('public')->exists($post->video)) {
                Storage::disk('public')->delete($post->video);
            }

            $data['video'] = $request->file('video')->store('posts/videos', 'public');
            $data['media_type'] = 'video';
            $data['image'] = null;
        }

        if ($request->remove_media ?? false) {
            if ($post->image) Storage::disk('public')->delete($post->image);
            if ($post->video) Storage::disk('public')->delete($post->video);

            $data['image'] = null;
            $data['video'] = null;
            $data['media_type'] = 'none';
        }

        $post->update($data);

        if ($request->has('categories')) {
            $post->categories()->sync($request->categories);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        // Authorization: Only the post author can delete
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this post'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}