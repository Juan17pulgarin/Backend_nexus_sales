<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('AppUsers')
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario o contraseña inválidos'], 401);
        }

        // Compara contra PasswordHash usando PasswordSalt (sin texto plano)
        $hash = hash('sha256', $request->password . $user->password_salt);

        if ($hash !== $user->password_hash) {
            return response()->json(['message' => 'Usuario o contraseña inválidos'], 401);
        }

        // Token simple para esta historia
        $token = Str::random(60);

        // Guardar en cache (BD) por 2 horas
        Cache::put('auth_token:' . $token, $user->email, now()->addHours(2));

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
        ], 200);
    }
}
