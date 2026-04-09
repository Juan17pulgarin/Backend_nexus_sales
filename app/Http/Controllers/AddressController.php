<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddressController extends Controller
{
    public function getCustomerAddresses(Request $request, $id)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $customerExists = DB::table('SalesLT.Customer')
            ->where('CustomerID', $id)
            ->exists();

        if (!$customerExists) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $addresses = DB::table('SalesLT.CustomerAddress as ca')
            ->join('SalesLT.Address as a', 'ca.AddressID', '=', 'a.AddressID')
            ->select(
                'a.AddressID',
                'a.AddressLine1',
                'a.AddressLine2',
                'a.City',
                'a.StateProvince',
                'a.CountryRegion',
                'a.PostalCode',
                'a.rowguid',
                'a.ModifiedDate',
                'ca.AddressType'
            )
            ->where('ca.CustomerID', $id)
            ->orderBy('a.AddressID')
            ->get();

        return response()->json($addresses, 200);
    }

    public function store(StoreAddressRequest $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $validated = $request->validated();

        $customerExists = DB::table('SalesLT.Customer')
            ->where('CustomerID', $validated['CustomerID'])
            ->exists();

        if (!$customerExists) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        try {
            $result = DB::transaction(function () use ($validated) {
                $addressRowguid = (string) Str::uuid();

                DB::table('SalesLT.Address')->insert([
                    'AddressLine1'  => $validated['AddressLine1'],
                    'AddressLine2'  => $validated['AddressLine2'] ?? null,
                    'City'          => $validated['City'],
                    'StateProvince' => $validated['StateProvince'],
                    'CountryRegion' => $validated['CountryRegion'],
                    'PostalCode'    => $validated['PostalCode'],
                    'rowguid'       => $addressRowguid,
                    'ModifiedDate'  => now(),
                ]);

                $address = DB::table('SalesLT.Address')
                    ->select('AddressID')
                    ->where('rowguid', $addressRowguid)
                    ->first();

                if (!$address) {
                    throw new \RuntimeException('No se pudo recuperar la dirección insertada');
                }

                DB::table('SalesLT.CustomerAddress')->insert([
                    'CustomerID'   => $validated['CustomerID'],
                    'AddressID'    => $address->AddressID,
                    'AddressType'  => $validated['AddressType'] ?? 'Main Office',
                    'rowguid'      => (string) Str::uuid(),
                    'ModifiedDate' => now(),
                ]);

                return $address->AddressID;
            });

            return response()->json([
                'message' => 'Dirección registrada correctamente',
                'AddressID' => $result,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al registrar la dirección',
                'error' => 'No se pudo guardar la dirección en la base de datos',
            ], 500);
        }
    }

    public function update(UpdateAddressRequest $request, $id)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $validated = $request->validated();

        $addressExists = DB::table('SalesLT.Address')
            ->where('AddressID', $id)
            ->exists();

        if (!$addressExists) {
            return response()->json([
                'message' => 'Dirección no encontrada'
            ], 404);
        }

        $payload = [
            'AddressLine1'  => $validated['AddressLine1'],
            'City'          => $validated['City'],
            'StateProvince' => $validated['StateProvince'],
            'CountryRegion' => $validated['CountryRegion'],
            'PostalCode'    => $validated['PostalCode'],
            'ModifiedDate'  => now(),
        ];

        if (array_key_exists('AddressLine2', $validated)) {
            $payload['AddressLine2'] = $validated['AddressLine2'];
        }

        DB::table('SalesLT.Address')
            ->where('AddressID', $id)
            ->update($payload);

        return response()->json([
            'message' => 'Dirección actualizada correctamente'
        ], 200);
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
