<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    /**
     * Show the registration form
     *//*    public function create()
  {
      return view('register');
  }
*/
    /**
     * Store a newly created user
     */
    /*
     public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create user using mass assignment
        // The password will be automatically hashed due to the 'hashed' cast in the User model
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Will be hashed automatically
        ]);

        // Optionally log the user in after registration
        // session([
        //     'user_id' => $user->id,
        //     'user_name' => $user->name,
        //     'user_email' => $user->email,
        //     'logged_in' => true
        // ]);

        return redirect()->route('login')->with('success', 'Registration successful! Please log in.');
    }
    */
}