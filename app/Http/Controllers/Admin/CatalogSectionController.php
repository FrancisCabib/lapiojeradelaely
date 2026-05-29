<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCatalogSectionRequest;
use App\Http\Requests\Admin\UpdateCatalogSectionRequest;
use App\Models\CatalogSection;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogSectionController extends Controller
{
    public function index(): Response
    {
        $sections = CatalogSection::query()
            ->ordered()
            ->with(['categories:id,name'])
            ->paginate(20)
            ->through(fn (CatalogSection $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'slug' => $section->slug,
                'description' => $section->description,
                'is_active' => $section->is_active,
                'sort_order' => $section->sort_order,
                'weekday' => $section->weekday,
                'starts_at' => $section->starts_at?->toDateTimeString(),
                'ends_at' => $section->ends_at?->toDateTimeString(),
                'categories' => $section->categories->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ]),
            ]);

        return Inertia::render('admin/Sections/Index', [
            'sections' => $sections,
            'categories' => Category::query()->orderByName()->get(['id', 'name']),
        ]);
    }

    public function store(StoreCatalogSectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $section = CatalogSection::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'weekday' => $validated['weekday'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        $this->syncCategories($section, $validated['category_ids'] ?? []);

        return to_route('admin.sections.index')->with('success', 'Sección creada correctamente.');
    }

    public function update(UpdateCatalogSectionRequest $request, CatalogSection $section): RedirectResponse
    {
        $validated = $request->validated();

        $section->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
            'weekday' => $validated['weekday'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        $this->syncCategories($section, $validated['category_ids'] ?? []);

        return back()->with('success', 'Sección actualizada correctamente.');
    }

    public function destroy(CatalogSection $section): RedirectResponse
    {
        $section->delete();

        return to_route('admin.sections.index')->with('success', 'Sección eliminada correctamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'min:0'],
        ]);

        foreach ($request->input('order') as $index => $sectionId) {
            CatalogSection::query()
                ->whereKey((int) $sectionId)
                ->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Orden de secciones actualizado.');
    }

    protected function syncCategories(CatalogSection $section, array $categoryIds): void
    {
        $syncData = [];
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));

        foreach ($ids as $index => $categoryId) {
            $syncData[$categoryId] = ['sort_order' => $index];
        }

        $section->categories()->sync($syncData);
    }
}
