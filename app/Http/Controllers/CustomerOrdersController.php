<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CustomerOrdersController extends Controller
{
    private function authenticate(Request $request)
    {
        $auth = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $m)) return false;
        return Cache::has('auth_token:' . $m[1]);
    }

    public function index(Request $request, $customerId)
    {
        // if (!$this->authenticate($request))
        //     return response()->json(['message' => 'No autorizado'], 401);

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage <= 0) $perPage = 15;

        // Calculate total accumulated across all orders for the customer
        $totalAccumulated = (float) DB::table('SalesOrderHeader')
            ->where('CustomerID', $customerId)
            ->sum('TotalDue');

        // Paginate orders
        $ordersPaginator = DB::table('SalesOrderHeader')
            ->where('CustomerID', $customerId)
            ->orderBy('OrderDate', 'desc')
            ->paginate($perPage);

        $orders = $ordersPaginator->items();

        $response = [
            'orders' => $orders,
            'totalAccumulated' => $totalAccumulated,
            'pagination' => [
                'current_page' => $ordersPaginator->currentPage(),
                'per_page' => $ordersPaginator->perPage(),
                'total' => $ordersPaginator->total(),
                'last_page' => $ordersPaginator->lastPage(),
            ],
        ];

        return response()->json($response, 200);
    }
}
