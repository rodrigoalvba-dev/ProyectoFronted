<?php
session_start();
require "conexion.php";
require "funciones.php";
$R = "";

// ----- Acciones del carrito (se envían por POST desde esta misma página) -----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST["accion"]) ? $_POST["accion"] : "";
    $id     = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

    if ($accion == "actualizar") {
        $cantidad = (int)$_POST["cantidad"];
        $filas = consulta("SELECT stock FROM productos WHERE id = ? AND activo = 1", "i", [$id]);
        if (count($filas) > 0) {
            $cantidad = min($cantidad, (int)$filas[0]["stock"]);
            if ($cantidad < 1) {
                unset($_SESSION["carrito"][$id]);
            } else {
                $_SESSION["carrito"][$id] = $cantidad;
            }
        }
    } elseif ($accion == "quitar") {
        unset($_SESSION["carrito"][$id]);
        mensaje("Producto quitado del carrito.");
    } elseif ($accion == "vaciar") {
        $_SESSION["carrito"] = [];
        mensaje("Carrito vaciado.");
    }
    header("Location: carrito.php");
    exit;
}

list($items, $total) = carrito_detalle();
$titulo = "Carrito";
include "includes/encabezado.php";
?>
  <h2 class="page-title">Tu carrito</h2>

  <?php if (count($items) == 0) { ?>
    <div class="empty">
      <h3>Tu carrito está vacío</h3>
      <p>Agrega una agenda o un planner para comenzar tu compra.</p>
      <a class="btn primary" href="index.php">Ir a la tienda</a>
    </div>
  <?php } else { ?>
  <div class="layout2">
    <ul class="items">
      <?php foreach ($items as $p) { ?>
      <li class="item cart-item">
        <div class="body">
          <p class="title"><?php echo h($p["nombre"]); ?></p>
          <div class="meta">
            <span class="cat" style="--dot:var(<?php echo color_categoria($p["categoria"]); ?>)"><?php echo h(nombre_categoria($p["categoria"])); ?></span>
            <span class="time"><?php echo dinero($p["precio"]); ?></span>
          </div>
        </div>
        <form class="qty" method="post">
          <input type="hidden" name="accion" value="actualizar">
          <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
          <input class="input" type="number" name="cantidad" min="0" max="<?php echo (int)$p["stock"]; ?>" value="<?php echo (int)$p["cantidad"]; ?>" aria-label="Cantidad">
          <button class="btn ghost small" type="submit">Actualizar</button>
        </form>
        <div style="display:flex;align-items:center;gap:8px">
          <span class="sub-amt"><?php echo dinero($p["subtotal"]); ?></span>
          <form method="post">
            <input type="hidden" name="accion" value="quitar">
            <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
            <button class="del" type="submit" aria-label="Quitar" title="Quitar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
            </button>
          </form>
        </div>
      </li>
      <?php } ?>
    </ul>

    <aside class="summary">
      <h2>Resumen</h2>
      <div class="line"><span>Artículos</span><span><?php echo array_sum(array_column($items, "cantidad")); ?></span></div>
      <div class="line total"><span>Total</span><span><?php echo dinero($total); ?></span></div>
      <a class="btn primary" href="checkout.php">Pagar con PayPal</a>
      <form method="post">
        <input type="hidden" name="accion" value="vaciar">
        <button class="btn ghost" style="width:100%" type="submit">Vaciar carrito</button>
      </form>
    </aside>
  </div>
  <?php } ?>
<?php include "includes/pie.php"; ?>
