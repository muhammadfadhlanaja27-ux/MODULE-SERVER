<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\CompletedLesson;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // E1: Register to a Course (User only)
    public function registerCourse(Request $request, $course_slug)
    {
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cek apakah sudah terdaftar
        $already = Enrollment::where('user_id', $request->user()->id)
                              ->where('course_id', $course->id)
                              ->first();

        if ($already) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The user is already registered for this course',
            ], 400);
        }

        Enrollment::create([
            'user_id'   => $request->user()->id,
            'course_id' => $course->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User registered successful',
        ], 201);
    }

    // E2: Get User Progress (User only)
    public function progress(Request $request)
    {
        $user = $request->user();

        // Ambil semua enrollment user beserta course
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with('course')
            ->get();

        $progress = $enrollments->map(function ($enrollment) use ($user) {
            // Ambil semua lesson yang sudah completed di course ini
            $completedLessons = CompletedLesson::where('user_id', $user->id)
                ->whereHas('lesson.set', function ($query) use ($enrollment) {
                    $query->where('course_id', $enrollment->course_id);
                })
                ->with('lesson')
                ->get()
                ->map(function ($completed) {
                    return [
                        'id'    => $completed->lesson->id,
                        'name'  => $completed->lesson->name,
                        'order' => $completed->lesson->order,
                    ];
                });

            return [
                'course'            => $enrollment->course,
                'completed_lessons' => $completedLessons,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'User progress retrieved successfully',
            'data'    => [
                'progress' => $progress,
            ],
        ], 200);
    }
}