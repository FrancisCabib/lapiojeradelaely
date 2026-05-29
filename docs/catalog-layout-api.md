# Catalog Layout API

Endpoint listo para consumir desde Astro en modo dinamico.

## Endpoint

- `GET /api/catalog-layout`

## Respuesta base

```json
{
  "meta": {
    "generated_at": "2026-03-16T15:00:00+00:00",
    "timezone": "UTC"
  },
  "hero": {
    "title": "Catalogo de servicios",
    "subtitle": "Soluciones pensadas para cada cliente",
    "info_text": "Explore servicios, temporadas y destacados definidos desde el dashboard."
  },
  "sections": [
    {
      "id": 1,
      "name": "Dia del Padre",
      "slug": "dia-del-padre",
      "description": "Promocion temporal",
      "is_active": true,
      "starts_at": "2026-06-01T00:00:00+00:00",
      "ends_at": "2026-06-20T23:59:59+00:00",
      "services": [],
      "categories": [
        {
          "id": 3,
          "name": "Regalos",
          "slug": "regalos",
          "services": [
            {
              "id": 10,
              "title": "Servicio ejemplo",
              "subtitle": "Subtitulo",
              "short_description": "Resumen corto",
              "long_description": "Detalle largo",
              "price": 25990,
              "price_formatted": "$25.990",
              "image_url": "https://...",
              "tags": [
                { "id": 2, "name": "Promo", "slug": "promo" }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

## Reglas que aplica el backend

- Solo incluye secciones `is_active = true`.
- Solo incluye secciones vigentes segun `starts_at` y `ends_at`.
- Ordena secciones por `sort_order`.
- Solo incluye categorias activas.
- Solo incluye servicios activos.
