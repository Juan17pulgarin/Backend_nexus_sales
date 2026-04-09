<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'CustomerID'     => 'required|integer',
            'AddressLine1'   => 'required|string|max:60',
            'AddressLine2'   => 'nullable|string|max:60',
            'City'           => 'required|string|max:30',
            'StateProvince'  => 'required|string|max:50',
            'CountryRegion'  => 'required|string|max:50',
            'PostalCode'     => 'required|string|max:15',
            'AddressType'    => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'CustomerID.required' => 'CustomerID es obligatorio',
            'AddressLine1.required' => 'AddressLine1 es obligatorio',
            'City.required' => 'City es obligatorio',
            'StateProvince.required' => 'StateProvince es obligatorio',
            'CountryRegion.required' => 'CountryRegion es obligatorio',
            'PostalCode.required' => 'PostalCode es obligatorio',
        ];
    }
}
