<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonContent;
use App\Models\Option;
use App\Models\Set;
use App\Models\CompletedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    // D1: Add Lesson (Admin only)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                          => 'required|string',
            'set_id'                        => 'required|exists:sets,id',
            'contents'                      => 'nullable|array',
            'contents.*.type'               => 'required_with:contents|in:learn,quiz',
            'contents.*.content'            => 'required_with:contents|string',
            'contents.*.options'            => 'nullable|array',
            'contents.*.options.*.option_text' => 'required_with:contents.*.options|string',
            'contents.*.options.*.is_correct'  => 'required_with:contents.*.options|boolean',
        ], [
            'name.required'    => 'The name field is required.',
            'set_id.required'  => 'The set id field is required.',
            'set_id.exists'    => 'The selected set id is invalid.',
            'contents.array'   => 'The contents field must be an array.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Auto increment order
        $lastOrder = Lesson::where('set_id', $request->set_id)->max('order');
        $newOrder  = $lastOrder !== null ? $lastOrder + 1 : 0;

        // Buat lesson
        $lesson = Lesson::create([
            'name'   => $request->name,
            'set_id' => $request->set_id,
            'order'  => $newOrder,
        ]);

        // Simpan contents jika ada
        if ($request->has('contents') && is_array($request->contents)) {
            foreach ($request->contents as $index => $contentData) {
                $content = LessonContent::create([
                    'lesson_id' => $lesson->id,
                    'type'      => $contentData['type'],
                    'content'   => $contentData['content'],
                    'order'     => $index,
                ]);

                // Simpan options jika type quiz
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

    // D2: Delete Lesson (Admin only)
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

    // D3: Check Answer (User only)
    public function checkAnswer(Request $request, $lesson_id, $content_id)
    {
        $lesson  = Lesson::find($lesson_id);
        $content = LessonContent::find($content_id);

        if (!$lesson || !$content) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cek apakah content bertipe quiz
        if ($content->type !== 'quiz') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only for quiz content',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'option_id' => 'required|exists:options,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $option = Option::find($request->option_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check answer success',
            'data'    => [
                'question'    => $content->content,
                'user_answer' => $option->option_text,
                'is_correct'  => (bool) $option->is_correct,
            ],
        ], 200);
    }

    // D4: Complete Lesson (User only)
    public function complete(Request $request, $lesson_id)
    {
        $lesson = Lesson::find($lesson_id);

        if (!$lesson) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cek apakah sudah pernah complete
        $already = CompletedLesson::where('user_id', $request->user()->id)
                                   ->where('lesson_id', $lesson_id)
                                   ->first();

        if (!$already) {
            CompletedLesson::create([
                'user_id'   => $request->user()->id,
                'lesson_id' => $lesson_id,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Lesson successfully completed',
        ], 200);
    }
}