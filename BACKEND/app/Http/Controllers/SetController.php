<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Set;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SetController extends Controller
{
    // C1: Add Set (Admin only)
    public function store(Request $request, $course_slug)
    {
        // Cari course berdasarkan slug
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

        // Auto increment order
        $lastOrder = Set::where('course_id', $course->id)->max('order');
        $newOrder  = $lastOrder !== null ? $lastOrder + 1 : 0;

        $set = Set::create([
            'name'      => $request->name,
            'course_id' => $course->id,
            'order'     => $newOrder,
        ]);

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

    // C2: Delete Set (Admin only)
    public function destroy($course_slug, $set_id)
    {
        // Cari course berdasarkan slug
        $course = Course::where('slug', $course_slug)->first();

        if (!$course) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Resource not found',
            ], 404);
        }

        // Cari set berdasarkan id dan course_id
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