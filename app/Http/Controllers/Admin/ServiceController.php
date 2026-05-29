<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Services\ReorderServicesAction;
use App\Actions\Services\StoreServiceAction;
use App\Actions\Services\ToggleServiceActiveAction;
use App\Actions\Services\UpdateServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Category;
use App\Models\Service;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        protected StoreServiceAction $storeService,
        protected UpdateServiceAction $updateService,
        protected ReorderServicesAction $reorderServices,
        protected ToggleServiceActiveAction $toggleActive
    ) {}

    public function index(): Response
    {
        $services = Service::query()
            ->ordered()
            ->with(['tags', 'category'])
            ->get()
            ->map(fn (Service $s) => [
                'id' => $s->id,
                'category' => $s->getRelation('category')?->name ?? 'General',
                'title' => $s->title,
                'subtitle' => $s->subtitle,
                'price' => $s->price,
                'short_description' => $s->short_description,
                'image_url' => $s->image_url,
                'is_active' => $s->is_active,
                'sort_order' => $s->sort_order,
                'tags' => $s->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]),
            ]);

        return Inertia::render('admin/Services/Index', [
            'services' => $services,
        ]);
    }

    public function create(): Response
    {
        $tags = Tag::query()->orderByName()->get(['id', 'name']);
        $categories = Category::query()->orderByName()->get(['id', 'name']);

        return Inertia::render('admin/Services/Form', [
            'service' => null,
            'tags' => $tags,
            'categories' => $categories,
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $this->storeService->execute($request->validated());

        return to_route('admin.services.index')->with('success', 'Servicio creado correctamente.');
    }

    public function edit(Service $service): Response
    {
        $service->load(['tags', 'category']);
        $tags = Tag::query()->orderByName()->get(['id', 'name']);
        $categories = Category::query()->orderByName()->get(['id', 'name']);

        return Inertia::render('admin/Services/Form', [
            'service' => [
                'id' => $service->id,
                'category_id' => $service->category_id,
                'title' => $service->title,
                'subtitle' => $service->subtitle,
                'price' => $service->price,
                'short_description' => $service->short_description,
                'long_description' => $service->long_description,
                'image_url' => $service->image_url,
                'is_active' => $service->is_active,
                'sort_order' => $service->sort_order,
                'tag_id' => $service->tags->first()?->id,
            ],
            'tags' => $tags,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->updateService->execute($service, $request->validated());

        return to_route('admin.services.index')->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return to_route('admin.services.index')->with('success', 'Servicio eliminado correctamente.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        DB::transaction(function () use ($ids): void {
            Service::query()->whereIn('id', $ids)->delete();
        });

        $count = count($ids);

        return to_route('admin.services.index')->with(
            'success',
            $count === 1
                ? 'Se eliminó 1 servicio.'
                : "Se eliminaron {$count} servicios."
        );
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'min:0'],
        ]);

        $order = [];
        foreach ($request->input('order') as $index => $serviceId) {
            $order[(int) $serviceId] = $index;
        }

        $this->reorderServices->execute($order);

        return back()->with('success', 'Orden actualizado correctamente.');
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $this->toggleActive->execute($service);

        return back()->with('success', 'Estado del servicio actualizado.');
    }
}
