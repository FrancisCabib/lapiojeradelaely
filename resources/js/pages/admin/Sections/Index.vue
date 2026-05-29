<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, CheckCircle2, FolderTree, Layers, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

type CategoryItem = { id: number; name: string };

type SectionItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    weekday: number | null;
    starts_at: string | null;
    ends_at: string | null;
    categories: CategoryItem[];
};

const WEEKDAY_OPTIONS: { value: string; label: string }[] = [
    { value: '', label: 'Sin día (sección general)' },
    { value: '1', label: 'Lunes' },
    { value: '2', label: 'Martes' },
    { value: '3', label: 'Miércoles' },
    { value: '4', label: 'Jueves' },
    { value: '5', label: 'Viernes' },
    { value: '6', label: 'Sábado' },
    { value: '7', label: 'Domingo' },
];

function weekdayBadge(section: SectionItem): string {
    if (section.weekday == null) {
        return '—';
    }

    const opt = WEEKDAY_OPTIONS.find((o) => o.value === String(section.weekday));

    return opt?.label ?? '—';
}

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedSections = {
    data: SectionItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
};

const props = defineProps<{
    sections: PaginatedSections;
    categories: CategoryItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
    { title: 'Secciones', href: admin.sections.index() },
];

const page = usePage();
const flashMessage = ref<string | null>(null);
let flashTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) {
            return;
        }

        flashMessage.value = msg;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(() => {
            flashMessage.value = null;
        }, 4000);
    },
    { immediate: true },
);

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
    weekday: '',
    starts_at: '',
    ends_at: '',
    category_ids: [] as number[],
});

function submitCreate() {
    createForm.post(admin.sections.store.url(), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

const editDialogOpen = ref(false);
const editingSection = ref<SectionItem | null>(null);
const editForm = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: 0,
    weekday: '',
    starts_at: '',
    ends_at: '',
    category_ids: [] as number[],
});

function toDateTimeLocal(value: string | null): string {
    if (!value) {
        return '';
    }

    return value.replace(' ', 'T').slice(0, 16);
}

function openEdit(section: SectionItem) {
    editingSection.value = section;
    editForm.name = section.name;
    editForm.description = section.description ?? '';
    editForm.is_active = section.is_active;
    editForm.sort_order = section.sort_order;
    editForm.weekday = section.weekday != null ? String(section.weekday) : '';
    editForm.starts_at = toDateTimeLocal(section.starts_at);
    editForm.ends_at = toDateTimeLocal(section.ends_at);
    editForm.category_ids = section.categories.map((category) => category.id);
    editForm.clearErrors();
    editDialogOpen.value = true;
}

function submitEdit() {
    if (!editingSection.value) {
        return;
    }

    editForm.put(admin.sections.update.url(editingSection.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
        },
    });
}

const { confirm } = useConfirm();

async function deleteSection(id: number) {
    const ok = await confirm({
        title: 'Eliminar sección',
        description: 'Esta acción no se puede deshacer.',
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        variant: 'destructive',
    });

    if (!ok) {
        return;
    }

    router.delete(admin.sections.destroy.url(id), { preserveScroll: true });
}

function moveUp(index: number) {
    if (index <= 0) {
        return;
    }

    const newOrder = [...props.sections.data];
    [newOrder[index - 1], newOrder[index]] = [newOrder[index], newOrder[index - 1]];
    submitReorder(newOrder);
}

function moveDown(index: number) {
    if (index >= props.sections.data.length - 1) {
        return;
    }

    const newOrder = [...props.sections.data];
    [newOrder[index], newOrder[index + 1]] = [newOrder[index + 1], newOrder[index]];
    submitReorder(newOrder);
}

function submitReorder(sections: SectionItem[]) {
    router.post(admin.sections.reorder.url(), {
        order: sections.map((section) => section.id),
    });
}
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Head title="Secciones de catálogo" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    title="Secciones"
                    description="Carta semanal: asigna Lunes–Domingo a cada sección; el catálogo público ordena esos días primero. Luego: categorías y servicios."
                />
                <div class="flex gap-2">
                    <Link :href="admin.categories.index()">
                        <Button variant="outline">
                            <FolderTree class="mr-2 h-4 w-4" />
                            Categorías
                        </Button>
                    </Link>
                    <Link :href="admin.services.index()">
                        <Button variant="outline">
                            <Layers class="mr-2 h-4 w-4" />
                            Servicios
                        </Button>
                    </Link>
                </div>
            </div>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-1 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-y-1 opacity-0"
            >
                <div
                    v-if="flashMessage"
                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
                >
                    <CheckCircle2 class="size-4 shrink-0" />
                    <span class="flex-1">{{ flashMessage }}</span>
                    <button
                        type="button"
                        class="rounded p-0.5 hover:bg-green-100 dark:hover:bg-green-900"
                        @click="flashMessage = null"
                    >
                        <X class="size-3.5" />
                    </button>
                </div>
            </Transition>

            <div
                v-if="$page.props.flash?.error"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
            >
                {{ $page.props.flash.error }}
            </div>

            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <h3 class="mb-3 text-sm font-medium">Crear nueva sección</h3>
                <p class="mb-3 text-xs text-muted-foreground">
                    Defina categorías en la sección y los servicios aparecerán automáticamente desde cada categoría. Para
                    días de carta, lo habitual es Entrada → Plato de fondo → Bebestibles (puedes generarlas con el seeder
                    <code class="rounded bg-muted px-1 py-0.5">WeekdayMenuCoursesSeeder</code>).
                </p>
                <form class="space-y-4" @submit.prevent="submitCreate">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="name">Nombre *</Label>
                            <Input id="name" v-model="createForm.name" required maxlength="120" placeholder="Ej: Día del Padre" />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="sort_order">Orden</Label>
                            <Input id="sort_order" v-model.number="createForm.sort_order" type="number" min="0" />
                            <InputError :message="createForm.errors.sort_order" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="weekday">Día de la carta (opcional)</Label>
                        <select
                            id="weekday"
                            v-model="createForm.weekday"
                            class="flex h-10 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option v-for="opt in WEEKDAY_OPTIONS" :key="`create-wd-${opt.value || 'none'}`" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.weekday" />
                        <p class="text-xs text-muted-foreground">
                            Para menú semanal: un día por sección (Lunes a Domingo). En el catálogo público se ordenan primero por día.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="createForm.description"
                            rows="2"
                            maxlength="1000"
                            class="flex min-h-[70px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="starts_at">Inicio (opcional)</Label>
                            <Input id="starts_at" v-model="createForm.starts_at" type="datetime-local" />
                            <InputError :message="createForm.errors.starts_at" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="ends_at">Fin (opcional)</Label>
                            <Input id="ends_at" v-model="createForm.ends_at" type="datetime-local" />
                            <InputError :message="createForm.errors.ends_at" />
                        </div>
                    </div>

                    <div class="space-y-2 rounded-md border border-input p-3">
                        <Label class="text-sm">Categorías (la sección contiene categorías)</Label>
                        <div class="max-h-48 space-y-1 overflow-auto">
                            <label
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="createForm.category_ids"
                                    type="checkbox"
                                    :value="category.id"
                                    class="size-4 rounded border-input"
                                >
                                <span>{{ category.name }}</span>
                            </label>
                        </div>
                        <InputError :message="createForm.errors.category_ids" />
                        <p class="text-xs text-muted-foreground">
                            Los servicios se muestran según la categoría seleccionada.
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        >
                        <span>Sección activa</span>
                    </label>

                    <Button type="submit" :disabled="createForm.processing">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear sección
                    </Button>
                </form>
            </div>

            <div class="rounded-lg border border-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="w-24 px-4 py-3 text-left font-medium">Orden</th>
                                <th class="px-4 py-3 text-left font-medium">Sección</th>
                                <th class="px-4 py-3 text-left font-medium">Día carta</th>
                                <th class="px-4 py-3 text-left font-medium">Estado</th>
                                <th class="px-4 py-3 text-left font-medium">Vigencia</th>
                                <th class="px-4 py-3 text-right font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(section, index) in sections.data"
                                :key="section.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <Button variant="ghost" size="icon" :disabled="index === 0" @click="moveUp(index)">
                                            <ArrowUp class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" :disabled="index === sections.data.length - 1" @click="moveDown(index)">
                                            <ArrowDown class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ section.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ section.slug }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ section.categories.length }} categorías
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ weekdayBadge(section) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs"
                                        :class="section.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'"
                                    >
                                        {{ section.is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <div>{{ section.starts_at ? `Desde ${section.starts_at}` : 'Sin inicio' }}</div>
                                    <div>{{ section.ends_at ? `Hasta ${section.ends_at}` : 'Sin fin' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="icon" aria-label="Editar" @click="openEdit(section)">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            aria-label="Eliminar"
                                            @click="deleteSection(section.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!sections.data.length">
                                <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                    No hay secciones. Cree una arriba.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="sections.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row"
                >
                    <span>
                        Mostrando {{ sections.from }}–{{ sections.to }} de {{ sections.total }} secciones
                    </span>
                    <nav class="flex gap-1" aria-label="Paginación">
                        <template v-for="link in sections.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="inline-flex min-w-[2rem] items-center justify-center rounded-md px-2 py-1 text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-primary text-primary-foreground pointer-events-none' : 'hover:bg-muted'"
                            >
                                <!-- eslint-disable-next-line vue/no-v-html -->
                                <span v-html="link.label" />
                            </Link>
                            <span
                                v-else
                                class="inline-flex min-w-[2rem] cursor-not-allowed items-center justify-center rounded-md px-2 py-1 text-xs opacity-40"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Editar sección</DialogTitle>
                    <DialogDescription>
                        Ajuste datos de la sección, asignaciones y vigencia.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="edit-name">Nombre *</Label>
                            <Input id="edit-name" v-model="editForm.name" required maxlength="120" />
                            <InputError :message="editForm.errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-sort-order">Orden</Label>
                            <Input id="edit-sort-order" v-model.number="editForm.sort_order" type="number" min="0" />
                            <InputError :message="editForm.errors.sort_order" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-weekday">Día de la carta (opcional)</Label>
                        <select
                            id="edit-weekday"
                            v-model="editForm.weekday"
                            class="flex h-10 w-full max-w-md rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option v-for="opt in WEEKDAY_OPTIONS" :key="`edit-wd-${opt.value || 'none'}`" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.weekday" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-description">Descripción</Label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="2"
                            maxlength="1000"
                            class="flex min-h-[70px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="edit-starts-at">Inicio (opcional)</Label>
                            <Input id="edit-starts-at" v-model="editForm.starts_at" type="datetime-local" />
                            <InputError :message="editForm.errors.starts_at" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-ends-at">Fin (opcional)</Label>
                            <Input id="edit-ends-at" v-model="editForm.ends_at" type="datetime-local" />
                            <InputError :message="editForm.errors.ends_at" />
                        </div>
                    </div>

                    <div class="space-y-2 rounded-md border border-input p-3">
                        <Label class="text-sm">Categorías (la sección contiene categorías)</Label>
                        <div class="max-h-48 space-y-1 overflow-auto">
                            <label
                                v-for="category in categories"
                                :key="`edit-category-${category.id}`"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="editForm.category_ids"
                                    type="checkbox"
                                    :value="category.id"
                                    class="size-4 rounded border-input"
                                >
                                <span>{{ category.name }}</span>
                            </label>
                        </div>
                        <InputError :message="editForm.errors.category_ids" />
                        <p class="text-xs text-muted-foreground">
                            Los servicios se muestran según la categoría seleccionada.
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        >
                        <span>Sección activa</span>
                    </label>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="editDialogOpen = false">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            Guardar cambios
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppSidebarLayout>
</template>
