<?php

namespace App\Http\Integrations;

use Saloon\Http\Request;
use Saloon\Enums\Method;

class GeminiRequest extends Request
{
    protected Method $method = Method::POST;
    private string $model;
    private array $contents;

    public function __construct(string $model, array $contents = [])
    {
        $this->model = $model;
        $this->contents = $contents;
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
        return [
            'contents' => $this->contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1024,
                'responseMimeType' => 'application/json',
            ],
        ];
    }
}
