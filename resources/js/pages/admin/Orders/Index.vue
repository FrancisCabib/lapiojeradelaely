<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { CheckCircle2, ClipboardList } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type OrderItem = {
    id: number;
    reference_code: string;
    customer_name: string;
    order_summary: string;
    scheduled_date: string;
    scheduled_date_label: string;
    scheduled_time: string;
    payment_method: string;
    payment_method_label: string;
    total_amount: number | null;
    deposit_amount: number | null;
    formatted_total: string | null;
    formatted_deposit: string | null;
    status: string;
    status_label: string;
    notes: string | null;
    is_custom: boolean;
    created_at: string | null;
};

type StatusOption = { value: string; label: string };

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedOrders = {
    data: OrderItem[];
    current_page: number;
    last_page: number;
    links: PaginatorLink[];
    total: number;
};

const props = defineProps<{
    orders: PaginatedOrders;
    statusFilter: string;
    statusOptions: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
    { title: 'Pedidos', href: '/admin/orders' },
];

const page = usePage();
const flashMessage = ref<string | null>(null);
let flashTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        flashMessage.value = msg;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(() => {
            flashMessage.value = null;
        }, 4000);
    },
    { immediate: true },
);

const statusBadgeClass = (status: string): string => {
    const map: Record<string, string> = {
        pending_deposit:
            'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
        confirmed:
            'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
        delivered:
            'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-200',
        cancelled:
            'bg-stone-200 text-stone-700 dark:bg-stone-800 dark:text-stone-300',
        no_show: 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200',
    };
    return map[status] ?? 'bg-stone-100 text-stone-800';
};

const handleFilterChange = (event: Event): void => {
    const target = event.target as HTMLSelectElement;
    router.get(
        '/admin/orders',
        { status: target.value || undefined },
        { preserveState: true, preserveScroll: true },
    );
};

const handleStatusUpdate = (orderId: number, status: string): void => {
    router.patch(`/admin/orders/${orderId}/status`, { status }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Pedidos" />

    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Pedidos y reservas"
                    description="Confirma adelantos por transferencia y gestiona el estado de cada pedido."
                />
                <div class="flex items-center gap-2">
                    <label
                        for="order-status-filter"
                        class="text-sm font-medium text-stone-600 dark:text-stone-400"
                    >
                        Filtrar
                    </label>
                    <select
                        id="order-status-filter"
                        class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-600 dark:bg-stone-900"
                        :value="statusFilter"
                        @change="handleFilterChange"
                    >
                        <option value="">Todos</option>
                        <option
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div
                v-if="flashMessage"
                class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
                role="status"
            >
                <CheckCircle2 class="h-4 w-4 shrink-0" />
                {{ flashMessage }}
            </div>

            <div
                v-if="orders.data.length === 0"
                class="rounded-xl border border-dashed border-stone-300 p-12 text-center dark:border-stone-600"
            >
                <ClipboardList
                    class="mx-auto mb-3 h-10 w-10 text-stone-400"
                    aria-hidden="true"
                />
                <p class="text-stone-600 dark:text-stone-400">
                    Aún no hay pedidos registrados desde el catálogo.
                </p>
            </div>

            <div v-else class="space-y-4">
                <article
                    v-for="order in orders.data"
                    :key="order.id"
                    class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-900"
                >
                    <div
                        class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <p class="font-mono text-sm font-semibold text-stone-900 dark:text-stone-100">
                                {{ order.reference_code }}
                            </p>
                            <h3 class="text-lg font-semibold">
                                {{ order.customer_name }}
                            </h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400">
                                {{ order.scheduled_date_label }} · {{ order.scheduled_time }}
                                · {{ order.payment_method_label }}
                            </p>
                        </div>
                        <span
                            class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold"
                            :class="statusBadgeClass(order.status)"
                        >
                            {{ order.status_label }}
                        </span>
                    </div>

                    <p
                        class="mb-3 whitespace-pre-wrap text-sm text-stone-700 dark:text-stone-300"
                    >
                        {{ order.order_summary }}
                    </p>

                    <div
                        class="mb-4 flex flex-wrap gap-3 text-sm text-stone-600 dark:text-stone-400"
                    >
                        <span v-if="order.formatted_total">
                            Total: <strong>{{ order.formatted_total }}</strong>
                        </span>
                        <span v-if="order.formatted_deposit">
                            Adelanto: <strong>{{ order.formatted_deposit }}</strong>
                        </span>
                        <span v-if="order.notes">Nota: {{ order.notes }}</span>
                        <span class="text-stone-400">Recibido: {{ order.created_at }}</span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="order.status === 'pending_deposit'"
                            size="sm"
                            class="bg-emerald-600 hover:bg-emerald-700"
                            @click="handleStatusUpdate(order.id, 'confirmed')"
                        >
                            Confirmar adelanto
                        </Button>
                        <Button
                            v-if="order.status === 'confirmed'"
                            size="sm"
                            variant="outline"
                            @click="handleStatusUpdate(order.id, 'delivered')"
                        >
                            Marcar entregado
                        </Button>
                        <Button
                            v-if="['pending_deposit', 'confirmed'].includes(order.status)"
                            size="sm"
                            variant="outline"
                            @click="handleStatusUpdate(order.id, 'no_show')"
                        >
                            No llegó
                        </Button>
                        <Button
                            v-if="order.status !== 'cancelled'"
                            size="sm"
                            variant="ghost"
                            class="text-red-600 hover:text-red-700"
                            @click="handleStatusUpdate(order.id, 'cancelled')"
                        >
                            Cancelar
                        </Button>
                    </div>
                </article>
            </div>
        </div>
    </AppSidebarLayout>
</template>
