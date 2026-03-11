<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClienteController extends Controller
{
    public function clientes(Request $request)
    {
        try {
            // Consultamos la tabla
            $query = DB::table('SalesLT.Customer')
                ->select('CustomerID', 'FirstName', 'LastName', 'EmailAddress', 'CompanyName', 'Phone')
                ->orderBy('CustomerID', 'desc');

            // SI el usuario NO pide "todo", limitamos a 10 para no saturar
            if (!$request->has('todo')) {
                $query->limit(10);
            }

            $clientes = $query->get();

            return response()->json($clientes, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Usamos insert() en lugar de insertGetId() para evitar conflictos con Triggers de SQL Server
            DB::table('SalesLT.Customer')->insert([
                'NameStyle'    => 0,
                'Title'        => $request->Title ?? 'Mr.',
                'FirstName'    => $request->FirstName,
                'MiddleName'   => $request->MiddleName ?? null,
                'LastName'     => $request->LastName,
                'Suffix'       => $request->Suffix ?? null,
                'CompanyName'  => $request->CompanyName,
                'SalesPerson'  => $request->SalesPerson ?? 'adventure-works\\alex0',
                'EmailAddress' => $request->EmailAddress,
                'Phone'        => $request->Phone,
                'PasswordHash' => bcrypt('password123'), 
                'PasswordSalt' => Str::random(10),       
                'rowguid'      => Str::uuid(),           
                'ModifiedDate' => now(),                 
            ]);

            return response()->json(['message' => 'Cliente creado correctamente'], 201);
        } catch (\Exception $e) {
            // Este return es clave: te dirá en el Preview de la consola de Chrome el error EXACTO
            return response()->json([
                'error' => 'Error en SQL Server',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $updated = DB::table('SalesLT.Customer')
                ->where('CustomerID', $id)
                ->update([
                    'Title'        => $request->Title,
                    'FirstName'    => $request->FirstName,
                    'LastName'     => $request->LastName,
                    'CompanyName'  => $request->CompanyName,
                    'EmailAddress' => $request->EmailAddress,
                    'Phone'        => $request->Phone,
                    'ModifiedDate' => now(),
                ]);

            if ($updated === 0) {
                return response()->json(['message' => 'No se encontró el cliente o no hubo cambios'], 404);
            }

            return response()->json(['message' => 'Cliente actualizado con éxito']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
{
    try {
        // Buscamos si existe
        $existe = DB::table('SalesLT.Customer')->where('CustomerID', $id)->exists();
        
        if (!$existe) {
            return response()->json(['error' => 'El cliente no existe'], 404);
        }

        // Intentamos eliminar
        DB::table('SalesLT.Customer')->where('CustomerID', $id)->delete();

        return response()->json(['message' => 'Cliente eliminado correctamente']);
    } catch (\Exception $e) {
        // SQL Server lanzará error si el cliente tiene pedidos (SalesOrderHeader)
        return response()->json([
            'error' => 'No se puede eliminar el cliente',
            'details' => 'Es probable que este cliente tenga pedidos asociados en la base de datos.'
        ], 409);
    }
}
    
}

