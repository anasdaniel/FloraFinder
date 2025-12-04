<?php

namespace App\Http\Integrations;

use Saloon\Http\Request;
use Saloon\Enums\Method;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class GeminiRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;
    private string $model;
    private array $contents;
    private ?string $responseMimeType;

    public function __construct(string $model, array $contents = [], ?string $responseMimeType = 'application/json')
    {
        $this->model = $model;
        $this->contents = $contents;
        $this->responseMimeType = $responseMimeType;
    }

    public function resolveEndpoint(): string
    {
        return '/models/' . $this->model . ':generateContent';
    }

    protected function defaultQuery(): array
    {
        return [
            'key' => env('GEMINI_API_KEY'),
        ];
    }

    protected function defaultBody(): array
    {
        $config = [
            'temperature' => 0.4,
            'maxOutputTokens' => 1024,
        ];

        if ($this->responseMimeType) {
            $config['responseMimeType'] = $this->responseMimeType;
        }

        return [
            'contents' => $this->contents,
            'generationConfig' => $config,
        ];
    }
}
