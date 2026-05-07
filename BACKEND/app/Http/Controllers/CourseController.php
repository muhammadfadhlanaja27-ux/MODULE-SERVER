<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CourseController
 *
 * Mengelola data kursus (CRUD) beserta endpoint publik untuk melihat kursus.
 * Admin dapat membuat, mengedit, dan menghapus kursus.
 * Pengguna umum hanya dapat melihat kursus yang sudah dipublikasikan.
 *
 * Endpoint yang tersedia:
 *  - POST   /api/courses                    → B1 Tambah kursus (Admin)
 *  - PUT    /api/courses/{course_slug}       → B2 Edit kursus (Admin)
 *  - DELETE /api/courses/{course_slug}       → B3 Hapus kursus (Admin)
 *  - GET    /api/courses                    → B4 Daftar kursus published (Public)
 *  - GET    /api/courses/{course_slug}       → B5 Detail kursus (Public)
 */
class CourseController extends Controller
{
    /**
     * B1 · Add Course
     *
     * Membuat kursus baru. Status is_published secara default adalah false,
     * artinya kursus tidak langsung tampil ke publik sebelum diterbitkan via B2.
     *
     * @route  POST /api/courses
     */

    public function adminIndex()
    {
        $courses = Course::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Courses retrieved successfully',
            'data' => [
                'courses' => $courses,
            ],
        ], 200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'slug' => 'required|unique:courses,slug', // Slug harus unik di seluruh tabel courses
        ], [
            'name.required' => 'The name field is required.',
            'slug.required' => 'The slug field is required.',
            'slug.unique' => 'The slug has already been taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid field(s) in request',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Kursus baru selalu dibuat dengan status tidak dipublikasikan (draft)
        $course = Course::create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug,
            'is_published' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Course successfully added',
            'data' => $course,
        ], 201);
    }

    /**
     * B2 · Edit Course
     *
     * Memperbarui data kursus berdasarkan slug.
     * Slug kursus tidak dapat diubah melalui endpoint ini.
     * Gunakan endpoint ini untuk mengubah nama, deskripsi, atau menerbitkan (is_published = true).
     *
     * @route  PUT /api/courses/{course_slug}
     * @access Admin only
     *
     * @urlParam string $course_slug  required  Slug kursus yang ingin diedit.
     *
     * @bodyParam string  $name          required  Nama kursus baru.
     * @bodyParam string  $description   nullable  Deskripsi baru; jika tidak dikirim, nilai lama dipertahankan.
     * @bodyParam boolean $is_published  nullable  true untuk menerbitkan, false untuk menarik dari publik.
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Course successfully updated",
     *   "data": { ...course }
     * }
     * @response 400 { "status": "error", "message": "Invalid field(s) in request", "errors": {} }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $course_slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $course_slug)
    {
        // Cari kursus berdasarkan slug; kembalikan 404 jika tidak ditemukan
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ], [
            'name.required' => 'The name field is required.',
            'is_published.boolean' => 'The is published field must be true or false.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid field(s) in request',
                'errors' => $validator->errors(),
            ], 400);
        }

        $course->update([
            'name' => $request->name,
            // Gunakan nilai lama jika field tidak dikirim dalam request (partial update)
            'description' => $request->description ?? $course->description,
            'is_published' => $request->is_published ?? $course->is_published,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Course successfully updated',
            'data' => $course,
        ], 200);
    }

    /**
     * B3 · Delete Course
     *
     * Menghapus kursus berdasarkan slug.
     * Pastikan relasi cascade sudah dikonfigurasi di migration agar
     * semua Set, Lesson, LessonContent, dan Option terkait ikut terhapus.
     *
     * @route  DELETE /api/courses/{course_slug}
     * @access Admin only
     *
     * @urlParam string $course_slug  required  Slug kursus yang akan dihapus.
     *
     * @response 200 { "status": "success", "message": "Course successfully deleted" }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  string  $course_slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($course_slug)
    {
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $course->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Course successfully deleted',
        ], 200);
    }

    /**
     * B4 · Get All Published Courses
     *
     * Mengembalikan semua kursus dengan status is_published = true.
     * Kursus yang masih draft (is_published = false) tidak akan muncul.
     *
     * @route  GET /api/courses
     * @access Public (tidak perlu autentikasi)
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Courses retrieved successfully",
     *   "data": {
     *     "courses": [ { "id", "name", "slug", "description", "is_published", ... }, ... ]
     *   }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Hanya tampilkan kursus yang sudah diterbitkan oleh admin
        $courses = Course::where('is_published', true)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Courses retrieved successfully',
            'data' => [
                'courses' => $courses,
            ],
        ], 200);
    }

    /**
     * B5 · Get Course Details
     *
     * Mengembalikan detail kursus beserta seluruh relasi nested:
     * sets → lessons → contents → options.
     *
     * Aturan penyembunyian data:
     *  - Konten bertipe "learn" → relasi `options` disembunyikan (tidak relevan)
     *  - Konten bertipe "quiz"  → kolom `is_correct` pada setiap opsi disembunyikan
     *    (agar jawaban tidak bocor ke client sebelum user menjawab)
     *
     * @route  GET /api/courses/{course_slug}
     * @access Public (tidak perlu autentikasi)
     *
     * @urlParam string $course_slug  required  Slug kursus yang ingin dilihat detailnya.
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Course details retrieved successfully",
     *   "data": {
     *     "id", "name", "slug", "description", "is_published",
     *     "sets": [
     *       {
     *         "id", "name", "order",
     *         "lessons": [
     *           {
     *             "id", "name", "order",
     *             "contents": [
     *               {
     *                 "id", "type", "content", "order",
     *                 "options": [ { "id", "option_text" } ]  // is_correct disembunyikan
     *               }
     *             ]
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  string  $course_slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($course_slug)
    {
        // Eager load semua relasi nested sekaligus untuk menghindari N+1 query
        $course = Course::where('slug', $course_slug)
            ->with([
                'sets' => function ($query) {
                    $query->orderBy('order'); // Urutkan set berdasarkan kolom order
                },
                'sets.lessons' => function ($query) {
                    $query->orderBy('order'); // Urutkan lesson dalam setiap set
                },
                'sets.lessons.contents' => function ($query) {
                    $query->orderBy('order'); // Urutkan konten dalam setiap lesson
                },
                'sets.lessons.contents.options', // Muat semua opsi jawaban
            ])
            ->first();

        if (!$course) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Sembunyikan data sensitif berdasarkan tipe konten
        $course->sets->each(function ($set) {
            $set->lessons->each(function ($lesson) {
                $lesson->contents->each(function ($content) {
                    if ($content->type === 'learn') {
                        // Konten materi tidak memiliki opsi → sembunyikan relasi options
                        $content->makeHidden('options');
                    } else {
                        // Konten quiz → sembunyikan is_correct agar jawaban tidak bocor
                        $content->options->each(function ($option) {
                            $option->makeHidden('is_correct');
                        });
                    }
                });
            });
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Course details retrieved successfully',
            'data' => $course,
        ], 200);
    }
}