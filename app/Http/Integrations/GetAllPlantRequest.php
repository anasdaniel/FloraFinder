<?php

namespace App\Http\Integrations;


use Saloon\Enums\Method;
use Saloon\Http\Request;



class GetAllPlantRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/plants';
    }

    protected function defaultQuery(): array
    {
        return [
            'token' => env('TREFLE_API_KEY'),
        ];
    }
}
