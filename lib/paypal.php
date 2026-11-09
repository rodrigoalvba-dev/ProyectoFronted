<?php
// =====================================================
// Funciones para hablar con PayPal (API REST v2) usando cURL
// =====================================================

// ¿Ya pusiste tus credenciales en conexion.php?
function paypal_configurado() {
    return substr(PAYPAL_CLIENT_ID, 0, 5) != "PEGA_" && substr(PAYPAL_SECRET, 0, 5) != "PEGA_";
}

// Dirección base: sandbox (pruebas) o live (real)
function paypal_url() {
    return (PAYPAL_MODO == "live") ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";
}

// Pide un token de acceso con el Client ID y el Secret
function paypal_token() {
    $ch = curl_init(paypal_url() . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $respuesta = curl_exec($ch);
    curl_close($ch);

    $datos = json_decode((string)$respuesta, true);
    if (!isset($datos["access_token"])) {
        throw new Exception("No se pudo autenticar con PayPal");
    }
    return $datos["access_token"];
}

// Llama a la API. Devuelve [código HTTP, respuesta en arreglo]
function paypal_api($metodo, $ruta, $cuerpo = null) {
    $ch = curl_init(paypal_url() . $ruta);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . paypal_token(),
        "Content-Type: application/json",
        "Accept: application/json",
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    if ($cuerpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo, JSON_UNESCAPED_UNICODE));
    }
    $respuesta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $datos = json_decode((string)$respuesta, true);
    if (!is_array($datos)) $datos = [];
    return [$codigo, $datos];
}

function paypal_token() {
    $ch = curl_init(paypal_url() . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $datos = json_decode((string)$respuesta, true);
    if (!isset($datos["access_token"])) {
        throw new Exception("PayPal token falló. HTTP $codigo. cURL: $error. Respuesta: $respuesta");
    }
    return $datos["access_token"];
}

?>
