<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = [
        'lesson_content_id',
        'option_text',
        'is_correct',
    ];

    public function lessonContent()
    {
        return $this->belongsTo(LessonContent::class);
    }
}