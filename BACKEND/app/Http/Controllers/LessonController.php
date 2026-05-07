<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\Option;
use App\Models\Set;
use App\Models\CompletedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * LessonController
 *
 * Mengelola lesson di dalam set kursus, termasuk konten belajar dan kuis.
 * Mendukung dua tipe konten:
 *  - "learn" → materi pembelajaran (teks/HTML)
 *  - "quiz"  → pertanyaan pilihan ganda dengan opsi jawaban
 *
 * Endpoint yang tersedia:
 *  - POST /api/lessons                                              → D1 Tambah lesson (Admin)
 *  - DELETE /api/lessons/{lesson_id}                               → D2 Hapus lesson (Admin)
 *  - POST /api/lessons/{lesson_id}/contents/{content_id}/check     → D3 Cek jawaban (User)
 *  - POST /api/lessons/{lesson_id}/complete                        → D4 Tandai selesai (User)
 */
class LessonController extends Controller
{
    /**
     * D1 · Add Lesson
     *
     * Membuat lesson baru beserta konten di dalamnya (opsional).
     * Konten dikirim sebagai array; urutan (order) mengikuti urutan index array.
     * Untuk konten bertipe "quiz", opsi jawaban bisa langsung disertakan.
     * Order lesson di-generate otomatis per set (max order + 1, mulai dari 0).
     *
     * @route  POST /api/lessons
     * @access Admin only
     *
     * @bodyParam string   $name                               required  Nama lesson.
     * @bodyParam integer  $set_id                             required  ID set tujuan (harus ada).
     * @bodyParam array    $contents                           nullable  Daftar konten lesson.
     * @bodyParam string   $contents.*.type                   required  "learn" atau "quiz".
     * @bodyParam string   $contents.*.content                required  Teks materi atau pertanyaan.
     * @bodyParam array    $contents.*.options                nullable  Opsi jawaban (hanya untuk quiz).
     * @bodyParam string   $contents.*.options.*.option_text  required  Teks pilihan jawaban.
     * @bodyParam boolean  $contents.*.options.*.is_correct   required  true jika jawaban benar.
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "Lesson successfully added",
     *   "data": { "id": 1, "name": "Pengenalan PHP", "order": 0 }
     * }
     * @response 400 { "status": "error", "message": "Invalid field(s) in request", "errors": {} }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                             => 'required|string',
            'set_id'                           => 'required|exists:sets,id', // Set harus benar-benar ada
            'contents'                         => 'nullable|array',
            'contents.*.type'                  => 'required_with:contents|in:learn,quiz', // Hanya dua tipe yang valid
            'contents.*.content'               => 'required_with:contents|string',
            'contents.*.options'               => 'nullable|array',
            'contents.*.options.*.option_text' => 'required_with:contents.*.options|string',
            'contents.*.options.*.is_correct'  => 'required_with:contents.*.options|boolean',
        ], [
            'name.required'   => 'The name field is required.',
            'set_id.required' => 'The set id field is required.',
            'set_id.exists'   => 'The selected set id is invalid.',
            'contents.array'  => 'The contents field must be an array.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Hitung order berikutnya dalam set yang sama; mulai dari 0 jika set masih kosong
        $lastOrder = Lesson::where('set_id', $request->set_id)->max('order');
        $newOrder  = $lastOrder !== null ? $lastOrder + 1 : 0;

        // Buat record lesson terlebih dahulu
        $lesson = Lesson::create([
            'name'   => $request->name,
            'set_id' => $request->set_id,
            'order'  => $newOrder,
        ]);

        // Simpan konten jika ada; order konten mengikuti urutan index array
        if ($request->has('contents') && is_array($request->contents)) {
            foreach ($request->contents as $index => $contentData) {

                // Buat record LessonContent untuk setiap item konten
                $content = LessonContent::create([
                    'lesson_id' => $lesson->id,
                    'type'      => $contentData['type'],
                    'content'   => $contentData['content'],
                    'order'     => $index, // Index array dijadikan order konten
                ]);

                // Opsi jawaban hanya disimpan untuk konten bertipe "quiz"
                if ($contentData['type'] === 'quiz' && isset($contentData['options'])) {
                    foreach ($contentData['options'] as $optionData) {
                        Option::create([
                            'lesson_content_id' => $content->id,
                            'option_text'       => $optionData['option_text'],
                            'is_correct'        => $optionData['is_correct'],
                        ]);
                    }
                }
            }
        }

        // Kembalikan hanya data minimal lesson (bukan seluruh konten)
        return response()->json([
            'status'  => 'success',
            'message' => 'Lesson successfully added',
            'data'    => [
                'id'    => $lesson->id,
                'name'  => $lesson->name,
                'order' => $lesson->order,
            ],
        ], 201);
    }

    /**
     * D2 · Delete Lesson
     *
     * Menghapus lesson berdasarkan ID.
     * Pastikan cascade delete sudah dikonfigurasi di migration agar
     * semua LessonContent dan Option terkait ikut terhapus otomatis.
     *
     * @route  DELETE /api/lessons/{lesson_id}
     * @access Admin only
     *
     * @urlParam integer $lesson_id  required  ID lesson yang akan dihapus.
     *
     * @response 200 { "status": "success", "message": "Lesson successfully deleted" }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  integer  $lesson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($lesson_id)
    {
        $lesson = Lesson::find($lesson_id);

        if (!$lesson) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $lesson->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lesson successfully deleted',
        ], 200);
    }

    /**
     * D3 · Check Answer
     *
     * Memeriksa apakah opsi yang dipilih user adalah jawaban yang benar.
     * Endpoint ini bersifat stateless: hasil tidak disimpan ke database,
     * hanya mengembalikan apakah jawaban benar atau salah.
     *
     * @route  POST /api/lessons/{lesson_id}/contents/{content_id}/check
     * @access User only (Auth required)
     *
     * @urlParam integer $lesson_id   required  ID lesson pemilik konten.
     * @urlParam integer $content_id  required  ID konten bertipe quiz.
     *
     * @bodyParam integer $option_id  required  ID opsi yang dipilih user (harus ada di tabel options).
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Check answer success",
     *   "data": {
     *     "question": "Apa itu Laravel?",
     *     "user_answer": "PHP Framework",
     *     "is_correct": true
     *   }
     * }
     * @response 400 { "status": "error", "message": "Only for quiz content" }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer                   $lesson_id
     * @param  integer                   $content_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAnswer(Request $request, $lesson_id, $content_id)
    {
        $lesson  = Lesson::find($lesson_id);
        $content = LessonContent::find($content_id);

        // Pastikan keduanya (lesson dan content) ditemukan
        if (!$lesson || !$content) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Endpoint ini hanya berlaku untuk konten bertipe "quiz"
        // Konten "learn" tidak memiliki jawaban benar/salah
        if ($content->type !== 'quiz') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only for quiz content',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'option_id' => 'required|exists:options,id', // Opsi harus ada di database
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Ambil opsi yang dipilih dan kembalikan hasilnya
        $option = Option::find($request->option_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check answer success',
            'data'    => [
                'question'    => $content->content,        // Teks pertanyaan
                'user_answer' => $option->option_text,     // Teks opsi yang dipilih user
                'is_correct'  => (bool) $option->is_correct, // Cast ke boolean eksplisit
            ],
        ], 200);
    }

    /**
     * D4 · Complete Lesson
     *
     * Menandai lesson sebagai selesai untuk pengguna yang sedang login.
     * Bersifat idempotent: jika lesson sudah pernah diselesaikan sebelumnya,
     * tidak akan membuat record duplikat di tabel `completed_lessons`.
     *
     * @route  POST /api/lessons/{lesson_id}/complete
     * @access User only (Auth required)
     *
     * @urlParam integer $lesson_id  required  ID lesson yang ingin ditandai selesai.
     *
     * @response 200 { "status": "success", "message": "Lesson successfully completed" }
     * @response 404 { "status": "not_found", "message": "Resource not found" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  integer                   $lesson_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request, $lesson_id)
    {
        $lesson = Lesson::find($lesson_id);

        if (!$lesson) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cek apakah user sudah pernah menyelesaikan lesson ini sebelumnya
        $already = CompletedLesson::where('user_id', $request->user()->id)
                                   ->where('lesson_id', $lesson_id)
                                   ->first();

        // Hanya buat record baru jika belum ada (hindari duplikat)
        if (!$already) {
            CompletedLesson::create([
                'user_id'   => $request->user()->id,
                'lesson_id' => $lesson_id,
            ]);
        }

        // Response sama baik sudah selesai sebelumnya maupun baru saja diselesaikan
        return response()->json([
            'status'  => 'success',
            'message' => 'Lesson successfully completed',
        ], 200);
    }
}