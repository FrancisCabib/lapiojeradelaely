<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import WhatsappOrderDialog from '@/components/catalog/WhatsappOrderDialog.vue';
import { home } from '@/routes';
import admin from '@/routes/admin';

type CatalogTag = { id: number; name: string; slug: string };

type CatalogService = {
    id: number;
    title: string;
    subtitle: string | null;
    short_description: string;
    long_description: string | null;
    price: number | null;
    price_formatted: string | null;
    image_url: string | null;
    tags: CatalogTag[];
};

type CatalogCourse = {
    menu_heading: string;
    menu_course: string | null;
    service: CatalogService | null;
};

type WeekdaySection = {
    id: number;
    name: string;
    slug: string;
    weekday: number | null;
    description: string | null;
    courses: CatalogCourse[];
};

const props = defineProps<{
    weekdays: WeekdaySection[];
    orderSettings: {
        deposit_percent: number;
        whatsapp_phone: string | null;
        bank: {
            name: string;
            account_type: string;
            account_number: string;
            holder: string;
            rut: string;
            email: string;
        };
    };
}>();

const page = usePage();
const hasAuth = !!page.props.auth?.user;
const appName =
    (page.props.app as { name?: string } | undefined)?.name || 'La Piojera de la Ely';

const hasAnyDish = props.weekdays.some((d) =>
    d.courses.some((c) => c.service !== null),
);

const whatsappOrderPhone = computed((): string | null => {
    const p = page.props as Record<string, unknown>;
    const raw = p.whatsappOrderPhone ?? p.whatsapp_order_phone;
    if (raw === null || raw === undefined || raw === '') {
        return null;
    }
    return String(raw);
});

const hasWhatsapp = computed(
    () => Boolean(whatsappOrderPhone.value && whatsappOrderPhone.value.length),
);

const orderDialogOpen = ref(false);
const orderSummary = ref('');
const orderIsCustom = ref(false);
const orderTotalAmount = ref<number | null>(null);

const openOrderDialog = (
    summary: string,
    isCustom = false,
    totalAmount: number | null = null,
): void => {
    orderSummary.value = summary;
    orderIsCustom.value = isCustom;
    orderTotalAmount.value = totalAmount;
    orderDialogOpen.value = true;
};

function mealOrderMessage(
    day: WeekdaySection,
    course: CatalogCourse,
): string {
    const s = course.service;
    if (!s) {
        return '';
    }
    const price = s.price_formatted ? ` (${s.price_formatted})` : '';
    return `Quiero pedir el ${day.name} — ${course.menu_heading}: ${s.title}${price}.`;
}

function dayOrderMessage(day: WeekdaySection): string {
    const lines = day.courses
        .filter((c): c is CatalogCourse & { service: CatalogService } => c.service !== null)
        .map((c) => {
            const p = c.service.price_formatted
                ? ` — ${c.service.price_formatted}`
                : '';
            return `• ${c.menu_heading}: ${c.service.title}${p}`;
        });
    if (lines.length === 0) {
        return '';
    }
    return `Quiero pedir el menú del ${day.name}:\n\n${lines.join('\n')}`;
}

function dayOrderTotal(day: WeekdaySection): number {
    return day.courses.reduce((sum, course) => {
        if (!course.service?.price) {
            return sum;
        }
        return sum + course.service.price;
    }, 0);
}

function coursesWithServiceCount(day: WeekdaySection): number {
    return day.courses.filter((c) => c.service !== null).length;
}
</script>

<template>
    <Head title="Carta de la semana" />

    <div
        class="min-h-screen bg-stone-50 text-stone-900 dark:bg-stone-950 dark:text-stone-100"
    >
        <header
            class="sticky top-0 z-10 border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-800 dark:bg-stone-950/90"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="text-lg font-semibold text-stone-900 hover:text-stone-600 dark:text-stone-100 dark:hover:text-stone-300"
                >
                    {{ appName }}
                </Link>
                <nav class="flex items-center gap-4">
                    <Link
                        :href="admin.services.index().url"
                        v-if="hasAuth"
                        class="rounded-md border border-stone-300 px-3 py-1.5 text-sm font-medium transition hover:bg-stone-100 dark:border-stone-600 dark:hover:bg-stone-800"
                    >
                        Administrar
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <h1 class="mb-2 text-3xl font-bold tracking-tight sm:text-4xl">
                Carta de la semana
            </h1>
            <p class="mb-12 text-stone-600 dark:text-stone-400">
                Cada día tiene su menú: desayuno, almuerzo y cena. Las fotos las
                puedes cargar después desde administración.
            </p>

            <div
                v-if="weekdays.length === 0 || !hasAnyDish"
                class="rounded-xl border border-dashed border-stone-300 p-12 text-center text-stone-500 dark:border-stone-600"
            >
                <p class="text-lg">
                    Aún no hay menús por día publicados. Carga secciones y platos
                    desde el panel o ejecuta el seeder de carta semanal.
                </p>
            </div>

            <div v-else class="space-y-16">
                <section
                    v-for="day in weekdays"
                    :key="day.id"
                    class="scroll-mt-20 border-b border-stone-200 pb-14 last:border-b-0 dark:border-stone-800"
                >
                    <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight">
                                {{ day.name }}
                            </h2>
                            <p
                                v-if="day.description"
                                class="mt-1 text-stone-600 dark:text-stone-400"
                            >
                                {{ day.description }}
                            </p>
                        </div>
                        <button
                            v-if="
                                hasWhatsapp &&
                                coursesWithServiceCount(day) >= 2
                            "
                            type="button"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 dark:focus:ring-offset-stone-950"
                            :aria-label="`Pedir por WhatsApp el menú completo de ${day.name}`"
                            @click="
                                openOrderDialog(
                                    dayOrderMessage(day),
                                    false,
                                    dayOrderTotal(day) || null,
                                )
                            "
                        >
                            <span
                                class="inline-block h-4 w-4"
                                aria-hidden="true"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-full w-full"
                                >
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.123 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                    />
                                </svg>
                            </span>
                            Pedir el menú del día
                        </button>
                    </div>

                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="course in day.courses"
                            :key="`${day.id}-${course.menu_course ?? course.menu_heading}`"
                            class="group overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md dark:border-stone-700 dark:bg-stone-900"
                        >
                            <div
                                v-if="course.service?.image_url"
                                class="aspect-video overflow-hidden bg-stone-100 dark:bg-stone-800"
                            >
                                <img
                                    :src="course.service.image_url"
                                    :alt="course.service.title"
                                    class="h-full w-full object-cover transition group-hover:scale-105"
                                />
                            </div>
                            <div
                                v-else
                                class="flex aspect-video items-center justify-center bg-stone-100 text-sm text-stone-400 dark:bg-stone-800 dark:text-stone-500"
                            >
                                Sin imagen
                            </div>
                            <div class="p-5">
                                <p
                                    class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-500"
                                >
                                    {{ course.menu_heading }}
                                </p>
                                <template v-if="course.service">
                                    <div class="mb-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="tag in course.service.tags"
                                            :key="tag.id"
                                            class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-medium text-stone-600 dark:bg-stone-700 dark:text-stone-300"
                                        >
                                            {{ tag.name }}
                                        </span>
                                    </div>
                                    <h3 class="mb-1 text-xl font-semibold">
                                        {{ course.service.title }}
                                    </h3>
                                    <p
                                        v-if="course.service.subtitle"
                                        class="mb-2 text-sm text-stone-600 dark:text-stone-400"
                                    >
                                        {{ course.service.subtitle }}
                                    </p>
                                    <p
                                        v-if="course.service.price_formatted"
                                        class="mb-2 font-medium text-stone-900 dark:text-stone-100"
                                    >
                                        {{ course.service.price_formatted }}
                                    </p>
                                    <button
                                        v-if="hasWhatsapp"
                                        type="button"
                                        class="mb-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#25D366] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#20bd5a] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 dark:focus:ring-offset-stone-900"
                                        :aria-label="`Pedir por WhatsApp: ${course.service.title}`"
                                        @click="
                                            openOrderDialog(
                                                mealOrderMessage(day, course),
                                                false,
                                                course.service.price,
                                            )
                                        "
                                    >
                                        <span
                                            class="inline-block h-4 w-4 shrink-0"
                                            aria-hidden="true"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                                class="h-full w-full"
                                            >
                                                <path
                                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.123 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                                />
                                            </svg>
                                        </span>
                                        Pedir por WhatsApp
                                    </button>
                                    <p
                                        class="text-sm text-stone-600 line-clamp-4 dark:text-stone-400"
                                    >
                                        {{ course.service.short_description }}
                                    </p>
                                </template>
                                <p
                                    v-else
                                    class="text-sm text-stone-500 dark:text-stone-400"
                                >
                                    Menú no cargado para esta comida.
                                </p>
                            </div>
                        </article>
                    </div>
                </section>

                <div
                    v-if="hasWhatsapp"
                    class="flex justify-center pt-2"
                >
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-800 transition hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:ring-offset-2 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:hover:bg-stone-800 dark:focus:ring-offset-stone-950"
                        @click="openOrderDialog('', true)"
                    >
                        Pedido personalizado
                    </button>
                </div>
            </div>
        </main>

        <WhatsappOrderDialog
            v-if="hasWhatsapp && whatsappOrderPhone"
            v-model:open="orderDialogOpen"
            :phone="whatsappOrderPhone"
            :order-summary="orderSummary"
            :is-custom="orderIsCustom"
            :total-amount="orderTotalAmount"
            :order-settings="orderSettings"
        />
    </div>
</template>
