<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the user dashboard with their posts.
     */
    public function index(Request $request)
    {
        // Fetch posts for the authenticated user, with comments count, newest first, paginated
        $posts = \App\Models\Post::where('user_id', Auth::id())->withCount('comments')->latest()->paginate(10);

        return view('dashboard', compact('posts'));
    }
}
