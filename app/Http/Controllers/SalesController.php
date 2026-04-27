<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    // LISTAR VENTAS
    public function index(Request $request)
    {
        try {
            $query = DB::table('SalesLT.SalesOrderHeader as soh')
                ->join('SalesLT.Customer as c', 'soh.CustomerID', '=', 'c.CustomerID')
                ->select(
                    'soh.SalesOrderID',
                    'soh.OrderDate',
                    'soh.Status',
                    'soh.TotalDue',
                    'c.FirstName',
                    'c.LastName'
                )
                ->orderBy('soh.SalesOrderID', 'desc');

            // Igual que clientes → optimización
            if (!$request->has('todo')) {
                $query->limit(10);
            }

            $ventas = $query->get();

            return response()->json($ventas);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error cargando ventas',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // DETALLE DE UNA VENTA (pro level 🔥)
    public function show($id)
    {
        try {
            $venta = DB::table('SalesLT.SalesOrderHeader as soh')
                ->join('SalesLT.Customer as c', 'soh.CustomerID', '=', 'c.CustomerID')
                ->where('soh.SalesOrderID', $id)
                ->select(
                    'soh.*',
                    'c.FirstName',
                    'c.LastName',
                    'c.EmailAddress'
                )
                ->first();

            $detalle = DB::table('SalesLT.SalesOrderDetail as sod')
                ->join('SalesLT.Product as p', 'sod.ProductID', '=', 'p.ProductID')
                ->where('sod.SalesOrderID', $id)
                ->select(
                    'p.Name',
                    'sod.OrderQty',
                    'sod.UnitPrice',
                    'sod.LineTotal'
                )
                ->get();

            return response()->json([
                'venta' => $venta,
                'detalle' => $detalle
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error cargando detalle',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}