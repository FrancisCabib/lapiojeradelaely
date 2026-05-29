<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { CheckCircle2, Pencil, Plus, Tag, Trash2, X } from 'lucide-vue-next';
import { useConfirm } from '@/composables/useConfirm';
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
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import admin from '@/routes/admin';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type TagItem = {
    id: number;
    name: string;
    slug: string;
    services_count: number;
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedTags = {
    data: TagItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
};

defineProps<{ tags: PaginatedTags }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
    { title: 'Etiquetas', href: admin.tags.index() },
];

// ── Flash notification ──────────────────────────────────────────────────────
const page = usePage();
const flashMessage = ref<string | null>(null);
let flashTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        flashMessage.value = msg;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(() => { flashMessage.value = null; }, 4000);
    },
    { immediate: true },
);

// ── Create form ─────────────────────────────────────────────────────────────
const createForm = useForm({ name: '' });

function submitCreate() {
    createForm.post(admin.tags.store.url(), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

// ── Edit dialog ─────────────────────────────────────────────────────────────
const editDialogOpen = ref(false);
const editingTag = ref<TagItem | null>(null);
const editForm = useForm({ name: '' });

function openEdit(tag: TagItem) {
    editingTag.value = tag;
    editForm.name = tag.name;
    editForm.clearErrors();
    editDialogOpen.value = true;
}

function submitEdit() {
    if (!editingTag.value) return;
    editForm.put(admin.tags.update.url(editingTag.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
        },
    });
}

// ── Delete ──────────────────────────────────────────────────────────────────
const { confirm } = useConfirm();

async function deleteTag(id: number) {
    const ok = await confirm({
        title: 'Eliminar tag',
        description: 'Los servicios asociados perderán esta etiqueta. Esta acción no se puede deshacer.',
        confirmText: 'Sí, eliminar',
        cancelText: 'Cancelar',
        variant: 'destructive',
    });
    if (!ok) return;
    router.delete(admin.tags.destroy.url(id), { preserveScroll: true });
}
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Head title="Gestión de tags" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    title="Etiquetas"
                    description="Etiquetas reutilizables para clasificar servicios"
                />
                <Link :href="admin.services.index()">
                    <Button variant="outline">
                        <Tag class="mr-2 h-4 w-4" />
                        Ir a servicios
                    </Button>
                </Link>
            </div>

            <!-- Flash message -->
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

            <!-- Create form -->
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <h3 class="mb-3 text-sm font-medium">Crear nuevo tag</h3>
                <form
                    class="flex flex-wrap items-end gap-4"
                    @submit.prevent="submitCreate"
                >
                    <div class="min-w-[200px] flex-1 space-y-1.5">
                        <Label for="tag-name" class="sr-only">Nombre</Label>
                        <Input
                            id="tag-name"
                            v-model="createForm.name"
                            required
                            maxlength="100"
                            placeholder="Nombre del tag"
                            :class="createForm.errors.name ? 'border-destructive' : ''"
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <Button type="submit" :disabled="createForm.processing">
                        <Plus class="mr-2 h-4 w-4" />
                        Crear
                    </Button>
                </form>
            </div>

            <!-- Data table -->
            <div class="rounded-lg border border-border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Nombre</th>
                                <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Slug</th>
                                <th class="px-4 py-3 text-right font-medium">Servicios</th>
                                <th class="w-24 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="tag in tags.data"
                                :key="tag.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-medium">{{ tag.name }}</td>
                                <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                                    {{ tag.slug }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ tag.services_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Editar"
                                            @click="openEdit(tag)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            aria-label="Eliminar"
                                            @click="deleteTag(tag.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!tags.data.length">
                                <td colspan="4" class="px-4 py-10 text-center text-muted-foreground">
                                    No hay tags. Cree uno arriba.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginator -->
                <div
                    v-if="tags.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row"
                >
                    <span>
                        Mostrando {{ tags.from }}–{{ tags.to }} de {{ tags.total }} tags
                    </span>
                    <nav class="flex gap-1" aria-label="Paginación">
                        <template v-for="link in tags.links" :key="link.label">
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

        <!-- Edit dialog — único, fuera del v-for -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar tag</DialogTitle>
                    <DialogDescription>
                        Modifique el nombre del tag.
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="space-y-2">
                        <Label for="edit-tag-name">Nombre</Label>
                        <Input
                            id="edit-tag-name"
                            v-model="editForm.name"
                            required
                            maxlength="100"
                            :class="editForm.errors.name ? 'border-destructive' : ''"
                        />
                        <InputError :message="editForm.errors.name" />
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
