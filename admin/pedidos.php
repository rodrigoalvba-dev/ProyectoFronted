<?php
// Todos los pedidos de todos los clientes (solo administrador)
session_start();
require "../conexion.php";
require "../funciones.php";
$R = "../";
$enAdmin = true;
requiere_admin();

$pedidos = consulta("SELECT p.*, u.nombre AS cliente, u.email AS cliente_email
                     FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id
                     ORDER BY p.id DESC");
$suma = array_sum(array_column($pedidos, "total"));

$titulo = "Pedidos";
include "../includes/encabezado.php";
?>
  <div>
    <h2 class="page-title">Pedidos</h2>
    <p class="sub"><?php echo count($pedidos); ?> pedido(s) · <?php echo dinero($suma); ?> en total</p>
  </div>

  <?php if (count($pedidos) == 0) { ?>
    <div class="empty">
      <h3>Sin pedidos todavía</h3>
      <p>Cuando un cliente pague con PayPal, el pedido aparecerá aquí.</p>
    </div>
  <?php } else { ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Pagador PayPal</th><th>Detalle</th><th>Transacción</th><th class="num">Total</th><th>Recibo</th></tr>
      </thead>
      <tbody>
      <?php foreach ($pedidos as $p) {
          $items = consulta("SELECT * FROM pedido_items WHERE pedido_id = ? ORDER BY id", "i", [$p["id"]]); ?>
        <tr>
          <td class="num">#<?php echo str_pad($p["id"], 6, "0", STR_PAD_LEFT); ?></td>
          <td><?php echo h(date("d/m/Y H:i", strtotime($p["creado_en"]))); ?></td>
          <td><span class="name"><?php echo h($p["cliente"]); ?></span><div class="hint"><?php echo h($p["cliente_email"]); ?></div></td>
          <td><?php echo h($p["pagador_nombre"] ? $p["pagador_nombre"] : "—"); ?><div class="hint"><?php echo h($p["pagador_email"]); ?></div></td>
          <td>
            <details>
              <summary><?php echo array_sum(array_column($items, "cantidad")); ?> pieza(s)</summary>
              <ul>
                <?php foreach ($items as $it) { ?>
                  <li><?php echo (int)$it["cantidad"]; ?> × <?php echo h($it["nombre"]); ?> (<?php echo dinero($it["subtotal"]); ?>)</li>
                <?php } ?>
              </ul>
            </details>
          </td>
          <td><code style="font-size:12px"><?php echo h($p["paypal_capture_id"]); ?></code></td>
          <td class="num"><?php echo dinero($p["total"]); ?></td>
          <td><a class="btn ghost small" target="_blank" rel="noopener" href="../recibo.php?id=<?php echo (int)$p["id"]; ?>">PDF</a></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
<?php include "../includes/pie.php"; ?>
