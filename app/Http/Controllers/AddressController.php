<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AddressController extends Controller
{
    private function authenticate(Request $request)
    {
        $auth = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $m)) return false;
        return Cache::has('auth_token:' . $m[1]);
    }

    public function index(Request $request, $customerId)
    {
        if (!$this->authenticate($request))
            return response()->json(['message' => 'No autorizado'], 401);

        $addresses = DB::table('customer_addresses')
            ->where('customer_id', $customerId)
            ->orderBy('id')
            ->get();

        return response()->json($addresses, 200);
    }

    public function store(Request $request, $customerId)
    {
        if (!$this->authenticate($request))
            return response()->json(['message' => 'No autorizado'], 401);

        $data = $request->validate([
            'line'    => 'required|string',
            'city'    => 'required|string',
            'state'   => 'required|string',
            'country' => 'required|string',
        ]);

        $id = DB::table('customer_addresses')->insertGetId([
            ...$data,
            'customer_id' => $customerId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $address = DB::table('customer_addresses')->find($id);
        return response()->json($address, 201);
    }

    public function update(Request $request, $customerId, $id)
    {
        if (!$this->authenticate($request))
            return response()->json(['message' => 'No autorizado'], 401);

        $data = $request->validate([
            'line'    => 'required|string',
            'city'    => 'required|string',
            'state'   => 'required|string',
            'country' => 'required|string',
        ]);

        DB::table('customer_addresses')
            ->where('id', $id)
            ->where('customer_id', $customerId)
            ->update([...$data, 'updated_at' => now()]);

        $address = DB::table('customer_addresses')->find($id);
        return response()->json($address, 200);
    }

    public function destroy(Request $request, $customerId, $id)
    {
        if (!$this->authenticate($request))
            return response()->json(['message' => 'No autorizado'], 401);

        DB::table('customer_addresses')
            ->where('id', $id)
            ->where('customer_id', $customerId)
            ->delete();

        return response()->json(['message' => 'Dirección eliminada'], 200);
    }
}
