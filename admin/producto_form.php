<?php
// Crear o editar un producto (si llega ?id=5 es edición)
session_start();
require "../conexion.php";
require "../funciones.php";
$R = "../";
$enAdmin = true;
requiere_admin();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : (isset($_POST["id"]) ? (int)$_POST["id"] : 0);

// Valores iniciales (producto nuevo)
$p = ["nombre" => "", "descripcion" => "", "categoria" => "diaria", "precio" => "", "stock" => 0, "imagen" => "", "activo" => 1];

if ($id > 0) {
    $filas = consulta("SELECT * FROM productos WHERE id = ?", "i", [$id]);
    if (count($filas) == 0) {
        mensaje("Producto no encontrado.", "err");
        header("Location: productos.php");
        exit;
    }
    $p = $filas[0];
}

$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p["nombre"]      = trim($_POST["nombre"]);
    $p["descripcion"] = trim($_POST["descripcion"]);
    $p["categoria"]   = $_POST["categoria"];
    $p["precio"]      = str_replace(",", ".", trim($_POST["precio"]));
    $p["stock"]       = trim($_POST["stock"]);
    $p["imagen"]      = trim($_POST["imagen"]);
    $p["activo"]      = isset($_POST["activo"]) ? 1 : 0;

    // Validaciones
    if ($p["nombre"] == "" || mb_strlen($p["nombre"]) > 120) $errores["nombre"] = "Nombre obligatorio (máximo 120 caracteres).";
    if (!isset($CATEGORIAS[$p["categoria"]])) $errores["categoria"] = "Elige una categoría.";
    if (!is_numeric($p["precio"]) || (float)$p["precio"] <= 0) $errores["precio"] = "El precio debe ser mayor a 0.";
    if (!ctype_digit($p["stock"])) $errores["stock"] = "El stock debe ser un número entero (0 o más).";
    if ($p["imagen"] != "" && !preg_match('#^https?://#i', $p["imagen"])) $errores["imagen"] = "Usa una URL que empiece con http:// o https://";

    if (count($errores) == 0) {
        $precio = round((float)$p["precio"], 2);
        $stock  = (int)$p["stock"];
        $imagen = $p["imagen"] == "" ? null : $p["imagen"];
        $desc   = $p["descripcion"] == "" ? null : $p["descripcion"];

        if ($id > 0) {
            ejecutar("UPDATE productos SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, imagen = ?, activo = ? WHERE id = ?",
                     "sssdisii",
                     [$p["nombre"], $desc, $p["categoria"], $precio, $stock, $imagen, $p["activo"], $id]);
            mensaje("Producto actualizado.");
        } else {
            ejecutar("INSERT INTO productos (nombre, descripcion, categoria, precio, stock, imagen, activo) VALUES (?, ?, ?, ?, ?, ?, ?)",
                     "sssdisi",
                     [$p["nombre"], $desc, $p["categoria"], $precio, $stock, $imagen, $p["activo"]]);
            mensaje("Producto creado.");
        }
        header("Location: productos.php");
        exit;
    }
}

$titulo = $id > 0 ? "Editar producto" : "Nuevo producto";
include "../includes/encabezado.php";
?>
  <form class="composer narrow" method="post" novalidate style="max-width:560px">
    <h2><?php echo h($titulo); ?></h2>
    <input type="hidden" name="id" value="<?php echo $id; ?>">

    <div class="field">
      <label for="nombre">Nombre</label>
      <input class="input" id="nombre" type="text" name="nombre" maxlength="120" value="<?php echo h($p["nombre"]); ?>">
      <?php if (isset($errores["nombre"])) { ?><p class="err"><?php echo h($errores["nombre"]); ?></p><?php } ?>
    </div>

    <div class="field">
      <label for="descripcion">Descripción</label>
      <textarea class="input" id="descripcion" name="descripcion"><?php echo h($p["descripcion"]); ?></textarea>
    </div>

    <div class="row2">
      <div class="field">
        <label for="categoria">Categoría</label>
        <select class="input" id="categoria" name="categoria">
          <?php foreach ($CATEGORIAS as $clave => $info) { ?>
            <option value="<?php echo $clave; ?>" <?php if ($p["categoria"] == $clave) echo "selected"; ?>><?php echo h($info[0]); ?></option>
          <?php } ?>
        </select>
        <?php if (isset($errores["categoria"])) { ?><p class="err"><?php echo h($errores["categoria"]); ?></p><?php } ?>
      </div>
      <div class="field">
        <label for="precio">Precio (<?php echo h(MONEDA); ?>)</label>
        <input class="input" id="precio" type="text" name="precio" inputmode="decimal" value="<?php echo h($p["precio"]); ?>">
        <?php if (isset($errores["precio"])) { ?><p class="err"><?php echo h($errores["precio"]); ?></p><?php } ?>
      </div>
    </div>

    <div class="row2">
      <div class="field">
        <label for="stock">Stock</label>
        <input class="input" id="stock" type="number" name="stock" min="0" value="<?php echo h($p["stock"]); ?>">
        <?php if (isset($errores["stock"])) { ?><p class="err"><?php echo h($errores["stock"]); ?></p><?php } ?>
      </div>
      <div class="field">
        <label for="imagen">URL de imagen</label>
        <input class="input" id="imagen" type="text" name="imagen" placeholder="Opcional" value="<?php echo h($p["imagen"]); ?>">
        <?php if (isset($errores["imagen"])) { ?><p class="err"><?php echo h($errores["imagen"]); ?></p><?php } ?>
      </div>
    </div>

    <label style="display:flex;gap:10px;align-items:center">
      <input type="checkbox" name="activo" value="1" <?php if ($p["activo"]) echo "checked"; ?>> Visible en la tienda
    </label>

    <div style="display:flex;gap:10px">
      <button class="btn primary" type="submit">Guardar</button>
      <a class="btn ghost" href="productos.php">Cancelar</a>
    </div>
  </form>
<?php include "../includes/pie.php"; ?>
