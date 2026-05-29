<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, FolderTree, GripVertical, Pencil, Plus, Tag, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatCLP } from '@/lib/utils';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

type ServiceItem = {
    id: number;
    category: string;
    title: string;
    subtitle?: string;
    price?: number | null;
    short_description: string;
    image_url?: string;
    is_active: boolean;
    sort_order: number;
    tags: { id: number; name: string }[];
};

const props = defineProps<{
    services: ServiceItem[];
}>();

const selectedIds = ref<number[]>([]);

watch(
    () => props.services,
    (list) => {
        const allowed = new Set(list.map((s) => s.id));
        selectedIds.value = selectedIds.value.filter((id) => allowed.has(id));
    },
    { deep: true },
);

const selectAllState = computed<boolean | 'indeterminate'>(() => {
    if (props.services.length === 0) {
        return false;
    }

    const n = selectedIds.value.length;

    if (n === 0) {
        return false;
    }

    if (n === props.services.length) {
        return true;
    }

    return 'indeterminate';
});

function onSelectAllChange(value: boolean | 'indeterminate') {
    if (value === true) {
        selectedIds.value = props.services.map((s) => s.id);
        return;
    }

    if (value === false) {
        selectedIds.value = [];
    }
}

function onRowSelectChange(id: number, value: boolean | 'indeterminate') {
    if (value === 'indeterminate') {
        return;
    }

    if (value) {
        if (!selectedIds.value.includes(id)) {
            selectedIds.value = [...selectedIds.value, id];
        }
    } else {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    }
}

const groupedServices = computed(() => {
    const groups = new Map<string, Array<{ item: ServiceItem; index: number }>>();

    for (const [index, service] of props.services.entries()) {
        const key = service.category || 'General';

        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key)!.push({ item: service, index });
    }

    return Array.from(groups.entries()).map(([category, services]) => ({ category, services }));
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.services.index() },
    { title: 'Servicios', href: admin.services.index() },
];

function moveUp(index: number) {
    if (index <= 0) {
        return;
    }

    const newOrder = [...props.services];
    [newOrder[index - 1], newOrder[index]] = [newOrder[index], newOrder[index - 1]];
    submitReorder(newOrder);
}

function moveDown(index: number) {
    if (index >= props.services.length - 1) {
        return;
    }

    const newOrder = [...props.services];
    [newOrder[index], newOrder[index + 1]] = [newOrder[index + 1], newOrder[index]];
    submitReorder(newOrder);
}

function submitReorder(services: ServiceItem[]) {
    const order = services.reduce<Record<number, number>>((acc, s, i) => {
        acc[s.id] = i;

        return acc;
    }, {});

    router.post(admin.services.reorder.url(), { order });
}

const { confirm } = useConfirm();

function toggleActive(id: number) {
    router.patch(admin.services.toggleActive.url(id));
}

async function destroy(id: number) {
    const ok = await confirm({
        title: 'Eliminar servicio',
        description: 'Esta acción no se puede deshacer.',
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        variant: 'destructive',
    });

    if (!ok) {
        return;
    }

    router.delete(admin.services.destroy.url(id), { preserveScroll: true });
}

async function bulkDestroy() {
    if (selectedIds.value.length === 0) {
        return;
    }

    const n = selectedIds.value.length;
    const ok = await confirm({
        title: n === 1 ? 'Eliminar servicio' : 'Eliminar servicios',
        description:
            n === 1
                ? 'Esta acción no se puede deshacer.'
                : `Se eliminarán ${n} servicios. Esta acción no se puede deshacer.`,
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        variant: 'destructive',
    });

    if (!ok) {
        return;
    }

    router.post(
        admin.services.bulkDestroy.url(),
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Servicios - Admin" />

        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Servicios</h1>
                    <p class="text-muted-foreground">
                        Gestiona los servicios del catálogo. Arrastra para cambiar el orden.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link :href="admin.categories.index()">
                        <Button variant="outline">
                            <FolderTree class="mr-2 size-4" />
                            Categorías
                        </Button>
                    </Link>
                    <Link :href="admin.tags.index()">
                        <Button variant="outline">
                            <Tag class="mr-2 size-4" />
                            Etiquetas
                        </Button>
                    </Link>
                    <Link :href="admin.services.create()">
                        <Button>
                            <Plus class="mr-2 size-4" />
                            Nuevo servicio
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-if="$page.props.flash?.success" class="rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="services.length"
                class="flex flex-col gap-3 rounded-lg border border-border bg-muted/30 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <Checkbox
                        id="select-all-services"
                        :model-value="selectAllState"
                        :aria-label="'Seleccionar todos los servicios'"
                        @update:model-value="onSelectAllChange"
                    />
                    <Label for="select-all-services" class="cursor-pointer text-sm font-normal">
                        Seleccionar todos
                    </Label>
                    <span v-if="selectedIds.length" class="text-sm text-muted-foreground">
                        {{ selectedIds.length }} seleccionado(s)
                    </span>
                </div>
                <Button
                    v-if="selectedIds.length"
                    variant="destructive"
                    size="sm"
                    type="button"
                    @click="bulkDestroy"
                >
                    <Trash2 class="mr-2 size-4" />
                    Eliminar seleccionados
                </Button>
            </div>

            <div class="space-y-6">
                <div v-for="group in groupedServices" :key="group.category" class="space-y-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ group.category }}
                    </h2>

                    <div class="grid gap-4">
                        <Card v-for="service in group.services" :key="service.item.id">
                            <CardHeader class="pb-2">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex flex-1 items-start gap-3">
                                        <div class="flex shrink-0 items-start pt-1">
                                            <Checkbox
                                                :model-value="selectedIds.includes(service.item.id)"
                                                :aria-label="`Seleccionar ${service.item.title}`"
                                                @update:model-value="(v) => onRowSelectChange(service.item.id, v)"
                                            />
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 shrink-0 cursor-grab"
                                                :disabled="service.index === 0"
                                                @click="moveUp(service.index)"
                                            >
                                                <ChevronUp class="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 shrink-0 cursor-grab"
                                                :disabled="service.index === services.length - 1"
                                                @click="moveDown(service.index)"
                                            >
                                                <ChevronDown class="size-4" />
                                            </Button>
                                        </div>
                                        <div
                                            v-if="service.item.image_url"
                                            class="size-16 shrink-0 overflow-hidden rounded-md bg-muted"
                                        >
                                            <img
                                                :src="service.item.image_url"
                                                :alt="service.item.title"
                                                class="size-full object-cover"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    v-if="!service.item.is_active"
                                                    class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900/50 dark:text-amber-200"
                                                >
                                                    Inactivo
                                                </span>
                                            </div>
                                            <CardTitle class="mt-1 text-lg">{{ service.item.title }}</CardTitle>
                                            <CardDescription v-if="service.item.subtitle">
                                                {{ service.item.subtitle }}
                                            </CardDescription>
                                            <p v-if="service.item.price" class="mt-1 text-sm font-semibold text-primary">
                                                {{ formatCLP(service.item.price) }}
                                            </p>
                                            <div
                                                v-if="service.item.tags?.length"
                                                class="mt-2 flex flex-wrap gap-1"
                                            >
                                                <span
                                                    v-for="tag in service.item.tags"
                                                    :key="tag.id"
                                                    class="rounded-full bg-primary/10 px-2 py-0.5 text-xs"
                                                >
                                                    {{ tag.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <Link :href="admin.services.edit(service.item.id)">
                                            <Button variant="outline" size="sm">
                                                <Pencil class="mr-1 size-4" />
                                                Editar
                                            </Button>
                                        </Link>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <GripVertical class="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    @select="() => toggleActive(service.item.id)"
                                                >
                                                    {{ service.item.is_active ? 'Desactivar' : 'Activar' }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    class="text-destructive"
                                                    variant="destructive"
                                                    @select="() => destroy(service.item.id)"
                                                >
                                                    <Trash2 class="mr-2 size-4" />
                                                    Eliminar
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </CardHeader>
                        </Card>
                    </div>
                </div>

                <div
                    v-if="!services.length"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed py-12 text-center"
                >
                    <p class="text-muted-foreground">No hay servicios aún.</p>
                    <Link :href="admin.services.create()" class="mt-2">
                        <Button variant="outline">Crear primer servicio</Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
