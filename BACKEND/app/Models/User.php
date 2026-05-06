<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Enrollment;
use App\Models\CompletedLesson;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'full_name',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function completedLessons()
    {
        return $this->hasMany(CompletedLesson::class);
    }
}