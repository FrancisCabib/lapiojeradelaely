<?php

namespace App\Actions\Services;

use App\Models\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreServiceAction
{
    public function execute(array $data): Service
    {
        $imagePath = $this->storeImage($data['image'] ?? null);

        $service = Service::create([
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

    protected function storeImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('services', 'public');
    }
}
