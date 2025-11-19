<?php


namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // List all categories
    public function index()
    {
        $categories = Category::withCount('posts')->latest()->paginate(15);

        // Return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'categories' => $categories
            ], 200);
        }

        return view('categories.index', compact('categories'));
    }

    // Show single category with its posts
    public function show(Category $category)
    {
        $posts = $category->posts()
            ->with(['user', 'categories'])
            ->published()
            ->latest('published_at')
            ->paginate(10);

        // Return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'category' => $category,
                'posts' => $posts
            ], 200);
        }

        return view('categories.show', compact('category', 'posts'));
    }

    // Show form to create new category (admin only)
    public function create()
    {
        return view('categories.create');
    }

    // Store new category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    // Show form to edit category
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    // Update category
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ], 200);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully!');
    }

    // Delete category
    public function destroy(Category $category)
    {
        $category->delete();

        // Return JSON for API requests
        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully!');
    }
}
