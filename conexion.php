<?php
// =====================================================
// CONEXIÓN A LA BASE DE DATOS Y CONFIGURACIÓN GENERAL
// Todas las páginas lo incluyen con: require "conexion.php";
// =====================================================

// Datos de la base de datos (en tu hosting casi nunca es "localhost")
$dbhost = "localhost";
$dbname = "tienda";     // <-- pon aquí el nombre de tu base de datos
$dbuser = "root";
$dbpass = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // los errores de MySQL lanzan excepción

$conexion = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)
            or die("Problemas con la conexión: " . mysqli_connect_error());
mysqli_set_charset($conexion, "utf8mb4");

date_default_timezone_set("America/Mexico_City");

// ---------- PayPal ----------
// Crea una app en https://developer.paypal.com/dashboard/applications
define("PAYPAL_MODO", "sandbox");                 // "sandbox" = pruebas, "live" = dinero real
define("PAYPAL_CLIENT_ID", "PEGA_AQUI_TU_CLIENT_ID");
define("PAYPAL_SECRET", "PEGA_AQUI_TU_SECRET");
define("MONEDA", "MXN");

define("TIENDA_NOMBRE", "Agenda de Actividades");
define("STOCK_BAJO", 5);                          // stock igual o menor a este número = "stock bajo"
?>
