<?php 

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function home()
    {
        $posts = Post::with('user')
            ->published()
            ->latest('published_at')
            ->take(6)
            ->get();

        return view('home', compact('posts'));
    }

    public function index()
    {
        $posts = Post::with(['user', 'categories'])
            ->published()
            ->latest('published_at')
            ->paginate(10);

        return view('posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = \App\Models\Category::all();
        return view('posts.create', compact('categories'));
    }

    public function store(StorePostRequest $request)
    {
        $data = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts/images', 'public');
            $data['media_type'] = 'image';
        }
        
        // Handle video upload
        if ($request->hasFile('video')) {
            $data['video'] = $request->file('video')->store('posts/videos', 'public');
            $data['media_type'] = 'video';
        }
        
        $post = $request->user()->posts()->create($data);

        // Attach categories to the post
        if ($request->has('categories')) {
            $post->categories()->attach($request->categories);
        }

        return redirect('/posts/' . $post->slug)
            ->with('success', 'Post created successfully!');
    }

    public function show(Post $post)
    {
        // Allow viewing unpublished posts only for the author
        if (!$post->published && (!Auth::check() || Auth::id() !== $post->user_id)) {
            abort(404);
        }

        $post->load(['user', 'comments.user', 'categories']);

        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        Gate::authorize('update', $post);

        $categories = \App\Models\Category::all();
        return view('posts.edit', compact('post', 'categories'));
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);

        $data = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            $data['image'] = $request->file('image')->store('posts/images', 'public');
            $data['media_type'] = 'image';
            $data['video'] = null; // Remove video if uploading image
        }
        
        // Handle video upload
        if ($request->hasFile('video')) {
            // Delete old video
            if ($post->video && Storage::disk('public')->exists($post->video)) {
                Storage::disk('public')->delete($post->video);
            }
            $data['video'] = $request->file('video')->store('posts/videos', 'public');
            $data['media_type'] = 'video';
            $data['image'] = null; // Remove image if uploading video
        }
        
        // Handle media removal
        if ($request->has('remove_media') && $request->remove_media) {
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            if ($post->video && Storage::disk('public')->exists($post->video)) {
                Storage::disk('public')->delete($post->video);
            }
            $data['image'] = null;
            $data['video'] = null;
            $data['media_type'] = 'none';
        }

        $post->update($data);

        // Sync categories
        if ($request->has('categories')) {
            $post->categories()->sync($request->categories);
        } else {
            $post->categories()->detach();
        }

        return redirect('/posts/' . $post->slug)
            ->with('success', 'Post updated successfully!');
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect('/posts')
            ->with('success', 'Post deleted successfully!');
    }
}