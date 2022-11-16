<?php

namespace App\Models;

use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\ConsumedSearchHistory;
use App\Models\ApiModel\Plan;
use App\Models\ApiModel\Sales;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function allowed_searches(){
        return  $this->hasMany(AllowedSearch::class);
    }
    public function sales(){
        return  $this->hasMany(Sales::class);
    }
    public function consumed_searches(){
        return  $this->hasMany(ConsumedSearchHistory::class);
    }
}
