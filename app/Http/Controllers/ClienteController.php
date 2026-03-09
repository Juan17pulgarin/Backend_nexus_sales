<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $clientes = DB::table('SalesLT.Customer')
            ->select('CustomerID', 'FirstName', 'LastName', 'EmailAddress')
            ->orderBy('CustomerID')
            ->take(20)
            ->get();

        return response()->json($clientes, 200);
    }

    public function store(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $validated = $request->validate([
            'FirstName' => 'required|string|max:50',
            'LastName' => 'required|string|max:50',
            'EmailAddress' => 'required|email|max:50',
        ]);

        $payload = [
            'NameStyle' => 0,
            'FirstName' => $validated['FirstName'],
            'LastName' => $validated['LastName'],
            'EmailAddress' => $validated['EmailAddress'],
            'PasswordSalt' => Str::random(10),
            'rowguid' => (string) Str::uuid(),
            'ModifiedDate' => now(),
        ];

        // AdventureWorks requires non-null PasswordHash and PasswordSalt.
        $payload['PasswordHash'] = hash('sha256', $validated['EmailAddress'] . $payload['PasswordSalt']);

        try {
            $inserted = DB::table('SalesLT.Customer')->insert($payload);
        } catch (\Throwable $exception) {
            $errorId = (string) Str::uuid();

            Log::error('Error al registrar cliente en SalesLT.Customer', [
                'error_id' => $errorId,
                'connection' => DB::getDefaultConnection(),
                'table' => 'SalesLT.Customer',
                'payload' => [
                    'FirstName' => $payload['FirstName'],
                    'LastName' => $payload['LastName'],
                    'EmailAddress' => $payload['EmailAddress'],
                    'rowguid' => $payload['rowguid'],
                    'ModifiedDate' => (string) $payload['ModifiedDate'],
                ],
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
            ]);

            return response()->json([
                'message' => 'Error al registrar cliente',
                'error' => 'No se pudo guardar el cliente en la base de datos',
                'error_id' => $errorId,
            ], 500);
        }

        if (!$inserted) {
            $errorId = (string) Str::uuid();

            Log::error('Insert devolvio false al registrar cliente en SalesLT.Customer', [
                'error_id' => $errorId,
                'connection' => DB::getDefaultConnection(),
                'table' => 'SalesLT.Customer',
                'payload' => [
                    'FirstName' => $payload['FirstName'],
                    'LastName' => $payload['LastName'],
                    'EmailAddress' => $payload['EmailAddress'],
                    'rowguid' => $payload['rowguid'],
                    'ModifiedDate' => (string) $payload['ModifiedDate'],
                ],
            ]);

            return response()->json([
                'message' => 'Error al registrar cliente',
                'error' => 'No se pudo guardar el cliente en la base de datos',
                'error_id' => $errorId,
            ], 500);
        }

        return response()->json([
            'message' => 'Cliente registrado correctamente',
        ], 201);
    }

    private function isAuthorized(Request $request): bool
    {
        $auth = $request->header('Authorization', '');

        if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $matches)) {
            return false;
        }

        $token = $matches[1];

        return Cache::has('auth_token:' . $token);
    }
}
