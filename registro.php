<?php
// Registro de clientes nuevos
session_start();
require "conexion.php";
require "funciones.php";
$R = "";

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$errores = [];
$nombre = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre   = trim($_POST["nombre"] ?? "");
    $email    = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if ($nombre == "" || mb_strlen($nombre) > 100) $errores["nombre"] = "Escribe tu nombre (máximo 100 caracteres).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores["email"] = "Correo no válido.";
    if (strlen($password) < 8) $errores["password"] = "La contraseña debe tener al menos 8 caracteres.";

    if (count($errores) == 0) {
        $existe = consulta("SELECT id FROM usuarios WHERE email = ?", "s", [$email]);
        if (count($existe) > 0) {
            $errores["email"] = "Ese correo ya está registrado.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);    // la contraseña nunca se guarda en texto plano
            ejecutar("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, 'cliente')", "sss", [$nombre, $email, $hash]);

            $_SESSION["usuario_id"] = mysqli_insert_id($conexion);
            $_SESSION["usuario"]    = $nombre;
            $_SESSION["rol"]        = "cliente";
            mensaje("¡Bienvenido, " . $nombre . "! Tu cuenta fue creada.");
            header("Location: index.php");
            exit;
        }
    }
}

$titulo = "Crear cuenta";
include "includes/encabezado.php";
?>
  <form class="composer narrow" method="post" novalidate>
    <h2>Crear cuenta</h2>
    <div class="field">
      <label for="nombre">Nombre</label>
      <input class="input" id="nombre" type="text" name="nombre" maxlength="100" value="<?php echo h($nombre); ?>">
      <?php if (isset($errores["nombre"])) { ?><p class="err"><?php echo h($errores["nombre"]); ?></p><?php } ?>
    </div>
    <div class="field">
      <label for="email">Correo</label>
      <input class="input" id="email" type="email" name="email" value="<?php echo h($email); ?>">
      <?php if (isset($errores["email"])) { ?><p class="err"><?php echo h($errores["email"]); ?></p><?php } ?>
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input class="input" id="password" type="password" name="password">
      <?php if (isset($errores["password"])) { ?><p class="err"><?php echo h($errores["password"]); ?></p><?php } ?>
    </div>
    <button class="btn primary" type="submit">Registrarme</button>
    <p class="hint">¿Ya tienes cuenta? <a href="login.php">Entrar</a></p>
  </form>
<?php include "includes/pie.php"; ?>
