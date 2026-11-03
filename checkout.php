<?php
// Página de pago: muestra el resumen y los botones de PayPal
session_start();
require "conexion.php";
require "funciones.php";
require "lib/paypal.php";
$R = "";

requiere_login();               // hay que iniciar sesión para pagar

list($items, $total) = carrito_detalle();
if (count($items) == 0) {
    mensaje("Tu carrito está vacío.", "err");
    header("Location: carrito.php");
    exit;
}

$titulo = "Pagar";
include "includes/encabezado.php";
?>
  <h2 class="page-title">Finalizar compra</h2>
  <div class="layout2">
    <ul class="items">
      <?php foreach ($items as $p) { ?>
      <li class="item cart-item" style="grid-template-columns:minmax(0,1fr) auto">
        <div class="body">
          <p class="title"><?php echo h($p["nombre"]); ?></p>
          <div class="meta"><span class="time"><?php echo (int)$p["cantidad"]; ?> × <?php echo dinero($p["precio"]); ?></span></div>
        </div>
        <span class="sub-amt"><?php echo dinero($p["subtotal"]); ?></span>
      </li>
      <?php } ?>
    </ul>

    <aside class="summary">
      <h2>Pago</h2>
      <div class="line total"><span>Total</span><span><?php echo dinero($total); ?></span></div>

      <?php if (!paypal_configurado()) { ?>
        <p class="flash flash-err">PayPal no está configurado. Agrega tu Client ID y Secret en <code>conexion.php</code>.</p>
      <?php } else { ?>
        <div id="paypal-buttons" class="paypal-box"></div>
        <p class="err" id="pp-error" hidden></p>
        <p class="hint">Se cobra en <?php echo h(MONEDA); ?>. Al terminar recibirás tu recibo en PDF.</p>
      <?php } ?>
      <a class="btn ghost" href="carrito.php">Volver al carrito</a>
    </aside>
  </div>

<?php if (paypal_configurado()) { ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode(PAYPAL_CLIENT_ID); ?>&currency=<?php echo urlencode(MONEDA); ?>&intent=capture&locale=es_MX"></script>
<script>
var errorBox = document.getElementById("pp-error");
function mostrarError(texto) {
    errorBox.textContent = texto;
    errorBox.hidden = false;
}

paypal.Buttons({
    // 1) El navegador pide al servidor que cree la orden (el total lo calcula el servidor)
    createOrder: function () {
        errorBox.hidden = true;
        return fetch("paypal_crear.php", { method: "POST" })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.id) { mostrarError(d.error || "No se pudo crear la orden."); throw new Error(d.error); }
                return d.id;
            });
    },
    // 2) El cliente aprobó el pago en PayPal: el servidor lo captura y guarda el pedido
    onApprove: function (data) {
        return fetch("paypal_capturar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ orderID: data.orderID })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.ok) { window.location.href = d.redirigir; }
            else { mostrarError(d.error || "No se pudo completar el pago."); }
        });
    },
    onCancel: function () { mostrarError("Cancelaste el pago. Tu carrito sigue intacto."); },
    onError: function () { mostrarError("Ocurrió un error con PayPal. Intenta de nuevo."); }
}).render("#paypal-buttons");
</script>
<?php } ?>
<?php include "includes/pie.php"; ?>
