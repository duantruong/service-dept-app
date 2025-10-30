<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $email = mb_strtolower(trim($request->input('email')));
        $password = $request->input('password');

        Log::error('Email !' . $email . ' Password !' . $password);

        $user = Post::authenticate($email, $password);

        if ($user) {
            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'logged_in' => true
            ]);

            return redirect()->route('home');
        }

        return back()->with('error', 'Invalid email or password.');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}

