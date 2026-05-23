<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

// 1. SEGURIDAD: Verificar que existan datos activos del flujo de pago en la sesión
if (!isset($_SESSION['stripe_checkout_trabajo']) || !isset($_SESSION['usuario_id'])) {
    header("Location: area_cliente.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_trabajo = intval($_SESSION['stripe_checkout_trabajo']);
$id_autonomo = intval($_SESSION['stripe_checkout_autonomo']);
$monto_total = floatval($_SESSION['stripe_checkout_monto']);

// 2. --- LÓGICA DE COMISIONES HASTA EL 20% (Fijado en Euros para Europa) ---
$comision_total = $monto_total * 0.20;       // El 20% total que se descuenta del proyecto
$neto_autonomo = $monto_total - $comision_total; // El 80% neto que va íntegro para el autónomo

// Tarifa Stripe Europa fija: 1.5% + 0.25 €
$comision_stripe = ($monto_total * 0.015) + 0.25;

// Lo que se queda Wirvux es el resto de la comisión total una vez pagado Stripe
$comision_wirvux_limpia = $comision_total - $comision_stripe;

// Evitamos que en importes extremadamente bajos la comisión de Wirvux quede en negativo por el fijo de 0.25
if ($comision_wirvux_limpia < 0) {
    $comision_wirvux_limpia = 0;
}

// A. ACTUALIZACIÓN DEL PROYECTO: Cambiamos el estado a 'completado'
$query_update = "UPDATE trabajos 
                 SET estado = 'completado' 
                 WHERE id = $id_trabajo AND id_cliente = $id_cliente";

if (mysqli_query($conexion, $query_update)) {
    
    // B. INGRESAR EN EL BALANCE DEL AUTÓNOMO
    // Sumamos el 80% neto al saldo acumulado del técnico que hizo el trabajo
    $update_saldo = "UPDATE usuarios 
                     SET saldo_disponible = saldo_disponible + $neto_autonomo 
                     WHERE id = $id_autonomo";
    mysqli_query($conexion, $update_saldo);

    // C. HISTORIAL DE PAGOS: Registramos la auditoría financiera desglosando ambas comisiones
    $insert_pago = "INSERT INTO pagos (id_trabajo, id_autonomo, monto_total, comision_stripe, comision_wirvux, neto_autonomo, estado_pago) 
                    VALUES ($id_trabajo, $id_autonomo, $monto_total, $comision_stripe, $comision_wirvux_limpia, $neto_autonomo, 'en_balance')";
    mysqli_query($conexion, $insert_pago);

    // 3. LIMPIAR SESIÓN: Borramos los datos temporales del pago para evitar recargas accidentales
    unset($_SESSION['stripe_checkout_trabajo']);
    unset($_SESSION['stripe_checkout_autonomo']);
    unset($_SESSION['stripe_checkout_monto']);

    // 4. ÉXITO: Redirigimos al cliente a tu pantalla original de confirmación
    header("Location: ver_propuestas.php?id=$id_trabajo&status=success");
    exit();

} else {
    die("Error crítico al asentar el pago en la base de datos: " . mysqli_error($conexion));
}
?>