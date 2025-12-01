<?php

namespace App\Http\Integrations;


use Saloon\Enums\Method;
use Saloon\Http\Request;



class SearchPlantRequest extends Request
{
    protected Method $method = Method::GET;
    protected string $slug;


    public function __construct(string $slug = '')
    {
        $slug = str_replace(' ', '-', strtolower($slug));
        $this->slug = $slug;
    }

    public function resolveEndpoint(): string
    {
        return '/species/' . $this->slug;
    }

    protected function defaultQuery(): array
    {
        return [
            'token' => env('TREFLE_API_KEY'),
        ];
    }
}
