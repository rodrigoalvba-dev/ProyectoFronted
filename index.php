<?php
// Catálogo de productos (parte del cliente)
session_start();
require "conexion.php";
require "funciones.php";
$R = "";

// Filtros por GET: ?cat=diaria&q=texto
$cat = isset($_GET["cat"]) ? $_GET["cat"] : "";
$q   = isset($_GET["q"]) ? trim($_GET["q"]) : "";
if (!isset($CATEGORIAS[$cat])) $cat = "";

$sql = "SELECT * FROM productos WHERE activo = 1";
$tipos = "";
$datos = [];
if ($cat != "") {
    $sql .= " AND categoria = ?";
    $tipos .= "s";
    $datos[] = $cat;
}
if ($q != "") {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $tipos .= "ss";
    $datos[] = "%" . $q . "%";
    $datos[] = "%" . $q . "%";
}
$sql .= " ORDER BY (stock = 0), id DESC";      // los agotados al final
$productos = consulta($sql, $tipos, $datos);

$titulo = "Tienda";
include "includes/encabezado.php";
?>
  <div class="toolbar">
    <div>
      <h2 class="page-title">Catálogo</h2>
      <p class="sub"><?php echo count($productos); ?> producto(s) disponibles</p>
    </div>
    <form class="search" method="get" role="search">
      <?php if ($cat != "") { ?><input type="hidden" name="cat" value="<?php echo h($cat); ?>"><?php } ?>
      <input class="input" type="search" name="q" value="<?php echo h($q); ?>" placeholder="Buscar agendas…" aria-label="Buscar">
      <button class="btn ghost" type="submit">Buscar</button>
    </form>
  </div>

  <div class="catbar" role="group" aria-label="Filtrar por categoría">
    <a href="index.php" <?php if ($cat == "") echo 'aria-current="true"'; ?>>Todas</a>
    <?php foreach ($CATEGORIAS as $clave => $info) { ?>
      <a href="index.php?cat=<?php echo $clave; ?>" style="--dot:var(<?php echo $info[1]; ?>)" <?php if ($cat == $clave) echo 'aria-current="true"'; ?>><i></i><?php echo h($info[0]); ?></a>
    <?php } ?>
  </div>

  <?php if (count($productos) == 0) { ?>
    <div class="empty">
      <h3>No encontramos productos</h3>
      <p>Prueba con otra búsqueda o quita el filtro de categoría.</p>
      <a class="btn ghost" href="index.php">Ver todo</a>
    </div>
  <?php } else { ?>
  <div class="grid">
    <?php foreach ($productos as $p) { $agotado = ($p["stock"] < 1); ?>
      <article class="card" style="--dot:var(<?php echo color_categoria($p["categoria"]); ?>)">
        <div class="pic">
          <?php if ($p["imagen"]) { ?>
            <img src="<?php echo h($p["imagen"]); ?>" alt="<?php echo h($p["nombre"]); ?>" loading="lazy">
          <?php } else { echo h(mb_strtoupper(mb_substr($p["nombre"], 0, 1))); } ?>
        </div>
        <div class="info">
          <span class="tag"><?php echo h(nombre_categoria($p["categoria"])); ?></span>
          <h3><?php echo h($p["nombre"]); ?></h3>
          <p><?php echo h($p["descripcion"]); ?></p>
          <div class="row">
            <span class="price"><?php echo dinero($p["precio"]); ?></span>
            <?php if ($agotado) { ?><span class="pill warn">Agotado</span>
            <?php } elseif ($p["stock"] <= STOCK_BAJO) { ?><span class="pill warn">Quedan <?php echo (int)$p["stock"]; ?></span>
            <?php } else { ?><span class="pill"><?php echo (int)$p["stock"]; ?> disponibles</span><?php } ?>
          </div>
          <form class="buy" method="post" action="agregar.php">
            <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
            <input class="input" type="number" name="cantidad" value="1" min="1" max="<?php echo (int)$p["stock"]; ?>" aria-label="Cantidad" <?php if ($agotado) echo "disabled"; ?>>
            <button class="btn primary" type="submit" <?php if ($agotado) echo "disabled"; ?>>Agregar</button>
          </form>
        </div>
      </article>
    <?php } ?>
  </div>
  <?php } ?>
<?php include "includes/pie.php"; ?>
