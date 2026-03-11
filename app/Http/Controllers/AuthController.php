<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('dbo.users')
            ->where('email', trim($request->email))
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 401);
        }

        $hash = hash('sha256', $request->password);

        if ($hash !== trim($user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta'], 401);
        }

        $token = Str::random(60);
        Cache::put('auth_token:' . $token, $user->email, now()->addHours(2));

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
        ], 200);
    }
}
