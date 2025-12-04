<?php

namespace App\Http\Integrations;

use Saloon\Http\Connector;

class GeminiConnector extends Connector
{
              public function resolveBaseUrl(): string
              {
                            return 'https://generativelanguage.googleapis.com/v1beta';
              }

              protected function defaultHeaders(): array
              {
                            return [
                                          'Accept' => 'application/json',
                                          'Content-Type' => 'application/json',
                            ];
              }
}
