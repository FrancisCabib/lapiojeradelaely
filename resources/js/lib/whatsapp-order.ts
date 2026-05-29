export type PaymentMethod = 'cash' | 'transfer' | 'card';

export type BankSettings = {
    name: string;
    account_type: string;
    account_number: string;
    holder: string;
    rut: string;
    email: string;
};

export type OrderSettings = {
    deposit_percent: number;
    whatsapp_phone: string | null;
    bank: BankSettings;
};

export type WhatsappOrderMessageInput = {
    customerName: string;
    orderSummary: string;
    date: string;
    time: string;
    notes?: string;
    isCustom?: boolean;
    paymentMethod?: PaymentMethod;
    referenceCode?: string;
    totalAmount?: number | null;
    depositAmount?: number | null;
    depositPercent?: number;
};

export type CreateOrderPayload = {
    customer_name: string;
    order_summary: string;
    scheduled_date: string;
    scheduled_time: string;
    payment_method: PaymentMethod;
    total_amount?: number | null;
    notes?: string;
    is_custom?: boolean;
};

export type CreateOrderResponse = {
    order: {
        reference_code: string;
        deposit_amount: number | null;
        deposit_percent: number;
        total_amount: number | null;
        status: string;
        status_label: string;
    };
};

export const formatClp = (amount: number): string =>
    `$${amount.toLocaleString('es-CL')}`;

export const formatDateChile = (isoDate: string): string => {
    const [year, month, day] = isoDate.split('-');
    if (!year || !month || !day) {
        return isoDate;
    }
    return `${day}/${month}/${year}`;
};

const paymentLabel = (method: PaymentMethod): string => {
    const labels: Record<PaymentMethod, string> = {
        cash: 'Efectivo (adelanto y saldo al retirar)',
        transfer: 'Transferencia',
        card: 'Tarjeta (adelanto y saldo al retirar en el local)',
    };
    return labels[method];
};

export const buildWhatsappOrderMessage = ({
    customerName,
    orderSummary,
    date,
    time,
    notes,
    isCustom = false,
    paymentMethod = 'transfer',
    referenceCode,
    totalAmount,
    depositAmount,
    depositPercent,
}: WhatsappOrderMessageInput): string => {
    const name = customerName.trim();
    const dateLabel = formatDateChile(date);
    const lines: string[] = [`Hola, soy ${name}.`, ''];

    if (referenceCode) {
        lines.push(`Reserva: ${referenceCode}`, '');
    }

    if (isCustom) {
        lines.push(`Pedido personalizado: ${notes?.trim() ?? ''}`, '');
    } else {
        lines.push(orderSummary.trim(), '');
        const noteText = notes?.trim();
        if (noteText && !isCustom) {
            lines.push(`Nota / pedido especial: ${noteText}`, '');
        }
    }

    lines.push(`Fecha: ${dateLabel}`, `Hora: ${time}`);
    lines.push(`Forma de pago: ${paymentLabel(paymentMethod)}`);

    if (totalAmount != null && totalAmount > 0) {
        lines.push(`Total estimado: ${formatClp(totalAmount)}`);
    }

    const pct = depositPercent ?? 50;
    if (depositAmount != null && depositAmount > 0) {
        lines.push(`Adelanto obligatorio (${pct}%): ${formatClp(depositAmount)}`);
    } else {
        lines.push(`Adelanto obligatorio (${pct}%): a confirmar contigo.`);
    }

    if (paymentMethod === 'transfer') {
        lines.push('Enviaré comprobante de transferencia por este WhatsApp.');
    } else if (paymentMethod === 'cash') {
        lines.push('Pagaré el adelanto en efectivo en el local.');
    } else {
        lines.push('Pagaré el adelanto con tarjeta en el local.');
    }

    lines.push('La reserva queda sujeta a confirmación del adelanto.');

    return lines.join('\n');
};

export const buildWhatsappUrl = (phone: string, message: string): string => {
    const digits = phone.replace(/\D+/g, '');
    return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
};

export const calcDepositAmount = (
    totalAmount: number | null | undefined,
    depositPercent: number,
): number | null => {
    if (totalAmount == null || totalAmount <= 0) {
        return null;
    }
    if (depositPercent <= 0) {
        return null;
    }
    return Math.round((totalAmount * depositPercent) / 100);
};

export const WHATSAPP_ORDER_STORAGE_KEYS = {
    name: 'wa-order-name',
    date: 'wa-order-date',
    time: 'wa-order-time',
    payment: 'wa-order-payment',
} as const;

export const todayIsoDate = (): string => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

export const loadStoredOrderField = (key: string): string => {
    try {
        return sessionStorage.getItem(key) ?? '';
    } catch {
        return '';
    }
};

export const saveStoredOrderField = (key: string, value: string): void => {
    try {
        if (value) {
            sessionStorage.setItem(key, value);
        } else {
            sessionStorage.removeItem(key);
        }
    } catch {
        // ignore storage errors
    }
};

export const submitOrder = async (
    payload: CreateOrderPayload,
): Promise<CreateOrderResponse> => {
    const response = await fetch('/api/orders', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const body = await response.json().catch(() => null);
        const message =
            body?.message ||
            body?.errors?.customer_name?.[0] ||
            'No se pudo registrar la reserva. Intenta de nuevo.';
        throw new Error(message);
    }

    return response.json() as Promise<CreateOrderResponse>;
};
