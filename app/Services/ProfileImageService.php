<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileImageService
{
    /**
     * Store or replace a profile image from either a base64 data URL or an uploaded file.
     */
    public function saveImage(string|UploadedFile|null $imageData, ?string $currentPath = null, string $subfolder = 'agents'): ?string
    {
        if (empty($imageData)) {
            return null;
        }

        if ($imageData instanceof UploadedFile) {
            $path = Storage::disk('public')->putFile($subfolder, $imageData);
            $this->deleteImage($currentPath);

            return $path ?: null;
        }

        if (is_string($imageData) && str_starts_with($imageData, 'data:image/')) {
            $commaPos = strpos($imageData, ',');
            if ($commaPos === false) {
                return null;
            }

            $header = substr($imageData, 0, $commaPos);
            $base64Data = substr($imageData, $commaPos + 1);

            $extension = 'jpg';
            if (str_contains($header, 'image/png')) {
                $extension = 'png';
            } elseif (str_contains($header, 'image/webp')) {
                $extension = 'webp';
            }

            $binary = base64_decode($base64Data);
            if ($binary === false || strlen($binary) === 0) {
                return null;
            }

            $filename = $subfolder.'/'.Str::random(32).'_'.time().'.'.$extension;
            Storage::disk('public')->put($filename, $binary);

            $this->deleteImage($currentPath);

            return $filename;
        }

        return null;
    }

    /**
     * Delete an existing image file from public storage.
     */
    public function deleteImage(?string $path): void
    {
        if (! empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
