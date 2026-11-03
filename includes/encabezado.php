<?php
// Parte superior de todas las páginas.
// Antes de incluirlo cada página define: $titulo, $R (ruta: "" o "../") y opcionalmente $enAdmin = true
if (!isset($R)) $R = "";
if (!isset($enAdmin)) $enAdmin = false;
if (!isset($titulo)) $titulo = TIENDA_NOMBRE;
$actual = basename($_SERVER["SCRIPT_NAME"]);

// Dibuja un enlace del menú; el de la página actual se resalta
function enlace($href, $texto, $actual, $extra = "") {
    $on = (basename($href) == $actual) ? ' aria-current="page"' : "";
    return '<a href="' . h($href) . '"' . $on . '>' . $texto . $extra . '</a>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($titulo); ?> · <?php echo h(TIENDA_NOMBRE); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=DM+Mono:wght@400;500&family=Instrument+Sans:wght@400..600&display=swap">
<link rel="stylesheet" href="<?php echo $R; ?>assets/styles.css">
<link rel="stylesheet" href="<?php echo $R; ?>assets/tienda.css">
</head>
<body>
<div class="wrap">
  <header class="top">
    <h1 class="brand">
      <a href="<?php echo $R . ($enAdmin ? "admin/index.php" : "index.php"); ?>"><?php echo h(TIENDA_NOMBRE); ?></a>
      <small><?php echo $enAdmin ? "Panel de administración" : "Agendas y planners"; ?></small>
    </h1>
    <nav class="nav" aria-label="Principal">
      <?php if ($enAdmin) { ?>
        <?php echo enlace($R . "admin/index.php", "Resumen", $actual); ?>
        <?php echo enlace($R . "admin/productos.php", "Productos", $actual); ?>
        <?php echo enlace($R . "admin/pedidos.php", "Pedidos", $actual); ?>
        <?php echo enlace($R . "admin/usuarios.php", "Usuarios", $actual); ?>
        <a href="<?php echo $R; ?>index.php">Ver tienda</a>
      <?php } else { ?>
        <?php echo enlace($R . "index.php", "Tienda", $actual); ?>
        <?php echo enlace($R . "carrito.php", "Carrito", $actual, '<b class="badge-n">' . piezas_carrito() . '</b>'); ?>
        <?php if (isset($_SESSION["usuario_id"])) { ?>
          <?php echo enlace($R . "mis_pedidos.php", "Mis pedidos", $actual); ?>
          <?php if ($_SESSION["rol"] == "admin") { ?><a href="<?php echo $R; ?>admin/index.php">Administrar</a><?php } ?>
        <?php } ?>
      <?php } ?>

      <?php if (isset($_SESSION["usuario_id"])) { ?>
        <span class="who"><?php echo h($_SESSION["usuario"]); ?></span>
        <a href="<?php echo $R; ?>salir.php">Cerrar sesión</a>
      <?php } else { ?>
        <?php echo enlace($R . "login.php", "Entrar", $actual); ?>
        <?php echo enlace($R . "registro.php", "Crear cuenta", $actual); ?>
      <?php } ?>
    </nav>
  </header>

  <?php
  // Mensaje de una sola vez (variable de sesión "mensaje")
  if (isset($_SESSION["mensaje"])) {
      $clase = ($_SESSION["mensaje"][0] == "err") ? "flash-err" : "flash-ok";
      echo '<p class="flash ' . $clase . '" role="status">' . h($_SESSION["mensaje"][1]) . '</p>';
      unset($_SESSION["mensaje"]);
  }
  ?>
