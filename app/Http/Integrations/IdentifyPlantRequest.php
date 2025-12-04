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
    protected array $imagePaths = [];
    protected array $organs = [];
    protected string $apiKey;
    protected array $tempImages = [];

    /**
     * @param string $project
     * @param array $imagePaths Array of image paths or URLs
     * @param string $apiKey
     * @param array $organs Array of organ types corresponding to each image
     */
    public function __construct(string $project, array $imagePaths, string $apiKey, array $organs = [])
    {
        $this->project = $project;
        $this->apiKey = $apiKey;
        $this->organs = $organs;

        // Process each image
        foreach ($imagePaths as $imagePath) {
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                $tempImage = $this->downloadImage($imagePath);
                if ($tempImage) {
                    $this->tempImages[] = $tempImage;
                }
            } elseif (file_exists($imagePath)) {
                $this->tempImages[] = $imagePath;
            }
        }
    }

    protected function downloadImage(string $imageURL): ?string
    {
        try {
            $tempImage = storage_path('app/temp-plant-image-' . uniqid('', true) . '.jpg');
            $imageContents = @file_get_contents($imageURL);

            if ($imageContents === false) {
                Log::error('Failed to download image from URL: ' . $imageURL);
                return null;
            }

            if (file_put_contents($tempImage, $imageContents)) {
                return $tempImage;
            } else {
                Log::error('Failed to save downloaded image to: ' . $tempImage);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while downloading image: ' . $e->getMessage());
            return null;
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
        if (empty($this->tempImages)) {
            throw new \RuntimeException('No valid images available for plant identification');
        }

        $body = [];

        // Add all images and their corresponding organs
        foreach ($this->tempImages as $index => $tempImage) {
            if (!file_exists($tempImage)) {
                continue;
            }

            $body[] = new MultipartValue(
                name: 'images',
                value: fopen($tempImage, 'r'),
                filename: 'plant-image-' . ($index + 1) . '.jpg'
            );

            // Add corresponding organ if it's not 'auto'
            $organ = $this->organs[$index] ?? 'auto';
            if (strtolower($organ) !== 'auto') {
                $body[] = new MultipartValue(
                    name: 'organs',
                    value: $organ
                );
            }
        }

        if (empty($body)) {
            throw new \RuntimeException('No valid images available for plant identification');
        }

        return $body;
    }

    public function __destruct()
    {
        // Clean up temporary files if we created them
        foreach ($this->tempImages as $tempImage) {
            if ($tempImage && strpos(
                $tempImage,
                'temp-plant-image-'
            ) !== false && file_exists($tempImage)) {
                @unlink($tempImage);
            }
        }
    }
}
