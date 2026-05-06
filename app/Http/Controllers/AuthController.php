<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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

    public function store(Request $request)
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
        ]);

        $nameParts = preg_split('/\s+/', trim($validated['name']));
        $firstName = array_shift($nameParts) ?? $validated['name'];
        $inferredLastName = count($nameParts) > 0 ? implode(' ', $nameParts) : null;

        $customer = Customer::create([
            'first_name' => $firstName,
            'last_name' => $validated['last_name'] ?? $inferredLastName,
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Cliente creado correctamente',
            'data' => $customer,
        ], 201);
    }
}
