<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SetController
 *
 * Mengelola Set (kelompok lesson) di dalam sebuah kursus.
 * Set diurutkan menggunakan kolom `order` yang di-generate otomatis.
 * Hanya administrator yang dapat menambah dan menghapus set.
 *
 * Endpoint yang tersedia:
 *  - POST   /api/courses/{course_slug}/sets/{set_id}  → C1 Tambah set (Admin)
 *  - DELETE /api/courses/{course_slug}/sets/{set_id}  → C2 Hapus set (Admin)
 */
class SetController extends Controller
{
    /**
     * C1 · Add Set
     *
     * Menambahkan set baru ke dalam kursus yang ditentukan via slug.
     * Nilai `order` di-generate otomatis: max(order) + 1 di dalam kursus tersebut.
     * Set pertama di sebuah kursus akan mendapat order = 0.
     *
     * @route  POST /api/courses/{course_slug}/sets
     * @access Admin only
     *
     * @urlParam string $course_slug  required  Slug kursus tujuan.
     *
     * @bodyParam string $name  required  Nama set (contoh: "Bab 1 - Pengenalan").
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "Set successfully added",
     *   "data": { "id": 1, "name": "Bab 1", "order": 0 }
     * }
     * @response 400 { "status": "error", "message": "Invalid field(s) in request", "errors": {} }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $course_slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $course_slug)
    {
        // Validasi bahwa kursus dengan slug tersebut ada
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Hitung order berikutnya: ambil nilai order terbesar di kursus ini, lalu +1
        // Jika belum ada set, mulai dari 0
        $lastOrder = Set::where('course_id', $course->id)->max('order');
        $newOrder  = $lastOrder !== null ? $lastOrder + 1 : 0;

        $set = Set::create([
            'name'      => $request->name,
            'course_id' => $course->id,
            'order'     => $newOrder,
        ]);

        // Hanya kembalikan field yang relevan (tidak perlu seluruh model)
        return response()->json([
            'status'  => 'success',
            'message' => 'Set successfully added',
            'data'    => [
                'id'    => $set->id,
                'name'  => $set->name,
                'order' => $set->order,
            ],
        ], 201);
    }

    /**
     * C2 · Delete Set
     *
     * Menghapus set berdasarkan ID dengan validasi kepemilikan:
     * set harus benar-benar milik kursus yang ditentukan via slug.
     * Ini mencegah penghapusan set dari kursus yang berbeda menggunakan ID yang sama.
     *
     * @route  DELETE /api/courses/{course_slug}/sets/{set_id}
     * @access Admin only
     *
     * @urlParam string  $course_slug  required  Slug kursus pemilik set.
     * @urlParam integer $set_id       required  ID set yang akan dihapus.
     *
     * @response 200 { "status": "success", "message": "Set successfully deleted" }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  string   $course_slug
     * @param  integer  $set_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($course_slug, $set_id)
    {
        // Langkah 1: Pastikan kursus dengan slug ini ada
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Langkah 2: Pastikan set dengan ID ini memang milik kursus di atas
        // (double check: id AND course_id) → mencegah manipulasi antar kursus
        $set = Set::where('id', $set_id)
                   ->where('course_id', $course->id)
                   ->first();

        if (!$set) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $set->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Set successfully deleted',
        ], 200);
    }
}