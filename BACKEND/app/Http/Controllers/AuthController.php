<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'username'  => [
                'required',
                'min:3',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9._]+$/',
            ],
            'password'  => 'required|min:6',
        ], [
            'username.regex'  => 'The username may only contain letters, numbers, dots, and underscores.',
            'username.unique' => 'The username has already been taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'User registration successful',
            'data'    => [
                'id'         => $user->id,
                'full_name'  => $user->full_name,
                'username'   => $user->username,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'token'      => $token,
                'role'       => 'user',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid field(s) in request',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Cek di tabel administrators dulu
        $admin = Administrator::where('username', $request->username)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'id'         => $admin->id,
                    'username'   => $admin->username,
                    'created_at' => $admin->created_at,
                    'updated_at' => $admin->updated_at,
                    'token'      => $token,
                    'role'       => 'admin',
                ],
            ], 200);
        }

        // Cek di tabel users
        $user = User::where('username', $request->username)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'data'    => [
                    'id'         => $user->id,
                    'username'   => $user->username,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'token'      => $token,
                    'role'       => 'user',
                ],
            ], 200);
        }

        // Jika tidak ada yang cocok
        return response()->json([
            'status'  => 'authentication_failed',
            'message' => 'The username or password you entered is incorrect',
        ], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout successful',
        ], 200);
    }
}