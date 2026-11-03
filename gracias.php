<?php
session_start();
require "conexion.php";
require "funciones.php";
$R = "";
requiere_login();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$filas = consulta("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?", "ii", [$id, $_SESSION["usuario_id"]]);
if (count($filas) == 0) {
    die("Pedido no encontrado.");
}
$p = $filas[0];

$titulo = "Pago exitoso";
include "includes/encabezado.php";
?>
  <div class="empty ok-box">
    <h3>¡Pago recibido!</h3>
    <p>Tu pedido <strong>#<?php echo str_pad($p["id"], 6, "0", STR_PAD_LEFT); ?></strong> por <strong><?php echo dinero($p["total"]); ?></strong> quedó registrado.</p>
    <p>ID de transacción PayPal: <code><?php echo h($p["paypal_capture_id"]); ?></code></p>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn primary" href="recibo.php?id=<?php echo (int)$p["id"]; ?>&descargar=1">Descargar recibo (PDF)</a>
      <a class="btn ghost" href="recibo.php?id=<?php echo (int)$p["id"]; ?>" target="_blank" rel="noopener">Ver recibo</a>
      <a class="btn ghost" href="index.php">Seguir comprando</a>
    </div>
  </div>
<?php include "includes/pie.php"; ?>
