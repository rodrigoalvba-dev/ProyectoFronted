<?php
// Agrega un producto al carrito (variable de sesión), igual que cantidad.php de las prácticas
session_start(); // guarda la variable de sesión
require "conexion.php";
require "funciones.php";

$id       = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
$cantidad = isset($_POST["cantidad"]) ? (int)$_POST["cantidad"] : 0;

if ($cantidad > 0) {
    $filas = consulta("SELECT id, nombre, stock FROM productos WHERE id = ? AND activo = 1", "i", [$id]);

    if (count($filas) == 0) {
        mensaje("Ese producto ya no está disponible.", "err");
    } else {
        $p = $filas[0];
        if (!isset($_SESSION["carrito"])) {
            $_SESSION["carrito"] = [];
        }
        $actual = isset($_SESSION["carrito"][$id]) ? $_SESSION["carrito"][$id] : 0;
        $nuevo = $actual + $cantidad;

        if ($nuevo > $p["stock"]) {                 // no se puede pedir más de lo que hay
            $nuevo = (int)$p["stock"];
            mensaje("Solo hay " . $p["stock"] . " pieza(s) de “" . $p["nombre"] . "”.", "err");
        } else {
            mensaje("“" . $p["nombre"] . "” agregado al carrito.");
        }
        $_SESSION["carrito"][$id] = $nuevo;
    }
}

header("Location: index.php");
exit;
?>
