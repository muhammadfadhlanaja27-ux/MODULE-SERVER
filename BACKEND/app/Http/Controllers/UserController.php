<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\CompletedLesson;
use Illuminate\Http\Request;

/**
 * UserController
 *
 * Menangani fitur yang berkaitan dengan user:
 *  - Registrasi ke course
 *  - Melihat progress belajar
 *
 * Endpoint:
 *  - POST /api/courses/{slug}/register → Daftar course
 *  - GET  /api/progress              → Lihat progress user
 */
class UserController extends Controller
{
    /**
     * E1 · Register to a Course
     *
     * Mendaftarkan user ke course berdasarkan slug.
     * User harus dalam kondisi login (authenticated).
     *
     * @route  POST /api/courses/{slug}/register
     * @access Auth (User)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $course_slug
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "User registered successful"
     * }
     *
     * @response 400 {
     *   "status": "error",
     *   "message": "The user is already registered for this course"
     * }
     *
     * @response 404 {
     *   "status": "not_found",
     *   "message": "Resource not found"
     * }
     */
    public function registerCourse(Request $request, $course_slug)
    {
        // Cari course berdasarkan slug
        $course = Course::where('slug', $course_slug)->first();

        // Jika course tidak ditemukan
        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cek apakah user sudah pernah daftar ke course ini
        $already = Enrollment::where('user_id', $request->user()->id)
                              ->where('course_id', $course->id)
                              ->first();

        if ($already) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The user is already registered for this course',
            ], 400);
        }

        // Simpan data enrollment (user daftar course)
        Enrollment::create([
            'user_id'   => $request->user()->id,
            'course_id' => $course->id,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User registered successful',
        ], 201);
    }

    /**
     * E2 · Get User Progress
     *
     * Mengambil progress belajar user:
     *  - Course yang diikuti
     *  - Lesson yang sudah diselesaikan
     *
     * @route  GET /api/progress
     * @access Auth (User)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "User progress retrieved successfully",
     *   "data": {
     *     "progress": [
     *       {
     *         "course": {},
     *         "completed_lessons": []
     *       }
     *     ]
     *   }
     * }
     */
    public function progress(Request $request)
    {
        // Ambil user yang sedang login
        $user = $request->user();

        // Ambil semua course yang diikuti user (enrollment)
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with('course') // eager loading biar ga nambah query
            ->get();

        // Mapping setiap enrollment menjadi data progress
        $progress = $enrollments->map(function ($enrollment) use ($user) {

            // Ambil semua lesson yang sudah diselesaikan oleh user
            // dan pastikan lesson tersebut milik course ini
            $completedLessons = CompletedLesson::where('user_id', $user->id)
                ->whereHas('lesson.set', function ($query) use ($enrollment) {
                    // Filter berdasarkan course_id dari enrollment
                    $query->where('course_id', $enrollment->course_id);
                })
                ->with('lesson') // ambil detail lesson
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