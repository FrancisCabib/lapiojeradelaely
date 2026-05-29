<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    WHATSAPP_ORDER_STORAGE_KEYS,
    buildWhatsappOrderMessage,
    buildWhatsappUrl,
    calcDepositAmount,
    formatClp,
    loadStoredOrderField,
    saveStoredOrderField,
    submitOrder,
    todayIsoDate,
    type OrderSettings,
    type PaymentMethod,
} from '@/lib/whatsapp-order';
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

const props = defineProps<{
    open: boolean;
    phone: string;
    orderSummary: string;
    isCustom?: boolean;
    totalAmount?: number | null;
    orderSettings: OrderSettings;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const customerName = ref('');
const orderDate = ref('');
const orderTime = ref('');
const orderNotes = ref('');
const paymentMethod = ref<PaymentMethod>('transfer');
const submitError = ref<string | null>(null);
const isSubmitting = ref(false);

const notesLabel = computed(() =>
    props.isCustom
        ? 'Describe tu pedido personalizado'
        : 'Nota / horario especial (opcional)',
);

const notesRequired = computed(() => Boolean(props.isCustom));

const notesPlaceholder = computed(() =>
    props.isCustom
        ? 'Ej.: 2 completos sin cebolla para retirar el sábado'
        : 'Ej.: retiro en local, sin picante, etc.',
);

const depositAmount = computed(() =>
    calcDepositAmount(props.totalAmount, props.orderSettings.deposit_percent),
);

const bankLines = computed(() => {
    const bank = props.orderSettings.bank;
    return [
        bank.name ? `Banco: ${bank.name}` : null,
        bank.account_type ? `Tipo de cuenta: ${bank.account_type}` : null,
        bank.account_number ? `Número: ${bank.account_number}` : null,
        bank.holder ? `Titular: ${bank.holder}` : null,
        bank.rut ? `RUT: ${bank.rut}` : null,
        bank.email ? `Correo: ${bank.email}` : null,
    ].filter(Boolean) as string[];
});

const hydrateForm = (): void => {
    customerName.value = loadStoredOrderField(
        WHATSAPP_ORDER_STORAGE_KEYS.name,
    );
    orderDate.value =
        loadStoredOrderField(WHATSAPP_ORDER_STORAGE_KEYS.date) ||
        todayIsoDate();
    orderTime.value = loadStoredOrderField(
        WHATSAPP_ORDER_STORAGE_KEYS.time,
    );
    orderNotes.value = '';
    submitError.value = null;
    isSubmitting.value = false;

    const storedPayment = loadStoredOrderField(
        WHATSAPP_ORDER_STORAGE_KEYS.payment,
    );
    paymentMethod.value =
        storedPayment === 'transfer' ||
        storedPayment === 'card' ||
        storedPayment === 'cash'
            ? storedPayment
            : 'transfer';
};

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            hydrateForm();
        }
    },
);

const handleOpenChange = (value: boolean): void => {
    emit('update:open', value);
};

const handleSubmit = async (): Promise<void> => {
    const name = customerName.value.trim();
    const date = orderDate.value;
    const time = orderTime.value;
    const notes = orderNotes.value.trim();

    if (!name || !date || !time) {
        return;
    }

    if (props.isCustom && !notes) {
        return;
    }

    if (!props.isCustom && !props.orderSummary.trim()) {
        return;
    }

    saveStoredOrderField(WHATSAPP_ORDER_STORAGE_KEYS.name, name);
    saveStoredOrderField(WHATSAPP_ORDER_STORAGE_KEYS.date, date);
    saveStoredOrderField(WHATSAPP_ORDER_STORAGE_KEYS.time, time);
    saveStoredOrderField(WHATSAPP_ORDER_STORAGE_KEYS.payment, paymentMethod.value);

    isSubmitting.value = true;
    submitError.value = null;

    try {
        const created = await submitOrder({
            customer_name: name,
            order_summary: props.isCustom ? notes : props.orderSummary,
            scheduled_date: date,
            scheduled_time: time,
            payment_method: paymentMethod.value,
            total_amount: props.totalAmount ?? null,
            notes: props.isCustom ? undefined : notes || undefined,
            is_custom: props.isCustom,
        });

        const message = buildWhatsappOrderMessage({
            customerName: name,
            orderSummary: props.orderSummary,
            date,
            time,
            notes: props.isCustom ? notes : notes || undefined,
            isCustom: props.isCustom,
            paymentMethod: paymentMethod.value,
            referenceCode: created.order.reference_code,
            totalAmount: props.totalAmount ?? null,
            depositAmount:
                created.order.deposit_amount ?? depositAmount.value,
            depositPercent: props.orderSettings.deposit_percent,
        });

        window.open(
            buildWhatsappUrl(props.phone, message),
            '_blank',
            'noopener,noreferrer',
        );
        emit('update:open', false);
    } catch (error) {
        submitError.value =
            error instanceof Error
                ? error.message
                : 'No se pudo registrar la reserva.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Datos del pedido</DialogTitle>
                <DialogDescription>
                    Completa tus datos y confirma la reserva. El adelanto es obligatorio.
                </DialogDescription>
            </DialogHeader>

            <p
                v-if="submitError"
                class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100"
                role="alert"
            >
                {{ submitError }}
            </p>

            <p
                v-if="!isCustom && orderSummary"
                class="rounded-md border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-700 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300"
            >
                {{ orderSummary }}
            </p>
            <p
                v-else-if="isCustom"
                class="text-sm text-stone-600 dark:text-stone-400"
            >
                Cuéntanos qué quieres pedir fuera de la carta del día.
            </p>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div class="space-y-2">
                    <Label for="wa-order-name">Tu nombre</Label>
                    <Input
                        id="wa-order-name"
                        v-model="customerName"
                        type="text"
                        required
                        autocomplete="name"
                        placeholder="Ej.: María"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="wa-order-date">Fecha</Label>
                        <Input
                            id="wa-order-date"
                            v-model="orderDate"
                            type="date"
                            required
                            :min="todayIsoDate()"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="wa-order-time">Hora</Label>
                        <Input
                            id="wa-order-time"
                            v-model="orderTime"
                            type="time"
                            required
                        />
                    </div>
                </div>

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium">Forma de pago</legend>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="paymentMethod" type="radio" value="transfer" />
                        Transferencia (adelanto)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="paymentMethod" type="radio" value="cash" />
                        Efectivo (adelanto al retirar)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="paymentMethod" type="radio" value="card" />
                        Tarjeta (adelanto al retirar)
                    </label>
                </fieldset>

                <div
                    class="rounded-md border border-dashed border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                >
                    <p class="mb-2 font-semibold">Adelanto para confirmar la reserva</p>
                    <p v-if="depositAmount != null" class="mb-2 font-medium">
                        Adelanto obligatorio ({{ orderSettings.deposit_percent }}%):
                        {{ formatClp(depositAmount) }}
                    </p>
                    <p v-else class="mb-2">
                        El monto del adelanto se confirmará contigo por WhatsApp.
                    </p>

                    <template v-if="paymentMethod === 'transfer'">
                        <p class="mb-2 font-medium">Datos para transferir</p>
                        <ul v-if="bankLines.length" class="list-disc space-y-1 pl-5">
                            <li v-for="line in bankLines" :key="line">{{ line }}</li>
                        </ul>
                        <p v-else class="text-amber-900/80 dark:text-amber-100/80">
                            Consulta los datos de transferencia con el local por WhatsApp.
                        </p>
                        <p class="mt-2 text-xs">
                            Después de transferir, envía el comprobante por WhatsApp.
                        </p>
                    </template>
                    <p v-else-if="paymentMethod === 'cash'" class="text-xs">
                        Debes pagar el adelanto en efectivo en el local para confirmar la reserva.
                    </p>
                    <p v-else class="text-xs">
                        Debes pagar el adelanto con tarjeta en el local para confirmar la reserva.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="wa-order-notes">{{ notesLabel }}</Label>
                    <textarea
                        id="wa-order-notes"
                        v-model="orderNotes"
                        rows="3"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :required="notesRequired"
                        :placeholder="notesPlaceholder"
                    />
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleOpenChange(false)"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        class="bg-[#25D366] text-white hover:bg-[#20bd5a]"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Registrando…' : 'Confirmar y enviar por WhatsApp' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
