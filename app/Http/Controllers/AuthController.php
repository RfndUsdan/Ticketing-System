<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterStoreRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller {
    
    public function login(Request $request)
    {
        try {
            if (!Auth::guard('web')->attempt($request->only('email','password'))) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $token = $user->createToken('auth_Token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user)
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            return response()->json([
                'message' => 'Profile User berhasil diambil',
                'data' => new UserResource($user)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            /** @var \Laravel\Sanctum\PersonalAccessToken $token */
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Logout berhasil',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function register(RegisterStoreRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);

            $user->nim = $data['nim'] ?? null;


            if ($request->hasFile('avatar')) {
                // Simpan foto ke folder 'storage/app/public/avatars'
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }
            $user->save();

            $token = $user->createToken('auth_Token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user)
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'nim' => 'required|string|max:20|unique:users,nim,' . $user->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->nim = $validated['nim'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'data' => new UserResource($user)
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan saat update profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function deleteAvatar()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
                
                $user->avatar = null;
                $user->save();
            }

            return response()->json([
                'message' => 'Foto profil berhasil dihapus',
                'data' => new UserResource($user)
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan saat menghapus foto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}