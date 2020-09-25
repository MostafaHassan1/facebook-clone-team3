<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

$test = Str::random(50);
class User extends Authenticatable  implements JWTSubject, MustVerifyEmail
{
    use Notifiable;

    public $timestamps = true;

    protected $fillable = [
        'firstname', 'lastname', 'birthdate', 'gender', 'email', 'password','vcode',//'email_verified_at',
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];
    /*
    protected $casts =
    [
        'email_verified_at' => 'datetime',
    ];
    */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
