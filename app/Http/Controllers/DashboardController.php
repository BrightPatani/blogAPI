<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Show the user dashboard (API version).
     */
    public function index(Request $request): JsonResponse
    {
        // Ensure the user is authenticated via Sanctum
        $user = $request->user();

        // Fetch posts for the authenticated user with comment counts, newest first, paginated
        $posts = Post::where('user_id', $user->id)
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Dashboard data fetched successfully.',
            'user' => $user,
            'posts' => $posts,
        ]);
    }
}
