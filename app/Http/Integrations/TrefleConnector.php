<?php

namespace App\Http\Integrations;

use Saloon\Http\Connector;

class TrefleConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://trefle.io/api/v1';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
