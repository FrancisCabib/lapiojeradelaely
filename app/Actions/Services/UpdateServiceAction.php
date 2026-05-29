<?php

namespace App\Actions\Services;

use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateServiceAction
{

    public function execute(Service $service, array $data): Service
    {
        $imagePath = $service->image_path;

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $this->deleteImage($service->image_path);
            $imagePath = $this->storeImage($data['image']);
        }

        $service->update([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'price' => $data['price'] ?? null,
            'short_description' => $data['short_description'],
            'long_description' => $data['long_description'] ?? null,
            'image_path' => $imagePath,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $service->tags()->sync(isset($data['tag_id']) ? [$data['tag_id']] : []);

        return $service->load(['tags', 'category']);
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('services', 'public');
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
