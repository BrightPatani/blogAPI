<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'categories'])
            ->published()
            ->latest('published_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

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
        $post->load(['user', 'comments.user', 'categories']);

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $data = $request->validated();

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

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}
