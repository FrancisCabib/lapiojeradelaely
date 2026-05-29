# La Piojera de la Ely — Resumen de desarrollo

Documento con lo implementado en el catálogo digital de **La Piojera de la Ely** (Tierra Amarilla, Región de Atacama).

---

## 1. Visión general

Proyecto de **carta semanal digital** con:

- **Backend:** Laravel 12 + Inertia/Vue (panel de administración y API).
- **Catálogo público principal:** Astro SSR en `astro-catalog/` (`http://localhost:4321`).
- **Menú:** Lunes a Domingo, con Desayuno, Almuerzo y Cena por día.
- **Pedidos:** formulario en la carta → registro en base de datos → WhatsApp con mensaje armado.

Marca unificada: **La Piojera de la Ely** (reemplazó referencias genéricas tipo “Brochure Digital”).

---

## 2. Localización e idioma

| Cambio | Detalle |
|--------|---------|
| Ubicación | Tierra Amarilla, Región de Atacama (hero, footer, seeders, textos) |
| Idioma | Español de Chile (tú, sin voseo: “consulta”, “puedes”, etc.) |
| Locale Laravel | `APP_LOCALE=es_CL`, `APP_FAKER_LOCALE=es_CL` |
| Referencias eliminadas | Santiago, Coquimbo y textos genéricos de otras regiones |

---

## 3. Catálogo público (Astro)

**Ruta local:** `http://localhost:4321`  
**API:** `GET /api/catalog-layout` (Laravel)

### Diseño
Estética **picada chilena** (papel kraft, tinta roja, pizarra, mantel a cuadros, tipografías DM Serif Display, Mansalva, Special Elite, Lora). El diseño de la carta **no se alteró** al agregar pedidos; solo se añadieron modales y botones sobre el estilo existente.

### Botones de pedido
- **Pedir por WhatsApp** en cada plato.
- **Pedir menú del día** cuando el día tiene 2+ comidas cargadas.
- **Pedido personalizado** en el footer (fuera de la carta del día).

### Archivos principales
- `astro-catalog/src/pages/index.astro`
- `astro-catalog/src/components/ServiceCard.astro`
- `astro-catalog/src/components/SectionBlock.astro`
- `astro-catalog/src/components/OrderWhatsappModal.astro`
- `astro-catalog/src/lib/whatsapp-order-modal.ts`
- `astro-catalog/src/lib/whatsapp-order-message.ts`
- `astro-catalog/src/lib/submit-order.ts`

---

## 4. Catálogo alternativo (Laravel / Inertia)

**Ruta:** `/catalog`

Misma lógica de pedidos con el componente Vue `WhatsappOrderDialog.vue` y la página `Catalog.vue`. Útil para pruebas o si se usa el build Inertia en lugar de Astro.

---

## 5. Flujo de pedido (evolución)

### Etapa 1 — Enlace directo a WhatsApp
Los botones abrían `wa.me` con un mensaje prellenado, sin formulario.

### Etapa 2 — Formulario antes de WhatsApp
Modal con:
- Nombre
- Fecha
- Hora
- Notas / pedido personalizado

Nombre, fecha y hora se **recuerdan en la sesión** del navegador (`sessionStorage`) para no repetirlos en cada plato.

### Etapa 3 — Reservas con adelanto + panel admin (opción 2+)

#### Formulario ampliado
- **Forma de pago:** transferencia, efectivo o tarjeta.
- **Adelanto obligatorio** en todos los pedidos (por defecto **50%** del total).
- Panel de adelanto siempre visible con monto calculado.
- Si elige **transferencia:** datos bancarios + instrucción de enviar comprobante por WhatsApp.
- Si elige **efectivo** o **tarjeta:** indicación de pagar el adelanto en el local.

#### Registro en base de datos
Al confirmar:
1. `POST /api/orders` crea la reserva.
2. Se genera código **`PED-YYMMDD-XXXX`** (ej. `PED-260528-A1B2`).
3. Se abre WhatsApp con el mensaje completo (reserva, pago, adelanto, fecha, hora).

#### Estados de la reserva

| Estado | Significado |
|--------|-------------|
| `pending_deposit` | Pendiente de adelanto (estado inicial de todos los pedidos) |
| `confirmed` | Adelanto confirmado por Ely |
| `delivered` | Pedido entregado |
| `no_show` | Cliente no llegó |
| `cancelled` | Cancelado |

---

## 6. Panel de administración

### Sección nueva: **Pedidos**
- Ruta: `/admin/orders`
- Menú lateral: ítem **Pedidos**
- Listado con filtro por estado
- Acciones: **Confirmar adelanto**, **Marcar entregado**, **No llegó**, **Cancelar**

### Secciones existentes
- Servicios, Etiquetas, Categorías, Secciones (carta semanal)

### Autenticación
Laravel Fortify. Usuario registrado en la instalación actual: `francis.cabib06@gmail.com`.

---

## 7. API

| Método | Ruta | Uso |
|--------|------|-----|
| `GET` | `/api/catalog-layout` | Layout completo para Astro (incluye `order_settings`) |
| `POST` | `/api/orders` | Crear reserva desde el catálogo (throttle 20/min) |

### Respuesta `order_settings` (en catalog-layout)
- `deposit_percent`
- `whatsapp_phone`
- `bank` (nombre, tipo cuenta, número, titular, RUT, email)

---

## 8. Backend — archivos nuevos

```
app/Models/Order.php
app/Enums/OrderStatus.php
app/Enums/PaymentMethod.php
app/Support/OrderSettings.php
app/Actions/Orders/StoreOrderAction.php
app/Http/Controllers/Api/OrderController.php
app/Http/Controllers/Admin/OrderController.php
app/Http/Requests/Api/StoreOrderRequest.php
app/Http/Requests/Admin/UpdateOrderStatusRequest.php
database/migrations/2026_05_28_000001_create_orders_table.php
resources/js/pages/admin/Orders/Index.vue
resources/js/components/catalog/WhatsappOrderDialog.vue
resources/js/lib/whatsapp-order.ts
```

---

## 9. Menú de datos (seeders)

Carta semanal alineada con la hoja impresa de Ely:

| Comida | Precio |
|--------|--------|
| Desayuno | $1.500 |
| Almuerzo | $3.000 |
| Cena | $3.000 |

Seeders:
- `WeekdayMenuSectionsSeeder` — 7 días
- `WeekdayMenuCoursesSeeder` — desayuno / almuerzo / cena por día
- `WeekdayMenuDishesSeeder` — platos y precios

Comando para recargar platos:

```bash
php artisan db:seed --class=WeekdayMenuDishesSeeder
```

---

## 10. Variables de entorno

### Laravel (`.env`)

```env
WHATSAPP_ORDER_PHONE=56988132247
RESERVATION_DEPOSIT_PERCENT=50

BANK_NAME=
BANK_ACCOUNT_TYPE=
BANK_ACCOUNT_NUMBER=
BANK_ACCOUNT_HOLDER=
BANK_ACCOUNT_RUT=
BANK_ACCOUNT_EMAIL=

CORS_ALLOWED_ORIGINS=http://localhost:4321
APP_LOCALE=es_CL
```

Número WhatsApp: **+56 9 8813 2247** → `56988132247` (solo dígitos, sin `+`).

### Astro (`astro-catalog/.env`)

```env
CATALOG_API_URL=http://la-piojera-de-la-ely.test/api/catalog-layout
WHATSAPP_ORDER_PHONE=56988132247
CATALOG_LOCATION_LINE=Tierra Amarilla · Región de Atacama
```

Tras cambiar `.env` en Astro: **reiniciar** `npm run dev` en `astro-catalog/`.

---

## 11. Correcciones técnicas aplicadas

| Problema | Solución |
|----------|----------|
| Botones no abrían el modal | El contenedor del modal tenía `hidden`; se quitó (el `<dialog>` ya se oculta solo) |
| Configuración no cargaba en JS | JSON en atributo HTML llegaba mal codificado; se pasó a **Base64** (`data-order-settings-b64`) |
| Botón submit del modal | Se separó del estilo `section-order-button` para evitar conflictos con pseudo-elementos |
| Adelanto solo en transferencia | Se unificó: **adelanto obligatorio siempre**, todos los pedidos inician en `pending_deposit` |

---

## 12. Ejemplo de mensaje WhatsApp

```
Hola, soy María.

Reserva: PED-260528-A1B2

Quiero pedir el Jueves — Almuerzo: Arroz con estofado ($3.000).

Fecha: 28/05/2026
Hora: 13:30
Forma de pago: Transferencia
Total estimado: $3.000
Adelanto obligatorio (50%): $1.500
Enviaré comprobante de transferencia por este WhatsApp.
La reserva queda sujeta a confirmación del adelanto.
```

---

## 13. Cómo probar en local

### Laravel + panel
```bash
composer install
npm install
php artisan migrate
npm run dev          # o composer run dev
```
- Panel: `http://la-piojera-de-la-ely.test/login`
- Pedidos: `/admin/orders`

### Astro (carta pública)
```bash
cd astro-catalog
npm install
npm run dev
```
- Carta: `http://localhost:4321`

### Prueba de punta a punta
1. Abrir carta en Astro.
2. Clic en **Pedir por WhatsApp** en un plato.
3. Completar formulario y confirmar.
4. Verificar que abre WhatsApp y que el pedido aparece en **Admin → Pedidos**.
5. Clic en **Confirmar adelanto** cuando llegue el comprobante.

---

## 14. Pendiente para producción

- [ ] Completar **datos bancarios** en `.env` (BANK_*)
- [ ] Prueba completa con Ely (pedido real + confirmación en panel)
- [ ] Despliegue en hosting (document root → `public/`, build de assets, Astro en subdominio o proceso Node)
- [ ] Ajustar `CORS_ALLOWED_ORIGINS` y `APP_URL` a dominio real
- [ ] (Opcional) Filtrar carta para mostrar solo el menú del día

---

## 15. Próximos pasos sugeridos (no implementados)

- Pasarela de pago online (Mercado Pago, Flow, etc.)
- Notificaciones automáticas a Ely cuando entra un pedido
- Edición de `%` de adelanto desde el panel (hoy es por `.env`)
- Filtro “solo carta de hoy” en API y frontend

---

*Última actualización: mayo 2026.*
