<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Universal Search for Posts, Comments, Categories or All
     */
    public function all(Request $request, $type = 'all'): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'nullable|string|max:255',
            'category' => 'nullable|string',
            'author' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'media_type' => 'nullable|in:none,image,video',
            'sort' => 'nullable|in:latest,oldest,popular',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $allowedTypes = ['posts', 'comments', 'categories', 'all'];
        if (!in_array($type, $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid type. Allowed: posts, comments, categories, or all",
            ], 422);
        }

        // Individual searches
        if ($type === 'posts') {
            return $this->searchPosts($request);
        }

        if ($type === 'comments') {
            return $this->searchComments($request);
        }

        if ($type === 'categories') {
            return $this->searchCategories($request);
        }

        // Search Everything
        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $this->searchPostsRaw($request),
                'comments' => $this->searchCommentsRaw($request),
                'categories' => $this->searchCategoriesRaw($request),
            ]
        ]);
    }

    /**
     * Search Posts
     */
    public function searchPosts(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->searchPostsRaw($request)
        ]);
    }

    private function searchPostsRaw(Request $request)
    {
        $query = Post::with(['user', 'categories', 'comments'])
            ->published();

        // Keyword
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        // Category
        if ($request->filled('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('slug', $request->category)
                  ->orWhere('name', 'like', "%{$request->category}%");
            });
        }

        // Author
        if ($request->filled('author')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('username', $request->author)
                  ->orWhere('name', 'like', "%{$request->author}%")
                  ->orWhere('email', 'like', "%{$request->author}%");
            });
        }

        // Date
        if ($request->filled('date_from')) {
            $query->whereDate('published_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('published_at', '<=', $request->date_to);
        }

        // Media type
        if ($request->filled('media_type')) {
            $query->where('media_type', $request->media_type);
        }

        // Sort
        switch ($request->input('sort', 'latest')) {
            case 'oldest':
                $query->oldest('published_at');
                break;
            case 'popular':
                $query->withCount('comments')->orderBy('comments_count', 'desc');
                break;
            default:
                $query->latest('published_at');
        }

        return $query->paginate(10);
    }

    /**
     * Search Comments
     */
    public function searchComments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->searchCommentsRaw($request)
        ]);
    }

    private function searchCommentsRaw(Request $request)
    {
        return Comment::with(['post', 'user'])
            ->where('content', 'like', '%' . $request->keyword . '%')
            ->latest()
            ->paginate(10);
    }

    /**
     * Search Categories
     */
    public function searchCategories(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->searchCategoriesRaw($request)
        ]);
    }

    private function searchCategoriesRaw(Request $request)
    {
        return Category::where('name', 'like', '%' . $request->keyword . '%')
            ->orWhere('description', 'like', '%' . $request->keyword . '%')
            ->withCount('posts')
            ->get();
    }
}
