<?php
// Tabla de productos con TODOS sus datos (solo administrador)
session_start();
require "../conexion.php";
require "../funciones.php";
$R = "../";
$enAdmin = true;
requiere_admin();

// ----- Acciones: ocultar/mostrar y eliminar -----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = (int)$_POST["id"];
    $accion = $_POST["accion"];

    if ($accion == "activar") {
        ejecutar("UPDATE productos SET activo = 1 - activo WHERE id = ?", "i", [$id]);
        mensaje("Visibilidad del producto actualizada.");
    } elseif ($accion == "eliminar") {
        $ventas = consulta("SELECT COUNT(*) AS n FROM pedido_items WHERE producto_id = ?", "i", [$id]);
        if ($ventas[0]["n"] > 0) {
            // Si ya se vendió, no se borra para conservar el historial: solo se oculta
            ejecutar("UPDATE productos SET activo = 0 WHERE id = ?", "i", [$id]);
            mensaje("Ese producto ya tiene ventas: se ocultó de la tienda en lugar de borrarse, para conservar el historial.");
        } else {
            ejecutar("DELETE FROM productos WHERE id = ?", "i", [$id]);
            mensaje("Producto eliminado.");
        }
    }
    header("Location: productos.php");
    exit;
}

// Cada producto con lo que ha generado en ventas
$productos = consulta("SELECT p.*, COALESCE(SUM(pi.subtotal), 0) AS ingresos
                       FROM productos p
                       LEFT JOIN pedido_items pi ON pi.producto_id = p.id
                       GROUP BY p.id
                       ORDER BY p.id");

$titulo = "Productos";
include "../includes/encabezado.php";
?>
  <div class="section-head">
    <div>
      <h2 class="page-title">Productos</h2>
      <p class="sub"><?php echo count($productos); ?> registro(s) · inventario y ventas en tiempo real</p>
    </div>
    <a class="btn primary" href="producto_form.php">Nuevo producto</a>
  </div>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>ID</th><th></th><th>Producto</th><th>Categoría</th><th class="num">Precio</th>
          <th class="num">Stock</th><th class="num">Vendidos</th><th class="num">Ingresos</th>
          <th>Estado</th><th>Alta</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($productos as $p) {
          $clase = "";
          if ($p["activo"] && $p["stock"] <= STOCK_BAJO) $clase .= "low ";
          if (!$p["activo"]) $clase .= "inactive"; ?>
        <tr class="<?php echo trim($clase); ?>">
          <td class="num"><?php echo (int)$p["id"]; ?></td>
          <td>
            <div class="thumb" style="--dot:var(<?php echo color_categoria($p["categoria"]); ?>)">
              <?php if ($p["imagen"]) { ?><img src="<?php echo h($p["imagen"]); ?>" alt="">
              <?php } else { echo h(mb_strtoupper(mb_substr($p["nombre"], 0, 1))); } ?>
            </div>
          </td>
          <td>
            <span class="name"><?php echo h($p["nombre"]); ?></span>
            <div class="hint"><?php echo h(mb_strimwidth((string)$p["descripcion"], 0, 70, "...")); ?></div>
          </td>
          <td><span class="tag" style="--dot:var(<?php echo color_categoria($p["categoria"]); ?>)"><?php echo h(nombre_categoria($p["categoria"])); ?></span></td>
          <td class="num"><?php echo dinero($p["precio"]); ?></td>
          <td class="num">
            <?php echo (int)$p["stock"]; ?>
            <?php if ($p["stock"] < 1) { ?><span class="pill warn">Agotado</span>
            <?php } elseif ($p["stock"] <= STOCK_BAJO) { ?><span class="pill warn">Bajo</span><?php } ?>
          </td>
          <td class="num"><?php echo (int)$p["vendidos"]; ?></td>
          <td class="num"><?php echo dinero($p["ingresos"]); ?></td>
          <td><?php echo $p["activo"] ? '<span class="pill">Visible</span>' : '<span class="pill off">Oculto</span>'; ?></td>
          <td><?php echo h(date("d/m/Y", strtotime($p["creado_en"]))); ?></td>
          <td>
            <div class="acts">
              <a class="btn ghost small" href="producto_form.php?id=<?php echo (int)$p["id"]; ?>">Editar</a>
              <form method="post">
                <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
                <button class="btn ghost small" name="accion" value="activar" type="submit"><?php echo $p["activo"] ? "Ocultar" : "Mostrar"; ?></button>
              </form>
              <form method="post" onsubmit="return confirm('¿Eliminar este producto?');">
                <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
                <button class="btn danger small" name="accion" value="eliminar" type="submit">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
<?php include "../includes/pie.php"; ?>
