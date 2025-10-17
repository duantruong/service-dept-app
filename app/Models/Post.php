<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Post extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Authenticate user with email and password
     *
     * @param string $email
     * @param string $password
     * @return Post|null
     */
    public static function authenticate($email, $password)
    {
        $user = self::where('email', $email)->first();

        if ($user) {
            // Check if password is hashed (starts with $2y$)
            if (str_starts_with($user->password, '$2y$')) {
                // Password is hashed with Bcrypt
                if (Hash::check($password, $user->password)) {
                    return $user;
                }
            } else {
                // Password is plain text - compare directly
                if ($user->password === $password) {
                    return $user;
                }
            }
        }

        return null;
    }
}