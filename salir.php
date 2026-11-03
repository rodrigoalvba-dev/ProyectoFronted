<?php
// Cerrar sesión (igual que cerrar.php de las prácticas)
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
