<?php
// Crea la orden en PayPal. El total SIEMPRE se calcula aquí, con los precios de la base de datos.
session_start();
require "conexion.php";
require "funciones.php";
require "lib/paypal.php";
header("Content-Type: application/json; charset=utf-8");

function terminar($texto, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(["error" => $texto]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") terminar("Método no permitido.", 405);
if (!isset($_SESSION["usuario_id"])) terminar("Debes iniciar sesión.", 401);
if (!paypal_configurado()) terminar("PayPal no está configurado.", 500);

list($items, $total) = carrito_detalle();
if (count($items) == 0) terminar("Tu carrito está vacío.");

$ppItems = [];      // lista para PayPal
$guardar = [];      // copia para guardar el pedido después
foreach ($items as $p) {
    $ppItems[] = [
        "name"        => mb_substr($p["nombre"], 0, 127),
        "quantity"    => (string)$p["cantidad"],
        "unit_amount" => ["currency_code" => MONEDA, "value" => number_format((float)$p["precio"], 2, ".", "")],
    ];
    $guardar[] = [
        "id"       => (int)$p["id"],
        "nombre"   => $p["nombre"],
        "precio"   => (float)$p["precio"],
        "cantidad" => (int)$p["cantidad"],
    ];
}
$totalTexto = number_format($total, 2, ".", "");

try {
    list($codigo, $orden) = paypal_api("POST", "/v2/checkout/orders", [
        "intent" => "CAPTURE",
        "purchase_units" => [[
            "description" => TIENDA_NOMBRE,
            "amount" => [
                "currency_code" => MONEDA,
                "value" => $totalTexto,
                "breakdown" => ["item_total" => ["currency_code" => MONEDA, "value" => $totalTexto]],
            ],
            "items" => $ppItems,
        ]],
    ]);
} catch (Exception $e) {
    error_log("PayPal crear: " . $e->getMessage());
    terminar("No se pudo conectar con PayPal.", 502);
}

if ($codigo >= 300 || !isset($orden["id"])) {
    error_log("PayPal crear error: " . json_encode($orden));
    terminar("PayPal rechazó la orden.", 502);
}

// Variable de sesión con la orden en curso (se revisa al capturar el pago)
$_SESSION["pp_orden"] = ["id" => $orden["id"], "total" => $totalTexto, "items" => $guardar];

echo json_encode(["id" => $orden["id"]]);
?>
