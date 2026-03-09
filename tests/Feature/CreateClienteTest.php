<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CreateClienteTest extends TestCase
{
    public function test_requires_bearer_token_to_create_cliente(): void
    {
        $response = $this->postJson('/api/clientes', [
            'FirstName' => 'Ana',
            'LastName' => 'Perez',
            'EmailAddress' => 'ana@example.com',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson(['message' => 'No autorizado']);
    }

    public function test_validates_required_names_and_email_format(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $response = $this->postJson(
            '/api/clientes',
            [
                'FirstName' => '',
                'LastName' => '',
                'EmailAddress' => 'correo-invalido',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['FirstName', 'LastName', 'EmailAddress']);
    }

    public function test_creates_cliente_successfully(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $builder = Mockery::mock();
        $builder->shouldReceive('insert')
            ->once()
            ->andReturn(true);

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Customer')
            ->andReturn($builder);

        $response = $this->postJson(
            '/api/clientes',
            [
                'FirstName' => 'Ana',
                'LastName' => 'Perez',
                'EmailAddress' => 'ana@example.com',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'Cliente registrado correctamente']);
    }

    public function test_returns_500_when_database_insert_fails(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $builder = Mockery::mock();
        $builder->shouldReceive('insert')
            ->once()
            ->andThrow(new \RuntimeException('DB error'));

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Customer')
            ->andReturn($builder);

        DB::shouldReceive('getDefaultConnection')
            ->once()
            ->andReturn('sqlite');

        $response = $this->postJson(
            '/api/clientes',
            [
                'FirstName' => 'Ana',
                'LastName' => 'Perez',
                'EmailAddress' => 'ana@example.com',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Error al registrar cliente',
                'error' => 'No se pudo guardar el cliente en la base de datos',
            ]);
    }
}
