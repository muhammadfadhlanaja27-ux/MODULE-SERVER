<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    // B1: Add Course (Admin only)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'slug'        => 'required|unique:courses,slug',
        ], [
            // Custom message sesuai kisi-kisi
            'name.required' => 'The name field is required.',
            'slug.required' => 'The slug field is required.',
            'slug.unique'   => 'The slug has already been taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $course = Course::create([
            'name'         => $request->name,
            'description'  => $request->description,
            'slug'         => $request->slug,
            'is_published' => false,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Course successfully added',
            'data'    => $course,
        ], 201);
    }

    // B2: Edit Course (Admin only)
    public function update(Request $request, $course_slug)
    {
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string',
            'description'  => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ], [
            // Custom message sesuai kisi-kisi
            'name.required'     => 'The name field is required.',
            'is_published.boolean' => 'The is published field must be true or false.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $course->update([
            'name'         => $request->name,
            'description'  => $request->description ?? $course->description,
            'is_published' => $request->is_published ?? $course->is_published,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Course successfully updated',
            'data'    => $course,
        ], 200);
    }

    // B3: Delete Course (Admin only)
    public function destroy($course_slug)
    {
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        $course->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Course successfully deleted',
        ], 200);
    }

    // B4: Get All Published Courses
    public function index()
    {
        $courses = Course::where('is_published', true)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Courses retrieved successfully',
            'data'    => [
                'courses' => $courses,
            ],
        ], 200);
    }

    // B5: Get Course Details
    public function show($course_slug)
    {
        $course = Course::where('slug', $course_slug)
            ->with([
                'sets' => function ($query) {
                    $query->orderBy('order');
                },
                'sets.lessons' => function ($query) {
                    $query->orderBy('order');
                },
                'sets.lessons.contents' => function ($query) {
                    $query->orderBy('order');
                },
                'sets.lessons.contents.options',
            ])
            ->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Format contents
        $course->sets->each(function ($set) {
            $set->lessons->each(function ($lesson) {
                $lesson->contents->each(function ($content) {
                    if ($content->type === 'learn') {
                        $content->makeHidden('options');
                    } else {
                        $content->options->each(function ($option) {
                            $option->makeHidden('is_correct');
                        });
                    }
                });
            });
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Course details retrieved successfully',
            'data'    => $course,
        ], 200);
    }
}