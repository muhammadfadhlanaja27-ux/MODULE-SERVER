<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin only
    Route::middleware('is.admin')->group(function () {
        // Course

        Route::get('/admin/courses', [CourseController::class, 'adminIndex']);
        
        Route::post('/courses',                 [CourseController::class, 'store']);
        Route::put('/courses/{course_slug}',    [CourseController::class, 'update']);
        Route::delete('/courses/{course_slug}', [CourseController::class, 'destroy']);

        // Set
        Route::post('/courses/{course_slug}/sets',            [SetController::class, 'store']);
        Route::delete('/courses/{course_slug}/sets/{set_id}', [SetController::class, 'destroy']);

        // Lesson
        Route::post('/lessons',               [LessonController::class, 'store']);
        Route::delete('/lessons/{lesson_id}', [LessonController::class, 'destroy']);
    });

    // User only
    Route::middleware('is.user')->group(function () {
        // Lesson
        Route::post('/lessons/{lesson_id}/contents/{content_id}/check', [LessonController::class, 'checkAnswer']);
        Route::put('/lessons/{lesson_id}/complete',                      [LessonController::class, 'complete']);

        // User
        Route::post('/courses/{course_slug}/register', [UserController::class, 'registerCourse']);
        Route::get('/users/progress',                  [UserController::class, 'progress']);
    });

    // User & Admin
    Route::get('/courses',               [CourseController::class, 'index']);
    Route::get('/courses/{course_slug}', [CourseController::class, 'show']);
});