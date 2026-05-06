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

    public function clientes(Request $request)
    {
        $auth = $request->header('Authorization', '');

        // Espera: Authorization: Bearer <token>
        if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $m)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $token = $m[1];

        if (!Cache::has('auth_token:' . $token)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        // Build query joining customers with customer_addresses
        $qb = DB::table('customers')
            ->leftJoin('customer_addresses', 'customers.id', '=', 'customer_addresses.customer_id')
            ->select('customers.id as id', 'customers.first_name', 'customers.last_name', 'customers.email')
            ->distinct()
            ->orderBy('customers.id');

        if ($request->filled('city')) {
            $qb->where('customer_addresses.city', $request->query('city'));
        }

        if ($request->filled('state')) {
            $qb->where('customer_addresses.state', $request->query('state'));
        }

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 10));
        $offset = ($page - 1) * $limit;

        // total distinct customers matching filters
        $totalQ = DB::table('customers')
            ->leftJoin('customer_addresses', 'customers.id', '=', 'customer_addresses.customer_id');

        if ($request->filled('city')) {
            $totalQ->where('customer_addresses.city', $request->query('city'));
        }

        if ($request->filled('state')) {
            $totalQ->where('customer_addresses.state', $request->query('state'));
        }

        $total = $totalQ->distinct()->count('customers.id');

        $clientes = $qb->offset($offset)->limit($limit)->get();

        return response()->json([
            'data' => $clientes,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ], 200);
    }
}
