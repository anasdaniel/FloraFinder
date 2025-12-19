<?php

namespace App\Http\Integrations;

use Saloon\Http\Connector;

class INaturalistConnector extends Connector
{
              public function resolveBaseUrl(): string
              {
                            return 'https://api.inaturalist.org/v1';
              }

              protected function defaultHeaders(): array
              {
                            return [
                                          'Accept' => 'application/json',
                                          'Content-Type' => 'application/json',
                            ];
              }
}
