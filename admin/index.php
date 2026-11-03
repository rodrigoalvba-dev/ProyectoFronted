<?php
// Panel del administrador: resumen general
session_start();
require "../conexion.php";
require "../funciones.php";
$R = "../";
$enAdmin = true;
requiere_admin();       // solo entra quien tenga rol "admin" en la sesión

// Números generales
$k = consulta("SELECT
    (SELECT COUNT(*) FROM productos) AS productos,
    (SELECT COALESCE(SUM(stock), 0) FROM productos WHERE activo = 1) AS stock_total,
    (SELECT COALESCE(SUM(vendidos), 0) FROM productos) AS vendidos,
    (SELECT COUNT(*) FROM pedidos) AS pedidos,
    (SELECT COALESCE(SUM(total), 0) FROM pedidos) AS ingresos,
    (SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente') AS clientes")[0];

$bajo    = consulta("SELECT id, nombre, stock FROM productos WHERE activo = 1 AND stock <= ? ORDER BY stock, nombre", "i", [STOCK_BAJO]);
$top     = consulta("SELECT nombre, vendidos FROM productos WHERE vendidos > 0 ORDER BY vendidos DESC LIMIT 5");
$ultimos = consulta("SELECT p.id, p.total, p.creado_en, u.nombre FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id DESC LIMIT 6");

$titulo = "Resumen";
include "../includes/encabezado.php";
?>
  <div class="stats">
    <div class="stat"><span class="k">Ingresos</span><span class="v"><?php echo dinero($k["ingresos"]); ?></span></div>
    <div class="stat"><span class="k">Unidades vendidas</span><span class="v"><?php echo (int)$k["vendidos"]; ?></span></div>
    <div class="stat"><span class="k">Pedidos</span><span class="v"><?php echo (int)$k["pedidos"]; ?></span></div>
    <div class="stat"><span class="k">Inventario actual</span><span class="v"><?php echo (int)$k["stock_total"]; ?></span></div>
    <div class="stat"><span class="k">Productos</span><span class="v"><?php echo (int)$k["productos"]; ?></span></div>
    <div class="stat"><span class="k">Clientes</span><span class="v"><?php echo (int)$k["clientes"]; ?></span></div>
  </div>

  <div class="section-head"><h2>Stock bajo</h2><a class="btn ghost small" href="productos.php">Ver tabla de productos</a></div>
  <?php if (count($bajo) == 0) { ?>
    <p class="sub">Todo en orden: ningún producto con stock bajo.</p>
  <?php } else { ?>
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:0">
      <thead><tr><th>Producto</th><th class="num">Stock</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($bajo as $b) { ?>
        <tr class="low">
          <td class="name"><?php echo h($b["nombre"]); ?></td>
          <td class="num"><?php echo (int)$b["stock"]; ?></td>
          <td><a class="btn ghost small" href="producto_form.php?id=<?php echo (int)$b["id"]; ?>">Reabastecer</a></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>

  <div class="section-head"><h2>Más vendidos</h2></div>
  <?php if (count($top) == 0) { ?>
    <p class="sub">Aún no hay ventas registradas.</p>
  <?php } else { ?>
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:0">
      <thead><tr><th>Producto</th><th class="num">Vendidos</th></tr></thead>
      <tbody>
      <?php foreach ($top as $t) { ?>
        <tr><td class="name"><?php echo h($t["nombre"]); ?></td><td class="num"><?php echo (int)$t["vendidos"]; ?></td></tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>

  <div class="section-head"><h2>Últimos pedidos</h2><a class="btn ghost small" href="pedidos.php">Ver todos</a></div>
  <?php if (count($ultimos) == 0) { ?>
    <p class="sub">Todavía no hay pedidos.</p>
  <?php } else { ?>
  <div class="tbl-wrap">
    <table class="tbl" style="min-width:0">
      <thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php foreach ($ultimos as $u) { ?>
        <tr>
          <td class="num">#<?php echo str_pad($u["id"], 6, "0", STR_PAD_LEFT); ?></td>
          <td><?php echo h($u["nombre"]); ?></td>
          <td><?php echo h(date("d/m/Y H:i", strtotime($u["creado_en"]))); ?></td>
          <td class="num"><?php echo dinero($u["total"]); ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
<?php include "../includes/pie.php"; ?>
