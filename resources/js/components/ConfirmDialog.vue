<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';

const { isOpen, dialogOptions, onConfirm, onCancel } = useConfirm();

// When the dialog closes by Escape or backdrop click → resolve as cancel
const open = computed({
    get: () => isOpen.value,
    set: (v) => { if (!v) onCancel(); },
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent :show-close-button="false" class="sm:max-w-sm">
            <DialogHeader class="flex flex-col items-center gap-3 text-center">
                <div
                    class="flex size-14 items-center justify-center rounded-full"
                    :class="dialogOptions.variant === 'destructive'
                        ? 'bg-red-100 dark:bg-red-900/30'
                        : 'bg-primary/10'"
                >
                    <Trash2
                        v-if="dialogOptions.variant === 'destructive'"
                        class="size-6 text-destructive"
                    />
                    <AlertTriangle
                        v-else
                        class="size-6 text-primary"
                    />
                </div>

                <div class="space-y-1">
                    <DialogTitle class="text-base">
                        {{ dialogOptions.title }}
                    </DialogTitle>
                    <DialogDescription v-if="dialogOptions.description">
                        {{ dialogOptions.description }}
                    </DialogDescription>
                </div>
            </DialogHeader>

            <DialogFooter class="mt-2 flex-col-reverse gap-2 sm:flex-row sm:gap-2">
                <Button
                    variant="outline"
                    class="w-full sm:w-auto"
                    @click="onCancel"
                >
                    {{ dialogOptions.cancelText ?? 'Cancelar' }}
                </Button>
                <Button
                    :variant="dialogOptions.variant === 'destructive' ? 'destructive' : 'default'"
                    class="w-full sm:w-auto"
                    @click="onConfirm"
                >
                    {{ dialogOptions.confirmText ?? 'Confirmar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
