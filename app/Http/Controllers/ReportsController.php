<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    // 📊 Revenue mensual
    public function revenue()
    {
        $data = DB::table('SalesLT.SalesOrderHeader')
            ->selectRaw("
                DATEPART(YEAR, OrderDate) as year,
                DATEPART(MONTH, OrderDate) as monthNumber,
                DATENAME(MONTH, OrderDate) as month,
                SUM(TotalDue) as revenue
            ")
            ->groupByRaw("
                DATEPART(YEAR, OrderDate),
                DATEPART(MONTH, OrderDate),
                DATENAME(MONTH, OrderDate)
            ")
            ->orderByRaw("year, monthNumber")
            ->get();

        return response()->json($data);
    }

    // 🥧 Ventas por categoría
    public function categories()
    {
        $data = DB::table('SalesLT.SalesOrderDetail as sod')
            ->join('SalesLT.Product as p', 'sod.ProductID', '=', 'p.ProductID')
            ->join('SalesLT.ProductCategory as pc', 'p.ProductCategoryID', '=', 'pc.ProductCategoryID')
            ->selectRaw("
                pc.Name as name,
                SUM(sod.LineTotal) as value
            ")
            ->groupBy('pc.Name')
            ->orderByDesc('value')
            ->get();

        return response()->json($data);
    }

    // 📈 Crecimiento clientes
    public function customers()
    {
        $data = DB::table('SalesLT.Customer')
            ->selectRaw("
                DATEPART(YEAR, ModifiedDate) as year,
                DATEPART(WEEK, ModifiedDate) as week,
                COUNT(*) as total
            ")
            ->groupByRaw("
                DATEPART(YEAR, ModifiedDate),
                DATEPART(WEEK, ModifiedDate)
            ")
            ->orderByRaw("year, week")
            ->get();

        return response()->json($data);
    }
}