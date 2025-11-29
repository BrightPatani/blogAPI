<?php


use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

    // Public routes
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post:slug}', [PostController::class, 'show']);

    // Guest routes (authentication)
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // public Category routes 
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);

    // public search routes
    Route::get('/search/{type?}', [SearchController::class, 'all']);
    Route::get('/search-posts', [SearchController::class, 'searchPosts']);
    Route::get('/search-comments', [SearchController::class, 'searchComments']);
    Route::get('/search-categories', [SearchController::class, 'searchCategories']);


    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [AuthController::class, 'dashboard']);

        // Post management - basic routing
        Route::post('/posts', [PostController::class, 'store']);
        Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
        Route::put('/posts/{post}', [PostController::class, 'update']);
        Route::delete('/posts/{post}', [PostController::class, 'destroy']);

           
        // Comments - basic routing
        Route::post('/posts/{post:slug}/comments', [CommentController::class, 'store']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        
        // Category 
        Route::get('/categories/create', [CategoryController::class, 'create']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });