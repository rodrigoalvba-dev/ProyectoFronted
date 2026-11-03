<?php
// Cobra el pago, guarda el pedido, DESCUENTA EL INVENTARIO y SUMA AL CONTEO DE VENTAS.
session_start();
require "conexion.php";
require "funciones.php";
require "lib/paypal.php";
header("Content-Type: application/json; charset=utf-8");

function terminar($texto, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(["ok" => false, "error" => $texto]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") terminar("Método no permitido.", 405);
if (!isset($_SESSION["usuario_id"])) terminar("Debes iniciar sesión.", 401);

$entrada = json_decode(file_get_contents("php://input"), true);
$ordenId = is_array($entrada) && isset($entrada["orderID"]) ? (string)$entrada["orderID"] : "";

// La orden debe ser la que se creó en esta sesión
if (!isset($_SESSION["pp_orden"]) || $ordenId == "" || $_SESSION["pp_orden"]["id"] != $ordenId) {
    terminar("La orden no coincide con tu sesión.");
}
$pp = $_SESSION["pp_orden"];

// Si este pago ya se guardó antes, solo mandamos al recibo (evita duplicados)
$ya = consulta("SELECT id FROM pedidos WHERE paypal_order_id = ?", "s", [$ordenId]);
if (count($ya) > 0) {
    echo json_encode(["ok" => true, "redirigir" => "gracias.php?id=" . (int)$ya[0]["id"]]);
    exit;
}

// 1) Capturar el pago en PayPal
try {
    list($codigo, $res) = paypal_api("POST", "/v2/checkout/orders/" . rawurlencode($ordenId) . "/capture", new stdClass());
} catch (Exception $e) {
    error_log("PayPal capturar: " . $e->getMessage());
    terminar("No se pudo conectar con PayPal.", 502);
}

$captura = null;
if (isset($res["purchase_units"][0]["payments"]["captures"][0])) {
    $captura = $res["purchase_units"][0]["payments"]["captures"][0];
}
$estado = isset($res["status"]) ? $res["status"] : "";
if ($codigo >= 300 || $estado != "COMPLETED" || $captura == null || $captura["status"] != "COMPLETED") {
    error_log("PayPal no completó: " . json_encode($res));
    terminar("El pago no se completó. No se realizó ningún cargo.");
}

// 2) El monto cobrado debe ser igual al que calculó el servidor
if (abs((float)$captura["amount"]["value"] - (float)$pp["total"]) > 0.001) {
    error_log("Monto distinto. Esperado " . $pp["total"] . " recibido " . $captura["amount"]["value"]);
    terminar("El monto cobrado no coincide. Contacta a la tienda con tu ID de orden: " . $ordenId);
}

$nombrePagador = "";
$correoPagador = "";
if (isset($res["payer"])) {
    $nombrePagador = trim(($res["payer"]["name"]["given_name"] ?? "") . " " . ($res["payer"]["name"]["surname"] ?? ""));
    $correoPagador = $res["payer"]["email_address"] ?? "";
}

// 3) Guardar pedido + bajar inventario (todo junto: si algo falla, no se guarda nada)
try {
    mysqli_begin_transaction($conexion);

    ejecutar("INSERT INTO pedidos (usuario_id, paypal_order_id, paypal_capture_id, total, moneda, estado, pagador_nombre, pagador_email)
              VALUES (?, ?, ?, ?, ?, 'pagado', ?, ?)",
             "issdsss",
             [$_SESSION["usuario_id"], $ordenId, $captura["id"], (float)$pp["total"], MONEDA, $nombrePagador, $correoPagador]);
    $pedidoId = mysqli_insert_id($conexion);

    foreach ($pp["items"] as $it) {
        $subtotal = round($it["precio"] * $it["cantidad"], 2);

        ejecutar("INSERT INTO pedido_items (pedido_id, producto_id, nombre, precio_unitario, cantidad, subtotal)
                  VALUES (?, ?, ?, ?, ?, ?)",
                 "iisdid",
                 [$pedidoId, $it["id"], $it["nombre"], $it["precio"], $it["cantidad"], $subtotal]);

        // DISMINUCIÓN DE INVENTARIO y CONTEO DE VENTAS
        ejecutar("UPDATE productos SET stock = GREATEST(stock - ?, 0), vendidos = vendidos + ? WHERE id = ?",
                 "iii",
                 [$it["cantidad"], $it["cantidad"], $it["id"]]);
    }

    mysqli_commit($conexion);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log("Error guardando pedido " . $ordenId . ": " . $e->getMessage());
    terminar("Tu pago se recibió pero hubo un error al guardar el pedido. Guarda tu ID de orden de PayPal: " . $ordenId, 500);
}

// 4) Vaciar el carrito y la orden en curso
$_SESSION["carrito"] = [];
unset($_SESSION["pp_orden"]);

echo json_encode(["ok" => true, "redirigir" => "gracias.php?id=" . $pedidoId]);
?>
