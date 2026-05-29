<?php

return [

    /*
    | WhatsApp (pedidos del catálogo). Solo dígitos, con código de país, sin +.
    | Ej. Chile: 56912345678. Vacío = no se muestran botones de pedido.
    */
    'whatsapp_order_phone' => preg_replace('/\D+/', '', (string) env('WHATSAPP_ORDER_PHONE', '')),

    'city' => env('BUSINESS_CITY', 'Tierra Amarilla'),

    'region' => env('BUSINESS_REGION', 'Región de Atacama'),

    'location_line' => env('BUSINESS_LOCATION_LINE', 'Tierra Amarilla · Región de Atacama'),

    /*
    | Reserva: porcentaje de adelanto al pagar por transferencia (0–100).
    */
    'reservation_deposit_percent' => (int) env('RESERVATION_DEPOSIT_PERCENT', 50),

    'bank_name' => env('BANK_NAME', ''),

    'bank_account_type' => env('BANK_ACCOUNT_TYPE', ''),

    'bank_account_number' => env('BANK_ACCOUNT_NUMBER', ''),

    'bank_account_holder' => env('BANK_ACCOUNT_HOLDER', ''),

    'bank_account_rut' => env('BANK_ACCOUNT_RUT', ''),

    'bank_account_email' => env('BANK_ACCOUNT_EMAIL', ''),

];
