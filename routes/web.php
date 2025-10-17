<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Post;

// Login page
Route::get('/', function () {
    return view('login');
})->name('login');

// Handle login form submission
Route::post('/login', function (Request $request) {
    // Validate the request
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6',
    ]);

    $email = $request->input('email');
    $password = $request->input('password');

    // Clean and normalize email
    $email = mb_strtolower(trim($email));

    // Use Post model to authenticate user from users table
    $user = Post::authenticate($email, $password);

    // Check if authentication was successful
    if ($user) {
        // Login successful - store user info in session
        session([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'logged_in' => true
        ]);

        return redirect()->route('home.get');
    } else {
        // Login failed - return error message
        return back()->with('error', 'Invalid email or password.');
    }
})->name('login.post');

// Home page route (GET)
Route::get('/home', function () {

    // Check if user is logged in
    if (!session('logged_in')) {
        return redirect()->route('login')->with('error', 'Please login first.');
    }

    return view('home');
})->name('home.get');

// Logout route