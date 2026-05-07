<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * AuthController
 *
 * Menangani autentikasi pengguna dan administrator.
 * Menggunakan Laravel Sanctum untuk manajemen token Bearer.
 *
 * Endpoint yang tersedia:
 *  - POST /api/register  → Registrasi user baru
 *  - POST /api/login     → Login user atau admin
 *  - POST /api/logout    → Logout (hapus token aktif)
 */
class AuthController extends Controller
{
    /**
     * A1 · Register User
     *
     * Mendaftarkan pengguna baru ke tabel `users`.
     * Setelah berhasil, langsung mengembalikan token Sanctum.
     *
     * @route  POST /api/register
     * @access Public (tidak perlu autentikasi)
     *
     * @bodyParam string $full_name  required  Nama lengkap pengguna.
     * @bodyParam string $username   required  Min 3 karakter, unik, hanya huruf/angka/titik/underscore.
     * @bodyParam string $password   required  Minimal 6 karakter.
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "User registration successful",
     *   "data": {
     *     "id": 1,
     *     "full_name": "John Doe",
     *     "username": "johndoe",
     *     "created_at": "...",
     *     "updated_at": "...",
     *     "token": "<sanctum_token>",
     *     "role": "user"
     *   }
     * }
     * @response 400 { "status": "error", "message": "Invalid field(s) in request", "errors": {} }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validasi input dari request
        $validator = Validator::make(
            $request->all(),
            [
                'full_name' => 'required|string',
                'username' => [
                    'required',
                    'min:3',
                    'unique:users,username',         // Username harus unik di tabel users
                    'regex:/^[a-zA-Z0-9._]+$/',     // Hanya huruf, angka, titik, underscore
                ],
                'password' => 'required|min:6',    // Minimal 6 karakter
            ],
            [
                // Pesan validasi custom agar lebih informatif
                'username.regex' => 'The username may only contain letters, numbers, dots, and underscores.',
                'username.unique' => 'The username has already been taken.',
            ]
        );

        // Jika validasi gagal, kembalikan error 400
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid field(s) in request',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Buat user baru; password di-hash sebelum disimpan
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'password' => Hash::make($request->password), // Tidak pernah simpan plain-text
        ]);

        // Buat token Sanctum untuk sesi login pertama
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registration successful',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'token' => $token,
                'role' => 'user', // Registrasi selalu menghasilkan role "user"
            ],
        ], 201);
    }

    /**
     * A2 · Login
     *
     * Mengautentikasi username dan password.
     * Sistem memeriksa tabel `administrators` terlebih dahulu,
     * baru kemudian tabel `users`. Jika keduanya tidak cocok,
     * mengembalikan status authentication_failed.
     *
     * @route  POST /api/login
     * @access Public (tidak perlu autentikasi)
     *
     * @bodyParam string $username  required  Username akun.
     * @bodyParam string $password  required  Password akun.
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Login successful",
     *   "data": {
     *     "id": 1,
     *     "username": "johndoe",
     *     "created_at": "...",
     *     "updated_at": "...",
     *     "token": "<sanctum_token>",
     *     "role": "admin" | "user"
     *   }
     * }
     * @response 400 { "status": "authentication_failed", "message": "The username or password you entered is incorrect" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Pastikan username dan password dikirim
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid field(s) in request',
                'errors' => $validator->errors(),
            ], 400);
        }

        // ── Langkah 1: Cek di tabel administrators ──────────────────
        // Admin dicek lebih dulu; jika cocok, langsung return dengan role "admin"
        $admin = Administrator::where('username', $request->username)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'created_at' => $admin->created_at,
                    'updated_at' => $admin->updated_at,
                    'token' => $token,
                    'role' => 'admin',
                ],
            ], 200);
        }

        // ── Langkah 2: Cek di tabel users ───────────────────────────
        // Jika bukan admin, coba cocokkan dengan user biasa
        $user = User::where('username', $request->username)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'token' => $token,
                    'role' => 'user',
                ],
            ], 200);
        }

        // ── Langkah 3: Tidak ada yang cocok ─────────────────────────
        // Kembalikan error autentikasi; jangan beri tahu field mana yang salah
        // (mencegah user enumeration attack)
        return response()->json([
            'status' => 'authentication_failed',
            'message' => 'The username or password you entered is incorrect',
        ], 400);
    }

    /**
     * A3 · Logout
     *
     * Menghapus token akses yang sedang digunakan (currentAccessToken).
     * Hanya token aktif yang dihapus; token lain (multi-device) tidak terpengaruh.
     *
     * @route  POST /api/logout
     * @access Auth required (Bearer Token)
     *
     * @response 200 { "status": "success", "message": "Logout successful" }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Hapus hanya token yang dipakai pada request ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful',
        ], 200);
    }
}