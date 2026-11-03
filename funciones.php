<?php
// =====================================================
// FUNCIONES COMUNES (se incluyen después de conexion.php)
// =====================================================

// Categorías: clave => [nombre, variable de color del CSS]
$CATEGORIAS = [
    "diaria"     => ["Diaria",     "--c-deporte"],
    "semanal"    => ["Semanal",    "--c-estudio"],
    "estudio"    => ["Estudio",    "--c-social"],
    "trabajo"    => ["Trabajo",    "--c-trabajo"],
    "accesorios" => ["Accesorios", "--c-casa"],
];

// Escapa texto para mostrarlo en HTML (igual que htmlspecialchars de las prácticas)
function h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

// Formato de dinero: $1,234.50 MXN
function dinero($n) {
    return "$" . number_format((float)$n, 2) . " " . MONEDA;
}

function nombre_categoria($clave) {
    global $CATEGORIAS;
    return isset($CATEGORIAS[$clave]) ? $CATEGORIAS[$clave][0] : ucfirst($clave);
}

function color_categoria($clave) {
    global $CATEGORIAS;
    return isset($CATEGORIAS[$clave]) ? $CATEGORIAS[$clave][1] : "--c-otro";
}

// SELECT con consulta preparada. Devuelve un arreglo de filas.
// Ejemplo: consulta("SELECT * FROM productos WHERE id=?", "i", [3]);
function consulta($sql, $tipos = "", $datos = []) {
    global $conexion;
    $stmt = mysqli_prepare($conexion, $sql);
    if ($tipos != "") {
        mysqli_stmt_bind_param($stmt, $tipos, ...$datos);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $filas = $resultado ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $filas;
}

// INSERT / UPDATE / DELETE con consulta preparada. Devuelve las filas afectadas.
// El id nuevo se obtiene después con mysqli_insert_id($conexion).
function ejecutar($sql, $tipos = "", $datos = []) {
    global $conexion;
    $stmt = mysqli_prepare($conexion, $sql);
    if ($tipos != "") {
        mysqli_stmt_bind_param($stmt, $tipos, ...$datos);
    }
    mysqli_stmt_execute($stmt);
    $filas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $filas;
}

// Mensaje que se muestra una sola vez en la siguiente página
function mensaje($texto, $tipo = "ok") {
    $_SESSION["mensaje"] = [$tipo, $texto];
}

// Solo usuarios con sesión iniciada (como bienvenida.php de las prácticas)
function requiere_login($ruta = "") {
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: " . $ruta . "login.php");
        exit;
    }
}

// Solo administradores
function requiere_admin($ruta = "../") {
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: " . $ruta . "login.php");
        exit;
    }
    if ($_SESSION["rol"] != "admin") {
        die("Acceso restringido a administradores.");
    }
}

// Total de piezas en el carrito (variable de sesión)
function piezas_carrito() {
    if (!isset($_SESSION["carrito"])) return 0;
    return array_sum($_SESSION["carrito"]);
}

// Lee el carrito de la sesión y trae precio y stock REALES desde la base de datos.
// Devuelve [lista de productos, total]
function carrito_detalle() {
    $items = [];
    $total = 0;
    if (!isset($_SESSION["carrito"])) {
        return [$items, $total];
    }
    foreach ($_SESSION["carrito"] as $id => $cantidad) {
        $filas = consulta("SELECT * FROM productos WHERE id = ? AND activo = 1", "i", [(int)$id]);
        if (count($filas) == 0) continue;           // el producto ya no existe
        $p = $filas[0];
        $cantidad = min((int)$cantidad, (int)$p["stock"]);
        if ($cantidad < 1) continue;                // sin stock
        $p["cantidad"] = $cantidad;
        $p["subtotal"] = round($p["precio"] * $cantidad, 2);
        $total += $p["subtotal"];
        $items[] = $p;
    }
    return [$items, round($total, 2)];
}
?>
