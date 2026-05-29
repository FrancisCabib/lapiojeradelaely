<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, FolderTree, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
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

type CategoryItem = {
    id: number;
    name: string;
    slug: string;
    menu_heading: string | null;
    menu_course: string | null;
    services_count: number;
};

const MENU_COURSE_OPTIONS: { value: string; label: string }[] = [
    { value: '', label: 'Sin tipo (carta general)' },
    { value: 'entrada', label: 'Entrada' },
    { value: 'plato_fondo', label: 'Plato de fondo' },
    { value: 'bebestibles', label: 'Bebestibles' },
];

function courseLabel(course: string | null): string {
    if (!course) {
        return '—';
    }

    return MENU_COURSE_OPTIONS.find((o) => o.value === course)?.label ?? course;
}

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCategories = {
    data: CategoryItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
};

defineProps<{ categories: PaginatedCategories }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
    { title: 'Categorías', href: admin.categories.index() },
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
    menu_heading: '',
    menu_course: '',
});

function submitCreate() {
    createForm.post(admin.categories.store.url(), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

const editDialogOpen = ref(false);
const editingCategory = ref<CategoryItem | null>(null);
const editForm = useForm({
    name: '',
    menu_heading: '',
    menu_course: '',
});

function openEdit(category: CategoryItem) {
    editingCategory.value = category;
    editForm.name = category.name;
    editForm.menu_heading = category.menu_heading ?? '';
    editForm.menu_course = category.menu_course ?? '';
    editForm.clearErrors();
    editDialogOpen.value = true;
}

function submitEdit() {
    if (!editingCategory.value) {
        return;
    }

    editForm.put(admin.categories.update.url(editingCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
        },
    });
}

const { confirm } = useConfirm();

async function deleteCategory(id: number) {
    const ok = await confirm({
        title: 'Eliminar categoría',
        description: 'Esta acción no se puede deshacer. Solo se eliminará si no tiene servicios asociados.',
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        variant: 'destructive',
    });

    if (!ok) {
        return;
    }

    router.delete(admin.categories.destroy.url(id), { preserveScroll: true });
}
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Head title="Gestión de categorías" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    title="Categorías"
                    description="En la carta semanal, cada día usa Entrada, Plato de fondo y Bebestibles. El título público puede ser distinto del nombre interno (ej. Lunes · Entrada)."
                />
                <Link :href="admin.services.index()">
                    <Button variant="outline">
                        <FolderTree class="mr-2 h-4 w-4" />
                        Ir a servicios
                    </Button>
                </Link>
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
                <h3 class="mb-3 text-sm font-medium">Crear nueva categoría</h3>
                <form class="space-y-4" @submit.prevent="submitCreate">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5 md:col-span-2">
                            <Label for="category-name">Nombre interno (único) *</Label>
                            <Input
                                id="category-name"
                                v-model="createForm.name"
                                required
                                maxlength="100"
                                placeholder="Ej: Lunes · Entrada"
                                :class="createForm.errors.name ? 'border-destructive' : ''"
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="category-menu-heading">Título en catálogo público (opcional)</Label>
                            <Input
                                id="category-menu-heading"
                                v-model="createForm.menu_heading"
                                maxlength="100"
                                placeholder="Ej: Entrada"
                            />
                            <InputError :message="createForm.errors.menu_heading" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="category-menu-course">Tipo de bloque menú</Label>
                            <select
                                id="category-menu-course"
                                v-model="createForm.menu_course"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option v-for="opt in MENU_COURSE_OPTIONS" :key="`create-mc-${opt.value || 'x'}`" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.menu_course" />
                        </div>
                    </div>
                    <Button type="submit" :disabled="createForm.processing">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear
                    </Button>
                </form>
            </div>

            <div class="rounded-lg border border-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nombre</th>
                                <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Bloque</th>
                                <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Slug</th>
                                <th class="px-4 py-3 text-right font-medium">Servicios</th>
                                <th class="w-24 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                                <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">
                                    {{ courseLabel(category.menu_course) }}
                                </td>
                                <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                                    {{ category.slug }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ category.services_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Editar"
                                            @click="openEdit(category)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            aria-label="Eliminar"
                                            @click="deleteCategory(category.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!categories.data.length">
                                <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                                    No hay categorías. Cree una arriba.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="categories.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row"
                >
                    <span>
                        Mostrando {{ categories.from }}–{{ categories.to }} de {{ categories.total }} categorías
                    </span>
                    <nav class="flex gap-1" aria-label="Paginación">
                        <template v-for="link in categories.links" :key="link.label">
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
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Editar categoría</DialogTitle>
                    <DialogDescription>
                        Nombre interno (único), título opcional para el catálogo público y tipo de bloque del menú.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="space-y-2">
                        <Label for="edit-category-name">Nombre interno (único)</Label>
                        <Input
                            id="edit-category-name"
                            v-model="editForm.name"
                            required
                            maxlength="100"
                            :class="editForm.errors.name ? 'border-destructive' : ''"
                        />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-menu-heading">Título en catálogo público (opcional)</Label>
                        <Input
                            id="edit-menu-heading"
                            v-model="editForm.menu_heading"
                            maxlength="100"
                            placeholder="Ej: Entrada"
                        />
                        <InputError :message="editForm.errors.menu_heading" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-menu-course">Tipo de bloque menú</Label>
                        <select
                            id="edit-menu-course"
                            v-model="editForm.menu_course"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option v-for="opt in MENU_COURSE_OPTIONS" :key="`edit-mc-${opt.value || 'x'}`" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.menu_course" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editDialogOpen = false"
                        >
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
