<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerOrdersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('SalesOrderHeader', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('CustomerID')->index();
            $table->dateTime('OrderDate')->nullable();
            $table->decimal('TotalDue', 10, 2)->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('SalesOrderHeader');

        parent::tearDown();
    }

    public function test_returns_paginated_orders_and_total_accumulated()
    {
        // Seed some orders
        DB::table('SalesOrderHeader')->insert([
            [
                'CustomerID' => 1,
                'OrderDate' => Carbon::now()->subDays(1),
                'TotalDue' => 10.50,
            ],
            [
                'CustomerID' => 1,
                'OrderDate' => Carbon::now()->subDays(2),
                'TotalDue' => 20.25,
            ],
            [
                'CustomerID' => 1,
                'OrderDate' => Carbon::now()->subDays(3),
                'TotalDue' => 5.25,
            ],
            // Other customer's order
            [
                'CustomerID' => 2,
                'OrderDate' => Carbon::now(),
                'TotalDue' => 99.99,
            ],
        ]);

        $response = $this->getJson('/api/customers/1/orders?per_page=2');

        $response->assertStatus(200);

        $json = $response->json();

        // Orders should be paginated (per_page=2)
        $this->assertArrayHasKey('orders', $json);
        $this->assertCount(2, $json['orders']);

        // totalAccumulated should equal sum of CustomerID=1 TotalDue
        $this->assertArrayHasKey('totalAccumulated', $json);
        $this->assertEquals(36.00, (float) $json['totalAccumulated']);
    }
}
