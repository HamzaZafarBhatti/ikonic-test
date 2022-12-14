<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function connectedConnections()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'user_id', 'connection_id')->whereStatus('connected');
    }

    public function pendingSentConnections()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'user_id', 'connection_id')->whereStatus('pending');
    }

    public function pendingReceivedConnections()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'connection_id', 'user_id')->whereStatus('pending');
    }

    public function rejectedConnections()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'user_id', 'connection_id')->whereStatus('rejected');
    }
}
