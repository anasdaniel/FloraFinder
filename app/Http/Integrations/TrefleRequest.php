<?php

namespace App\Http\Integrations;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class TrefleRequest extends Request
{
    protected Method $method;

    public function __construct(
        private readonly string $endpoint,
        private readonly array $queryParameters = [],
        Method $method = Method::GET,
    ) {
        $this->method = $method;
    }

    public function resolveEndpoint(): string
    {
        return '/' . ltrim($this->endpoint, '/');
    }

    protected function defaultQuery(): array
    {
        return array_merge([
            'token' => env('TREFLE_API_KEY'),
        ], $this->queryParameters);
    }
}