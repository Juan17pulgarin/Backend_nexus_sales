<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesOrderHeaderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Seed sample orders for customers 1 and 2
        DB::table('SalesOrderHeader')->insert([
            [
                'CustomerID' => 1,
                'OrderDate' => Carbon::now()->subDays(10),
                'TotalDue' => 100.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'CustomerID' => 1,
                'OrderDate' => Carbon::now()->subDays(5),
                'TotalDue' => 200.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'CustomerID' => 2,
                'OrderDate' => Carbon::now()->subDays(2),
                'TotalDue' => 50.25,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
