<?php
// Esto es un ejemplo conceptual. Debes usar el SDK oficial de Mercado Pago.

// ----------------------------------------------------
// ⚠️ REEMPLAZA CON TUS VALORES REALES DE PRODUCCIÓN
// ----------------------------------------------------
$ACCESS_TOKEN = "TU_ACCESS_TOKEN_DE_MERCADO_PAGO"; // CLAVE SECRETA!
$WHATSAPP_NUMBER = "54911xxxxxxxx"; // Tu número (incluyendo código de país sin el +)
$BACKEND_URL = "https://tu-sitio.com/create_preference.php"; // URL de tu script de backend
// ----------------------------------------------------

// 1. Recibir los datos del carrito desde el frontend (POST)
$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];

if (empty($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío']);
    exit;
}

// 2. Formatear los ítems para la API de Mercado Pago
$itemsMP = [];
$total = 0;
$whatsappDetail = "¡Hola! Mi pago con MP fue aprobado. Mi pedido es:\n\n";

foreach ($cart as $item) {
    $subtotal = $item['price'] * $item['qty'];
    $total += $subtotal;
    
    // Formato para API de Mercado Pago
    $itemsMP[] = [
        'title' => $item['name'] . ($item['units'] === '2' ? ' (x2)' : ''),
        'unit_price' => (float)$item['price'],
        'quantity' => (int)$item['qty'],
        'currency_id' => 'ARS', // Reemplaza si es necesario
    ];
    
    // Construcción del detalle para WhatsApp
    $whatsappDetail .= "* " . $item['name'] . " x " . $item['qty'] . " uni. | $" . number_format($subtotal, 2) . "\n";
}

$whatsappDetail .= "\n*TOTAL PAGADO: $" . number_format($total, 2) . "*\n\n";
$whatsappDetail .= "Adjunto el comprobante en el siguiente mensaje.";


// 3. Crear el URL de Éxito de WhatsApp DINÁMICO
// Usamos urlencode para que el mensaje sea válido en un URL
$successUrl = "https://wa.me/{$WHATSAPP_NUMBER}?text=" . urlencode($whatsappDetail);


// 4. Crear la Preferencia de Pago
$preferenceData = [
    'items' => $itemsMP,
    'back_urls' => [
        'success' => $successUrl,
        'failure' => 'https://tu-sitio.com/checkout.html?status=failure', // URL a tu sitio en caso de fallo
        'pending' => 'https://tu-sitio.com/checkout.html?status=pending', // URL a tu sitio en caso de pendiente
    ],
    'auto_return' => 'approved', // Redirigir automáticamente al éxito si el pago es aprobado
    'external_reference' => 'ORDER-' . time(), // Referencia única de la orden
];

// --- Inicia comunicación con la API de Mercado Pago ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/checkout/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preferenceData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $ACCESS_TOKEN,
    'Content-Type: application/json'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
// --- Finaliza comunicación con la API de Mercado Pago ---

if ($httpCode === 201) {
    $response = json_decode($result, true);
    // Devuelve el URL de inicio de pago al frontend
    echo json_encode([
        'init_point' => $response['init_point'] // El URL de Mercado Pago
    ]);
} else {
    // Manejo de errores
    http_response_code($httpCode);
    echo json_encode(['error' => 'Error al crear la preferencia en MP.', 'details' => json_decode($result, true)]);
}

?>