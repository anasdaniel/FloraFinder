<?php

namespace App\Http\Integrations;

use Saloon\Http\Request;
use Saloon\Enums\Method;
use Saloon\Data\MultipartValue;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasMultipartBody;
use Illuminate\Support\Facades\Log;

class IdentifyPlantRequest extends Request implements HasBody
{
    use HasMultipartBody;

    protected Method $method = Method::POST;
    protected string $project;
    protected $imageURL;
    protected string $apiKey;
    protected string $organ;
    protected ?string $tempImage = null;

    public function __construct(string $project, $imageURL, string $apiKey, string $organ = 'flower')
    {
        $this->project = $project;
        $this->imageURL = $imageURL;
        $this->apiKey = $apiKey;
        $this->organ = $organ;

        // Process the image - try to download it if it's a URL
        if (filter_var($imageURL, FILTER_VALIDATE_URL)) {
            $this->downloadImage();
        } elseif (file_exists($imageURL)) {
            // If it's already a local file path
            $this->tempImage = $imageURL;
        }
    }

    protected function downloadImage(): void
    {
        try {
            $tempImage = storage_path('app/temp-plant-image-' . uniqid('', true) . '.jpg');
            $imageContents = @file_get_contents($this->imageURL);

            if ($imageContents === false) {
                Log::error('Failed to download image from URL: ' . $this->imageURL);
                return;
            }

            if (file_put_contents($tempImage, $imageContents)) {
                $this->tempImage = $tempImage;
            } else {
                Log::error('Failed to save downloaded image to: ' . $tempImage);
            }
        } catch (\Exception $e) {
            Log::error('Exception while downloading image: ' . $e->getMessage());
        }
    }

    public function resolveEndpoint(): string
    {
        return "/identify/{$this->project}";
    }

    public function defaultQuery(): array
    {
        return [
            'api-key' => $this->apiKey, // Move API key to query parameters
            'include-related-images' => 'true',
            'lang' => 'en',
            'nb-results' => '3',
        ];
    }

    protected function defaultBody(): array
    {
        if (!$this->tempImage || !file_exists($this->tempImage)) {
            throw new \RuntimeException('No valid image available for plant identification');
        }

        return [
            new MultipartValue(
                name: 'images',
                value: fopen($this->tempImage, 'r'),
                filename: 'plant-image.jpg'
            ),
            new MultipartValue(
                name: 'organs',
                value: $this->organ
            )
        ];
    }

    public function __destruct()
    {
        // Clean up temporary files if we created them
        if ($this->tempImage && strpos(
            $this->tempImage,
            'temp-plant-image-'
        ) !== false && file_exists($this->tempImage)) {
            @unlink($this->tempImage);
        }
    }
}
