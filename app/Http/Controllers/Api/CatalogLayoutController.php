<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogSection;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use App\Support\OrderSettings;
use Illuminate\Support\Carbon;

class CatalogLayoutController extends Controller
{
    public function show(): JsonResponse
    {
        $heroText = function (array $keys, string $fallback): string {
            foreach ($keys as $key) {
                $value = config($key);
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }

            return $fallback;
        };

        $sections = CatalogSection::query()
            ->active()
            ->visibleNow()
            ->orderByRaw('CASE WHEN weekday IS NULL THEN 1 ELSE 0 END')
            ->orderBy('weekday')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'categories' => fn ($query) => $query
                    ->where('categories.is_active', true)
                    ->orderBy('categories.sort_order')
                    ->orderBy('categories.id'),
                'categories.services' => fn ($query) => $query
                    ->where('services.is_active', true)
                    ->ordered()
                    ->with(['tags:id,name,slug']),
            ])
            ->get();

        $payload = $sections->map(function (CatalogSection $section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'slug' => $section->slug,
                'description' => $section->description,
                'is_active' => $section->is_active,
                'weekday' => $section->weekday,
                'starts_at' => $section->starts_at?->toIso8601String(),
                'ends_at' => $section->ends_at?->toIso8601String(),
                'categories' => $section->categories->map(function (Category $category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->menu_heading ?? $category->name,
                        'slug' => $category->slug,
                        'menu_course' => $category->menu_course,
                        'services' => $category->services->map(fn ($service) => [
                            'id' => $service->id,
                            'title' => $service->title,
                            'subtitle' => $service->subtitle,
                            'short_description' => $service->short_description,
                            'long_description' => $service->long_description,
                            'price' => $service->price,
                            'price_formatted' => is_null($service->price) ? null : '$'.number_format((int) $service->price, 0, ',', '.'),
                            'image_url' => $service->image_url,
                            'tags' => $service->tags->map(fn ($tag) => [
                                'id' => $tag->id,
                                'name' => $tag->name,
                                'slug' => $tag->slug,
                            ])->values(),
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'meta' => [
                'generated_at' => Carbon::now()->toIso8601String(),
                'timezone' => config('app.timezone'),
            ],
            'hero' => [
                'pre_headline' => $heroText(
                    ['catalog.hero.pre_headline'],
                    'La Piojera de la Ely'
                ),
                'headline' => $heroText(
                    ['catalog.hero.headline', 'catalog.hero.title'],
                    'Carta de la semana'
                ),
                'sub_headline' => $heroText(
                    ['catalog.hero.sub_headline', 'catalog.hero.subtitle', 'catalog.hero.info_text'],
                    'Un plato distinto cada día en Tierra Amarilla, Región de Atacama. Cocina casera con sabor de barrio: consulta disponibilidad y precios al momento.'
                ),
                'cta_text' => $heroText(
                    ['catalog.hero.cta_text'],
                    'Consultar o pedir'
                ),
                'cta_url' => $heroText(
                    ['catalog.hero.cta_url'],
                    '#contacto'
                ),
            ],
            'sections' => $payload,
            'order_settings' => OrderSettings::toArray(),
        ]);
    }
}
