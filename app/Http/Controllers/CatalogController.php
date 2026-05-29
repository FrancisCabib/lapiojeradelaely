<?php

namespace App\Http\Controllers;

use App\Models\CatalogSection;
use App\Models\Category;
use App\Support\OrderSettings;
use Inertia\Response;

class CatalogController extends Controller
{
    public function show(): Response
    {
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

        $weekdays = $sections->map(function (CatalogSection $section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'slug' => $section->slug,
                'weekday' => $section->weekday,
                'description' => $section->description,
                'courses' => $section->categories->map(function (Category $category) {
                    $service = $category->services->first();

                    return [
                        'menu_heading' => $category->menu_heading ?? $category->name,
                        'menu_course' => $category->menu_course,
                        'service' => $service ? [
                            'id' => $service->id,
                            'title' => $service->title,
                            'subtitle' => $service->subtitle,
                            'short_description' => $service->short_description,
                            'long_description' => $service->long_description,
                            'price' => $service->price,
                            'price_formatted' => is_null($service->price) ? null : '$'.number_format((int) $service->price, 0, ',', '.'),
                            'image_url' => $service->image_url,
                            'tags' => $service->tags->map(fn ($t) => [
                                'id' => $t->id,
                                'name' => $t->name,
                                'slug' => $t->slug,
                            ]),
                        ] : null,
                    ];
                })->values(),
            ];
        })->values();

        return Inertia::render('Catalog', [
            'weekdays' => $weekdays,
            'orderSettings' => OrderSettings::toArray(),
        ]);
    }
}
