<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Bienvenido de vuelta"
        description="Ingresa tus credenciales para acceder al panel"
    >
        <Head title="Iniciar sesión" />

        <div
            v-if="status"
            class="mb-2 rounded-lg bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-700"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label for="email" class="text-gray-700 dark:text-gray-300">
                        Correo electrónico
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="tu@correo.com"
                        class="focus-visible:ring-violet-500"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-gray-700 dark:text-gray-300">
                            Contraseña
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-violet-600 hover:text-violet-700"
                            :tabindex="5"
                        >
                            ¿Olvidaste tu contraseña?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="focus-visible:ring-violet-500"
                    />
                    <InputError :message="errors.password" />
                </div>

                <Label for="remember" class="flex cursor-pointer items-center gap-2.5">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Recordar sesión
                    </span>
                </Label>
            </div>

            <Button
                type="submit"
                class="w-full bg-gradient-to-r from-violet-600 to-blue-600 font-semibold text-white shadow-md hover:from-violet-700 hover:to-blue-700 focus-visible:ring-violet-500"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                {{ processing ? 'Ingresando...' : 'Iniciar sesión' }}
            </Button>

            <div
                v-if="canRegister"
                class="text-center text-sm text-gray-500"
            >
                ¿No tienes cuenta?
                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="font-medium text-violet-600 hover:text-violet-700"
                >
                    Regístrate
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
