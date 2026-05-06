<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateCustomerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_creates_customer_successfully(): void
    {
        $token = 'token-de-prueba';
        Cache::put('auth_token:' . $token, 'tester@example.com', now()->addHour());

        $response = $this->postJson('/api/clientes', [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('message', 'Cliente creado correctamente')
            ->assertJsonPath('data.first_name', 'Juan')
            ->assertJsonPath('data.last_name', 'Perez')
            ->assertJsonPath('data.email', 'juan@example.com');

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'email' => 'juan@example.com',
        ]);
    }

    public function test_returns_422_when_payload_is_invalid(): void
    {
        $token = 'token-de-prueba';
        Cache::put('auth_token:' . $token, 'tester@example.com', now()->addHour());

        $response = $this->postJson('/api/clientes', [
            'name' => '',
            'email' => 'correo-no-valido',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
