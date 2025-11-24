// -------------------------------------------------------------------------
// ESTE CÓDIGO DEBE EJECUTARSE EN TU SERVIDOR (Node.js)
// -------------------------------------------------------------------------

// 1. Instala el SDK de Mercado Pago y Express si aún no lo has hecho:
// npm install express mercadopago cors

const express = require('express');
const mercadopago = require('mercadopago');
const cors = require('cors');

const app = express();
const PORT = 3000;

// ⚠️ PASO CRÍTICO: REEMPLAZA ESTA CLAVE por tu Access Token (Clave Secreta) REAL
const ACCESS_TOKEN = "TU_ACCESS_TOKEN_AQUI";

// Inicializa el SDK de Mercado Pago con tu Access Token
mercadopago.configure({
    access_token: ACCESS_TOKEN,
});

// Middlewares
app.use(express.json()); // Permite procesar el JSON enviado desde el frontend
app.use(cors()); // Habilita CORS para permitir llamadas desde el frontend

// =======================================================
// ENDPOINT PARA CREAR LA PREFERENCIA DE PAGO
// =======================================================
app.post('/create_mp_preference', async (req, res) => {
    console.log("Recibida solicitud del frontend para crear preferencia.");

    // Recibe el array de items del carrito y el total desde el frontend
    const { items, total } = req.body;

    if (!items || items.length === 0 || !total) {
        return res.status(400).json({ error: 'El carrito está vacío o faltan datos.' });
    }

    // Mapea los ítems del carrito al formato esperado por la API de Mercado Pago
    const preferenceItems = items.map(item => ({
        title: item.name,
        unit_price: parseFloat(item.price), // Aseguramos que sea un número
        quantity: parseInt(item.qty, 10),    // Aseguramos que sea un entero
        currency_id: "ARS", // Moneda: Pesos Argentinos. Cambia si es necesario (ej: BRL, CLP)
    }));

    // El objeto de preferencia que se enviará a Mercado Pago
    const preference = {
        items: preferenceItems,
        // URLs de Redirección después de pagar (IMPORTANTE: Cambiar a tu dominio real)
        back_urls: {
            success: "http://localhost:3000/feedback?status=success",
            failure: "http://localhost:3000/feedback?status=failure",
            pending: "http://localhost:3000/feedback?status=pending"
        },
        // URL de Notificación para Webhooks (fundamental para saber el estado real del pago)
        // Debes reemplazar 'tuserver.com' con tu dominio y configurar la URL en MP.
        notification_url: "https://tuserver.com/webhooks/mercadopago",
        external_reference: `ORDER-${Date.now()}`, // Referencia única para tu pedido
        auto_return: "approved" // Redirige automáticamente solo si es aprobado
    };

    try {
        // Llama a la API de Mercado Pago para crear la preferencia
        const mpResponse = await mercadopago.preferences.create(preference);

        // Devuelve SOLAMENTE la ID de preferencia al frontend
        console.log(`Preferencia creada con ID: ${mpResponse.body.id}`);
        res.status(200).json({ preferenceId: mpResponse.body.id });

    } catch (error) {
        console.error("Error al crear la preferencia de MP:", error.message);
        res.status(500).json({ error: 'Fallo interno al procesar el pago. Revise el Access Token y la consola del servidor.' });
    }
});


// Endpoint de Feedback (puedes reemplazar esto con tu página de éxito real)
app.get('/feedback', (req, res) => {
    const status = req.query.status || 'unknown';
    const message = {
        success: "¡Pago Aprobado! Gracias por tu compra.",
        failure: "El pago fue rechazado. Intenta con otro medio.",
        pending: "El pago está pendiente de confirmación."
    }[status] || "Estado de pago desconocido.";

    res.send(`
        <div style="text-align: center; padding: 50px; font-family: 'Inter', sans-serif;">
            <h1 style="color: ${status === 'success' ? '#009ee3' : '#8200ff'};">${message}</h1>
            <p style="font-size: 1.2em; margin-top: 20px;">Estado: <strong>${status.toUpperCase()}</strong></p>
            <a href="checkout_mp_frontend.html" style="display: inline-block; margin-top: 30px; padding: 10px 20px; background: #8200ff; color: white; border-radius: 8px; text-decoration: none;">Volver al Checkout</a>
        </div>
    `);
});


// Iniciar el servidor
app.listen(PORT, () => {
    console.log(`\n🚀 Servidor de Backend corriendo en http://localhost:${PORT}`);
    console.log(`¡Recuerda reemplazar las claves de prueba por las de producción cuando corresponda!`);
});