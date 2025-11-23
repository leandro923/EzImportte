<?php
// 1. Cargar el autoloader de Composer (NECESARIO)
require __DIR__ . '/vendor/autoload.php';

// --- CONFIGURACIÓN Y CLAVES SECRETAS ---

// ⚠️ 1. PEGA AQUÍ TU ACCESS TOKEN SECRETO 🔑
const ACCESS_TOKEN = 'APP_USR-1466628612664002-112217-72308268e4c7420287664c356fbbadd9-1020356369'; 
// ⚠️ 2. PEGA AQUÍ LA URL BASE DE TU SITIO 🌐 (ej: https://tudominio.com)
const BASE_URL = 'https://leandro923.github.io/EzImportte/'; 
// ⚠️ 3. PEGA AQUÍ EL CÓDIGO DE TU MONEDA 💵 
const CURRENCY_ID = 'ARS'; 

// --- INICIALIZACIÓN Y LÓGICA ---

// Inicializar el SDK de Mercado Pago
MercadoPago\SDK::setAccessToken(ACCESS_TOKEN);

// Establecer que la respuesta sea JSON (para que el frontend la entienda)
header('Content-Type: application/json');

// Leer los datos del carrito enviados desde el frontend
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
$cart = $data['items'] ?? []; // Usamos 'items' como lo definimos en el frontend

if (empty($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío.']);
    exit;
}

// Formatear los ítems para la API de Mercado Pago
$mp_items = [];
foreach ($cart as $item) {
    // Aseguramos que los nombres de los campos coincidan con la API de MP y el SDK
    $mp_items[] = [
        'title' => $item['name'],
        'unit_price' => (float) $item['price'],
        'quantity' => (int) $item['qty'],
        'currency_id' => CURRENCY_ID,
    ];
}

// Crear el objeto de Preferencia de Pago
$preference = new MercadoPago\Preference();
$preference->items = $mp_items;

// Definir las URLs de retorno simples a tu sitio
$preference->back_urls = [
    'success' => BASE_URL . '/pago-exitoso.html', 
    'pending' => BASE_URL . '/pago-pendiente.html',
    'failure' => BASE_URL . '/pago-fallido.html',
];
$preference->auto_return = 'approved';

try {
    // 4. Enviar la preferencia a la API de Mercado Pago
    $preference->save();

    // 5. ¡DEVOLVER EL ID DE LA PREFERENCIA! (Lo que espera el frontend)
    echo json_encode(['id' => $preference->id]);

} catch (Exception $e) {
    // Manejo de errores
    http_response_code(500);
    echo json_encode(['error' => 'Error al crear la preferencia con el SDK.', 'details' => $e->getMessage()]);
}
?>