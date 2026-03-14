<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // FIXME: Forces every newly created user row in the database to have these default boolean values - delete this entire block for default "false" values instead
    protected $attributes = [
        'is_super_admin' => true,
        'can_manage_users' => true,
        'can_view_bookings' => true,
        'can_edit_bookings' => true,
        'can_manage_financials' => true,
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
        'can_manage_users',
        'can_view_bookings',
        'can_edit_bookings',
        'can_manage_financials',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
        'can_manage_users' => 'boolean',
        'can_view_bookings' => 'boolean',
        'can_edit_bookings' => 'boolean',
        'can_manage_financials' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
