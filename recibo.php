<?php
// Recibo de pago en PDF. Lo puede ver el dueño del pedido o un administrador.
session_start();
require "conexion.php";
require "funciones.php";
require "lib/SimplePDF.php";
requiere_login();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$filas = consulta("SELECT p.*, u.nombre AS cliente, u.email AS cliente_email
                   FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                   WHERE p.id = ?", "i", [$id]);

// Solo el dueño del pedido o el admin
if (count($filas) == 0 || ($filas[0]["usuario_id"] != $_SESSION["usuario_id"] && $_SESSION["rol"] != "admin")) {
    die("Pedido no encontrado.");
}
$p = $filas[0];
$items = consulta("SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id", "i", [$id]);

$folio = str_pad($p["id"], 6, "0", STR_PAD_LEFT);

// Colores (los mismos de la Agenda)
$VERDE = [15, 107, 88];
$TINTA = [22, 33, 29];
$GRIS  = [91, 106, 100];
$LINEA = [211, 218, 214];
$SUAVE = [214, 235, 228];
$izq = 48;      // margen izquierdo
$der = 564;     // margen derecho

$pdf = new SimplePDF();
$pdf->addPage();

// Encabezado verde
$pdf->rect(0, 0, 612, 96, $VERDE);
$pdf->text($izq, 52, TIENDA_NOMBRE, 24, true, [255, 255, 255]);
$pdf->text($izq, 74, "Recibo de pago", 12, false, [214, 235, 228]);
$pdf->textRight($der, 52, "Folio " . $folio, 16, true, [255, 255, 255]);
$pdf->textRight($der, 74, date("d/m/Y H:i", strtotime($p["creado_en"])), 11, false, [214, 235, 228]);

// Datos del cliente y del pago
$y = 134;
$pdf->text($izq, $y, "CLIENTE", 9, true, $GRIS);
$pdf->text($izq, $y + 16, $p["cliente"], 12, true, $TINTA);
$pdf->text($izq, $y + 32, $p["cliente_email"], 10, false, $GRIS);
$pdf->text(330, $y, "DATOS DEL PAGO", 9, true, $GRIS);
$pdf->text(330, $y + 16, "Método: PayPal", 10, false, $TINTA);
$pdf->text(330, $y + 30, "Estado: " . ucfirst($p["estado"]), 10, false, $TINTA);
$pdf->text(330, $y + 44, "Orden: " . $p["paypal_order_id"], 9, false, $GRIS);
$pdf->text(330, $y + 57, "Transacción: " . $p["paypal_capture_id"], 9, false, $GRIS);

// Encabezado de la tabla (se repite si hay otra página)
function encabezado_tabla($pdf, $y, $izq, $der, $SUAVE, $TINTA) {
    $pdf->rect($izq, $y, $der - $izq, 24, $SUAVE);
    $pdf->text($izq + 10, $y + 16, "Producto", 10, true, $TINTA);
    $pdf->textRight(370, $y + 16, "Cant.", 10, true, $TINTA);
    $pdf->textRight(470, $y + 16, "Precio", 10, true, $TINTA);
    $pdf->textRight($der - 10, $y + 16, "Importe", 10, true, $TINTA);
}

$y = 232;
encabezado_tabla($pdf, $y, $izq, $der, $SUAVE, $TINTA);
$y += 24;

foreach ($items as $it) {
    if ($y > 690) {                       // nueva página si ya no cabe
        $pdf->addPage();
        $y = 60;
        encabezado_tabla($pdf, $y, $izq, $der, $SUAVE, $TINTA);
        $y += 24;
    }
    // Recorta el nombre si es muy largo
    $nombre = $it["nombre"];
    while ($pdf->width($nombre, 10) > 270 && mb_strlen($nombre) > 4) {
        $nombre = mb_substr($nombre, 0, -2);
    }
    if ($nombre != $it["nombre"]) $nombre = rtrim($nombre) . "...";

    $pdf->text($izq + 10, $y + 18, $nombre, 10, false, $TINTA);
    $pdf->textRight(370, $y + 18, (string)$it["cantidad"], 10, false, $TINTA);
    $pdf->textRight(470, $y + 18, "$" . number_format($it["precio_unitario"], 2), 10, false, $TINTA);
    $pdf->textRight($der - 10, $y + 18, "$" . number_format($it["subtotal"], 2), 10, false, $TINTA);
    $y += 28;
    $pdf->line($izq, $y, $der, $y, $LINEA);
}

// Total
if ($y > 660) { $pdf->addPage(); $y = 60; }
$y += 18;
$pdf->rect(340, $y, $der - 340, 36, $VERDE);
$pdf->text(352, $y + 23, "TOTAL PAGADO", 11, true, [255, 255, 255]);
$pdf->textRight($der - 12, $y + 24, "$" . number_format($p["total"], 2) . " " . $p["moneda"], 14, true, [255, 255, 255]);

$pdf->text($izq, 748, "Gracias por tu compra. Comprobante de pago generado por " . TIENDA_NOMBRE . ".", 9, false, $GRIS);

// Enviar el PDF al navegador
$contenido = $pdf->output();
$modo = isset($_GET["descargar"]) ? "attachment" : "inline";
header("Content-Type: application/pdf");
header("Content-Disposition: " . $modo . '; filename="recibo-' . $folio . '.pdf"');
header("Content-Length: " . strlen($contenido));
header("Cache-Control: private, no-store");
echo $contenido;
?>
