<?php

namespace App\Http\Integrations;

use Saloon\Http\Request;
use Saloon\Enums\Method;

class GetINaturalistObservationsRequest extends Request
{
              protected Method $method = Method::GET;

              public function __construct(
                            protected string $scientificName,
                            protected int $placeId,
                            protected string $termId = '12', // Phenology
                            protected string $termValueId = '13' // Flowering (14 for Fruiting)
              ) {}

              public function resolveEndpoint(): string
              {
                            return '/observations/species_counts';
              }

              protected function defaultQuery(): array
              {
                            return [
                                          'q' => $this->scientificName,
                                          'place_id' => $this->placeId,
                                          'term_id' => $this->termId,
                                          'term_value_id' => $this->termValueId,
                                          'per_page' => 1,
                            ];
              }
}
