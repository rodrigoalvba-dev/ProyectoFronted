<?php
// Lista de usuarios registrados (solo administrador)
session_start();
require "../conexion.php";
require "../funciones.php";
$R = "../";
$enAdmin = true;
requiere_admin();

$usuarios = consulta("SELECT u.*, COUNT(p.id) AS pedidos, COALESCE(SUM(p.total), 0) AS gastado
                      FROM usuarios u
                      LEFT JOIN pedidos p ON p.usuario_id = u.id
                      GROUP BY u.id
                      ORDER BY u.id");

$titulo = "Usuarios";
include "../includes/encabezado.php";
?>
  <div>
    <h2 class="page-title">Usuarios</h2>
    <p class="sub"><?php echo count($usuarios); ?> cuenta(s) registradas</p>
  </div>

  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th class="num">Pedidos</th><th class="num">Gastado</th><th>Registro</th></tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u) { ?>
        <tr>
          <td class="num"><?php echo (int)$u["id"]; ?></td>
          <td class="name"><?php echo h($u["nombre"]); ?></td>
          <td><?php echo h($u["email"]); ?></td>
          <td><?php echo $u["rol"] == "admin" ? '<span class="pill">Admin</span>' : '<span class="pill off">Cliente</span>'; ?></td>
          <td class="num"><?php echo (int)$u["pedidos"]; ?></td>
          <td class="num"><?php echo dinero($u["gastado"]); ?></td>
          <td><?php echo h(date("d/m/Y", strtotime($u["creado_en"]))); ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
<?php include "../includes/pie.php"; ?>
