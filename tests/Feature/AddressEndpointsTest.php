
<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AddressEndpointsTest extends TestCase
{
    public function test_requires_bearer_token_to_get_addresses(): void
    {
        $response = $this->getJson('/api/customers/1/addresses');

        $response
            ->assertStatus(401)
            ->assertJson(['message' => 'No autorizado']);
    }

    public function test_returns_404_when_customer_does_not_exist_for_get_addresses(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $customerBuilder = Mockery::mock();
        $customerBuilder->shouldReceive('where')
            ->once()
            ->with('CustomerID', '1')
            ->andReturnSelf();
        $customerBuilder->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Customer')
            ->andReturn($customerBuilder);

        $response = $this->getJson('/api/customers/1/addresses', [
            'Authorization' => 'Bearer token-valido',
        ]);

        $response
            ->assertStatus(404)
            ->assertJson(['message' => 'Cliente no encontrado']);
    }

    public function test_returns_addresses_for_customer(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $customerBuilder = Mockery::mock();
        $customerBuilder->shouldReceive('where')
            ->once()
            ->with('CustomerID', '1')
            ->andReturnSelf();
        $customerBuilder->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $addressBuilder = Mockery::mock();
        $addressBuilder->shouldReceive('join')->once()->andReturnSelf();
        $addressBuilder->shouldReceive('select')->once()->andReturnSelf();
        $addressBuilder->shouldReceive('where')->once()->with('ca.CustomerID', '1')->andReturnSelf();
        $addressBuilder->shouldReceive('orderBy')->once()->with('a.AddressID')->andReturnSelf();
        $addressBuilder->shouldReceive('get')->once()->andReturn(collect([
            [
                'AddressID' => 10,
                'AddressLine1' => 'Calle 1',
                'AddressLine2' => null,
                'City' => 'Medellin',
                'StateProvince' => 'Antioquia',
                'CountryRegion' => 'Colombia',
                'PostalCode' => '050001',
                'rowguid' => 'uuid-test',
                'ModifiedDate' => '2026-04-09 10:00:00',
                'AddressType' => 'Home',
            ]
        ]));

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Customer')
            ->andReturn($customerBuilder);

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.CustomerAddress as ca')
            ->andReturn($addressBuilder);

        $response = $this->getJson('/api/customers/1/addresses', [
            'Authorization' => 'Bearer token-valido',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'AddressID' => 10,
                'AddressLine1' => 'Calle 1',
                'City' => 'Medellin',
            ]);
    }

    public function test_requires_required_fields_to_create_address(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $response = $this->postJson(
            '/api/addresses',
            [
                'CustomerID' => 1,
                'AddressLine1' => '',
                'City' => '',
                'StateProvince' => '',
                'CountryRegion' => '',
                'PostalCode' => '',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'AddressLine1',
                'City',
                'StateProvince',
                'CountryRegion',
                'PostalCode',
            ]);
    }

    public function test_returns_404_when_customer_does_not_exist_for_store_address(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $customerBuilder = Mockery::mock();
        $customerBuilder->shouldReceive('where')
            ->once()
            ->with('CustomerID', 1)
            ->andReturnSelf();
        $customerBuilder->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Customer')
            ->andReturn($customerBuilder);

        $response = $this->postJson(
            '/api/addresses',
            [
                'CustomerID' => 1,
                'AddressLine1' => 'Calle 10',
                'City' => 'Medellin',
                'StateProvince' => 'Antioquia',
                'CountryRegion' => 'Colombia',
                'PostalCode' => '050001',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(404)
            ->assertJson(['message' => 'Cliente no encontrado']);
    }

    public function test_requires_required_fields_to_update_address(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $response = $this->putJson(
            '/api/addresses/10',
            [
                'AddressLine1' => '',
                'City' => '',
                'StateProvince' => '',
                'CountryRegion' => '',
                'PostalCode' => '',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'AddressLine1',
                'City',
                'StateProvince',
                'CountryRegion',
                'PostalCode',
            ]);
    }

    public function test_returns_404_when_address_does_not_exist_for_update(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->andReturn(true);

        $addressBuilder = Mockery::mock();
        $addressBuilder->shouldReceive('where')
            ->once()
            ->with('AddressID', '10')
            ->andReturnSelf();
        $addressBuilder->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        DB::shouldReceive('table')
            ->once()
            ->with('SalesLT.Address')
            ->andReturn($addressBuilder);

        $response = $this->putJson(
            '/api/addresses/10',
            [
                'AddressLine1' => 'Nueva dirección',
                'City' => 'Bogota',
                'StateProvince' => 'Cundinamarca',
                'CountryRegion' => 'Colombia',
                'PostalCode' => '110111',
            ],
            [
                'Authorization' => 'Bearer token-valido',
            ]
        );

        $response
            ->assertStatus(404)
            ->assertJson(['message' => 'Dirección no encontrada']);
    }
}
