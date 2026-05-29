<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

type Tag = { id: number; name: string };
type Category = { id: number; name: string };

type Service = {
    id?: number;
    category_id: number;
    title: string;
    subtitle?: string;
    price?: number | null;
    short_description: string;
    long_description?: string;
    image_url?: string | null;
    is_active: boolean;
    sort_order: number;
    tag_id?: number | null;
};

type Props = {
    service: Service | null;
    tags: Tag[];
    categories: Category[];
};

const props = defineProps<Props>();

const isEditing = !!props.service;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
    { title: 'Servicios', href: admin.services.index() },
    {
        title: isEditing ? 'Editar servicio' : 'Nuevo servicio',
        href: isEditing && props.service?.id
            ? admin.services.edit(props.service.id)
            : admin.services.create(),
    },
];
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Editar servicio' : 'Nuevo servicio'" />

        <div class="space-y-6">
            <Heading
                :title="isEditing ? 'Editar servicio' : 'Crear servicio'"
                description="Complete los datos del servicio"
            />

            <Form
                :action="isEditing && service?.id ? admin.services.update.url(service.id) : admin.services.store.url()"
                method="post"
                enctype="multipart/form-data"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <input
                    v-if="isEditing"
                    type="hidden"
                    name="_method"
                    value="PUT"
                />

                <div class="space-y-2">
                    <Label for="category">Categoría *</Label>
                    <select
                        id="category"
                        name="category_id"
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option
                            value=""
                            disabled
                            :selected="!service?.category_id"
                        >
                            Seleccione una categoría
                        </option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                            :selected="service?.category_id === category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <p v-if="!categories.length" class="text-sm text-muted-foreground">
                        No hay categorías. Cree categorías en la sección de gestión.
                    </p>
                    <div>
                        <Link :href="admin.categories.index()" class="text-sm text-primary hover:underline">
                            Gestionar categorías
                        </Link>
                    </div>
                    <InputError :message="errors.category_id" />
                </div>

                <div class="space-y-2">
                    <Label for="title">Título *</Label>
                    <Input
                        id="title"
                        name="title"
                        :default-value="service?.title"
                        required
                        maxlength="255"
                        placeholder="Nombre del servicio"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="subtitle">Subtítulo</Label>
                    <Input
                        id="subtitle"
                        name="subtitle"
                        :default-value="service?.subtitle"
                        maxlength="255"
                        placeholder="Subtítulo opcional"
                    />
                    <InputError :message="errors.subtitle" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="price">
                            Precio
                            <span class="ml-1 text-xs font-normal text-muted-foreground">(CLP, opcional)</span>
                        </Label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">$</span>
                            <Input
                                id="price"
                                name="price"
                                type="number"
                                step="1"
                                min="0"
                                class="pl-7"
                                :default-value="service?.price ?? undefined"
                                placeholder="Ej: 15000"
                            />
                        </div>
                        <InputError :message="errors.price" />
                    </div>

                    <div class="space-y-2">
                        <Label for="sort_order">Orden de aparición</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            :default-value="service?.sort_order ?? 0"
                        />
                        <InputError :message="errors.sort_order" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="short_description">Descripción corta *</Label>
                    <textarea
                        id="short_description"
                        name="short_description"
                        rows="2"
                        required
                        maxlength="500"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Descripción breve del servicio"
                        :value="service?.short_description ?? ''"
                    ></textarea>
                    <InputError :message="errors.short_description" />
                </div>

                <div class="space-y-2">
                    <Label for="long_description">Descripción larga</Label>
                    <textarea
                        id="long_description"
                        name="long_description"
                        rows="4"
                        class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Descripción detallada opcional"
                        :value="service?.long_description ?? ''"
                    ></textarea>
                    <InputError :message="errors.long_description" />
                </div>

                <div class="space-y-2">
                    <Label for="image">Imagen principal</Label>
                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/*"
                        class="block w-full max-w-xs text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground file:hover:bg-primary/90"
                    />
                    <p class="text-xs text-muted-foreground">
                        Formatos: JPG, PNG, GIF. Máx. 2MB.
                    </p>
                    <img
                        v-if="service?.image_url"
                        :src="service.image_url"
                        :alt="service.title"
                        class="mt-2 h-24 rounded-md object-cover"
                    />
                    <InputError :message="errors.image" />
                </div>

                <div class="space-y-2">
                    <Label for="tag_id">Tag</Label>
                    <select
                        id="tag_id"
                        name="tag_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option :selected="!service?.tag_id" value="">Sin tag</option>
                        <option
                            v-for="tag in tags"
                            :key="tag.id"
                            :value="tag.id"
                            :selected="service?.tag_id === tag.id"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                    <p v-if="!tags.length" class="text-sm text-muted-foreground">
                        No hay tags. Cree tags en la sección de gestión.
                    </p>
                    <InputError :message="errors.tag_id" />
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            :checked="service?.is_active ?? true"
                            value="1"
                        />
                        <Label for="is_active" class="text-sm font-normal">
                            Servicio activo (visible en catálogo)
                        </Label>
                    </div>
                </div>

                <div class="flex gap-4">
                    <Button type="submit" :disabled="processing">
                        {{ isEditing ? 'Guardar cambios' : 'Crear servicio' }}
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="admin.services.index()">Cancelar</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppSidebarLayout>
</template>
