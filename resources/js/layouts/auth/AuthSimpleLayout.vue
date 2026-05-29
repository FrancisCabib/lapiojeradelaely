<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10"
    >
        <!-- Fondo degradado violeta → azul -->
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-violet-700 via-indigo-600 to-blue-600" />

        <!-- Manchas de luz decorativas -->
        <div class="absolute -top-40 -left-40 -z-10 h-[500px] w-[500px] rounded-full bg-violet-400/25 blur-3xl" />
        <div class="absolute -bottom-40 -right-40 -z-10 h-[500px] w-[500px] rounded-full bg-blue-400/25 blur-3xl" />
        <div class="absolute top-1/2 left-1/4 -z-10 h-64 w-64 -translate-y-1/2 rounded-full bg-indigo-400/15 blur-2xl" />

        <!-- Tarjeta -->
        <div class="w-full max-w-sm rounded-2xl bg-white/95 p-8 shadow-2xl ring-1 ring-white/20 backdrop-blur-sm dark:bg-gray-900/90">
            <div class="flex flex-col gap-7">

                <!-- ── LOGO DEL SISTEMA ── -->
                <div class="flex flex-col items-center gap-4">
                    <!-- Ícono + nombre del sistema -->
                    <div class="flex flex-col items-center gap-2">
                        <!-- Contenedor del ícono -->
                        <div class="relative flex h-20 w-20 items-center justify-center">
                            <!-- Halo exterior -->
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-violet-500/20 to-blue-500/20 blur-md" />
                            <!-- Caja del ícono -->
                            <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-blue-600 shadow-lg shadow-violet-500/40">
                                <!-- Ícono: páginas de catálogo / brochure -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="size-8 text-white"
                                >
                                    <!-- Libro abierto con marcador -->
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                                    <!-- Destellos en las páginas -->
                                    <path d="M6 8h2" stroke-width="1.5" />
                                    <path d="M6 11h2" stroke-width="1.5" />
                                    <path d="M16 8h2" stroke-width="1.5" />
                                    <path d="M16 11h2" stroke-width="1.5" />
                                </svg>
                            </div>
                        </div>

                        <!-- Nombre del sistema -->
                        <div class="text-center">
                            <h2 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                La Piojera
                                <span class="bg-gradient-to-r from-violet-600 to-blue-600 bg-clip-text text-transparent">
                                    de la Ely
                                </span>
                            </h2>
                            <p class="mt-0.5 text-[11px] font-medium uppercase tracking-widest text-gray-400">
                                Carta digital · Tierra Amarilla
                            </p>
                        </div>
                    </div>

                    <!-- Separador -->
                    <div class="h-px w-full bg-gradient-to-r from-transparent via-gray-200 to-transparent dark:via-gray-700" />

                    <!-- Título y descripción de la acción -->
                    <div class="space-y-1 text-center">
                        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <!-- ── FIN LOGO ── -->

                <slot />
            </div>
        </div>

        <!-- Pie de página -->
        <p class="mt-6 text-xs text-white/50">
            La Piojera de la Ely &copy; {{ new Date().getFullYear() }}
        </p>
    </div>
</template>
