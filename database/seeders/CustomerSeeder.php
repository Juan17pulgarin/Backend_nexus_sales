<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $now = Carbon::now();

        // Create sample customers
        $customers = [
            ['first_name' => 'Ana', 'last_name' => 'Gomez', 'email' => 'ana@example.com', 'created_at' => $now, 'updated_at' => $now],
            ['first_name' => 'Luis', 'last_name' => 'Martinez', 'email' => 'luis@example.com', 'created_at' => $now, 'updated_at' => $now],
            ['first_name' => 'Carla', 'last_name' => 'Perez', 'email' => 'carla@example.com', 'created_at' => $now, 'updated_at' => $now],
            ['first_name' => 'Jose', 'last_name' => 'Lopez', 'email' => 'jose@example.com', 'created_at' => $now, 'updated_at' => $now],
        ];

        $ids = [];
        foreach ($customers as $c) {
            $ids[] = DB::table('customers')->insertGetId($c);
        }

        // Create addresses for customers with different cities/states
        $addresses = [
            ['customer_id' => $ids[0], 'line' => 'Calle 1', 'city' => 'Madrid', 'state' => 'Madrid', 'country' => 'ES', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => $ids[1], 'line' => 'Avenida 2', 'city' => 'Barcelona', 'state' => 'Catalonia', 'country' => 'ES', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => $ids[2], 'line' => 'Street 3', 'city' => 'Valencia', 'state' => 'Valencian Community', 'country' => 'ES', 'created_at' => $now, 'updated_at' => $now],
            // Multiple addresses for one customer (to ensure distinct results)
            ['customer_id' => $ids[3], 'line' => 'Road 4', 'city' => 'Madrid', 'state' => 'Madrid', 'country' => 'ES', 'created_at' => $now, 'updated_at' => $now],
            ['customer_id' => $ids[3], 'line' => 'Road 5', 'city' => 'Seville', 'state' => 'Andalusia', 'country' => 'ES', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('customer_addresses')->insert($addresses);
    }
}
