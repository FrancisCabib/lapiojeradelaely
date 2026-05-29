import { ref } from 'vue';

export type ConfirmOptions = {
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
};

// Module-level singleton — state shared across all callers
const isOpen = ref(false);
const dialogOptions = ref<ConfirmOptions>({ title: '' });
let resolveCallback: ((value: boolean) => void) | null = null;

export function useConfirm() {
    function confirm(options: ConfirmOptions): Promise<boolean> {
        dialogOptions.value = options;
        isOpen.value = true;

        return new Promise<boolean>((resolve) => {
            resolveCallback = resolve;
        });
    }

    function onConfirm() {
        isOpen.value = false;
        resolveCallback?.(true);
        resolveCallback = null;
    }

    function onCancel() {
        isOpen.value = false;
        resolveCallback?.(false);
        resolveCallback = null;
    }

    return { confirm, isOpen, dialogOptions, onConfirm, onCancel };
}
