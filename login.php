<?php
// Iniciar sesión (como usuario.html + procesar.php de las prácticas, pero con la base de datos)
session_start();
require "conexion.php";
require "funciones.php";
$R = "";

if (isset($_SESSION["usuario_id"])) {
    header("Location: " . ($_SESSION["rol"] == "admin" ? "admin/index.php" : "index.php"));
    exit;
}

$error = "";
$email = "";
// A qué página volver después de entrar (solo nombres como "checkout.php")
$siguiente = isset($_GET["next"]) ? $_GET["next"] : "";
if (!preg_match('/^[A-Za-z0-9_]+\.php$/', $siguiente)) $siguiente = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    $filas = consulta("SELECT * FROM usuarios WHERE email = ?", "s", [$email]);

    if (count($filas) > 0 && password_verify($password, $filas[0]["password_hash"])) {
        $u = $filas[0];
        // Variables de sesión del usuario
        session_regenerate_id(true);
        $_SESSION["usuario_id"] = (int)$u["id"];
        $_SESSION["usuario"]    = $u["nombre"];
        $_SESSION["rol"]        = $u["rol"];

        if ($u["rol"] == "admin") {
            header("Location: admin/index.php");
        } else {
            header("Location: " . ($siguiente != "" ? $siguiente : "index.php"));
        }
        exit;
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}

$titulo = "Entrar";
include "includes/encabezado.php";
?>
  <form class="composer narrow" method="post" action="login.php<?php if ($siguiente != "") echo "?next=" . urlencode($siguiente); ?>">
    <h2>Iniciar sesión</h2>
    <div class="field">
      <label for="email">Correo</label>
      <input class="input" id="email" type="email" name="email" value="<?php echo h($email); ?>" required>
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input class="input" id="password" type="password" name="password" required>
      <?php if ($error != "") { ?><p class="err"><?php echo h($error); ?></p><?php } ?>
    </div>
    <button class="btn primary" type="submit">Entrar</button>
    <p class="hint">¿No tienes cuenta? <a href="registro.php">Crear cuenta</a></p>
  </form>
<?php include "includes/pie.php"; ?>
