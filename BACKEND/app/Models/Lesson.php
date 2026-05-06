<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'name',
        'set_id',
        'order',
    ];

    public function set()
    {
        return $this->belongsTo(Set::class);
    }

    public function contents()
    {
        return $this->hasMany(LessonContent::class)->orderBy('order');
    }

    public function completedLessons()
    {
        return $this->hasMany(CompletedLesson::class);
    }
}