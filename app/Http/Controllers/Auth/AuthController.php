<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Show registration form
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // Handle registration
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users', 'alpha_dash'], // Added username validation
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Welcome to our blog!');
    }

    // Show login form
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login - supports both email and username
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'], // Can be email or username
            'password' => ['required'],
        ]);

        $login = $request->input('login');
        $password = $request->input('password');
        $remember = $request->filled('remember');

        // Determine if login is email or username
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Attempt authentication
        if (Auth::attempt([$fieldType => $login, 'password' => $password], $remember)) {
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/')->with('success', 'You have been logged out.');
    }

    public function dashboard()
    {
        $posts = Auth::user()
            ->posts()
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return view('dashboard', compact('posts'));
    }

}