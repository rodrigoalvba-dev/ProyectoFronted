<?php
session_start();
require "conexion.php";
require "funciones.php";
$R = "";
requiere_login();

$pedidos = consulta("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC", "i", [$_SESSION["usuario_id"]]);

$titulo = "Mis pedidos";
include "includes/encabezado.php";
?>
  <h2 class="page-title">Mis pedidos</h2>

  <?php if (count($pedidos) == 0) { ?>
    <div class="empty">
      <h3>Aún no tienes pedidos</h3>
      <p>Cuando completes una compra aparecerá aquí con su recibo.</p>
      <a class="btn primary" href="index.php">Ir a la tienda</a>
    </div>
  <?php } else { ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th>Pedido</th><th>Fecha</th><th>Artículos</th><th class="num">Total</th><th>Recibo</th></tr>
      </thead>
      <tbody>
      <?php foreach ($pedidos as $p) {
          $items = consulta("SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id", "i", [$p["id"]]); ?>
        <tr>
          <td class="num">#<?php echo str_pad($p["id"], 6, "0", STR_PAD_LEFT); ?></td>
          <td><?php echo h(date("d/m/Y H:i", strtotime($p["creado_en"]))); ?></td>
          <td>
            <details>
              <summary><?php echo array_sum(array_column($items, "cantidad")); ?> pieza(s)</summary>
              <ul>
                <?php foreach ($items as $it) { ?>
                  <li><?php echo (int)$it["cantidad"]; ?> × <?php echo h($it["nombre"]); ?></li>
                <?php } ?>
              </ul>
            </details>
          </td>
          <td class="num"><?php echo dinero($p["total"]); ?></td>
          <td><a class="btn ghost small" href="recibo.php?id=<?php echo (int)$p["id"]; ?>&descargar=1">PDF</a></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
<?php include "includes/pie.php"; ?>
