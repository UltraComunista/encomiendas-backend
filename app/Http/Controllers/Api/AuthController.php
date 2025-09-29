<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Opcional: crea token en registro (puedes omitir si prefieres forzar login)
        $expiresAt = now()->addDays(30);
        $token = $user->createToken('api', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ], 201)->cookie('access_token', $token, 60 * 24 * 30, '/', null, false, true, false, 'Lax');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // ✅ Estrategia A: UNA SOLA SESIÓN por usuario
        $user->tokens()->delete();

        $expiresAt = now()->addDays(30);
        $token = $user->createToken('api', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ])->cookie('access_token', $token, 60 * 24 * 30, '/', null, false, true, false, 'Lax');
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        // Revoca solo el token actual (este dispositivo)
        $request->user()->currentAccessToken()?->delete();

        // Si estás usando la cookie HttpOnly, la limpiamos
        return response()->json(['message' => 'Sesión cerrada'])
            ->withoutCookie('access_token');
    }

    // (Opcional) cerrar sesión en todos los dispositivos
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesiones cerradas en todos los dispositivos'])
            ->withoutCookie('access_token');
    }
}
