<?php
session_start();
include 'db.php';

// 1. SEGURIDAD REFORZADA: Si no es una petición POST, lo mandamos a la página anterior
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: retirar_fondos.php");
    exit();
}

// 2. SEGURIDAD: Solo autónomos logueados
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

// Carga manual del SDK de Stripe
require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey('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');

$id_autonomo = $_SESSION['usuario_id'];
$monto_bruto_retirar = floatval($_POST['monto']);

// 1. Obtener datos de la BD de manera segura
$stmt_usuario = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
$stmt_usuario->bind_param("i", $id_autonomo);
$stmt_usuario->execute();
$usuario = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

$saldo_actual = floatval($usuario['saldo_disponible']);
$stripe_connect_id = $usuario['stripe_connect_id'];

// 2. VALIDACIONES LÓGICAS
if ($monto_bruto_retirar < 0.50) {
    header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
    exit();
}
if ($monto_bruto_retirar > $saldo_actual) {
    header("Location: retirar_fondos.php?error=saldo_insuficiente");
    exit();
}
if (empty($stripe_connect_id)) {
    header("Location: retirar_fondos.php?error=sin_cuenta_stripe");
    exit();
}

// =========================================================================
// MATEMÁTICA Y COMISIONES
// =========================================================================
$stripe_centimos = ($monto_bruto_retirar * 100 * 0.015) + 25;
$comision_stripe = ceil($stripe_centimos) / 100;
$restante_tras_stripe = max(0, $monto_bruto_retirar - $comision_stripe);
$comision_wirvux = round($restante_tras_stripe * 0.185, 2);

if ($monto_bruto_retirar <= 1.00) {
    $comision_wirvux = ($monto_bruto_retirar == 1.00) ? 0.14 : 0.00;
}

$monto_neto_final = max(0, $restante_tras_stripe - $comision_wirvux);

if ($monto_neto_final < 0.50) {
    header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
    exit();
}

$monto_neto_centimos = round($monto_neto_final * 100);

// =========================================================================

try {
    // 5. EJECUTAR TRANSFERENCIA EN STRIPE
    $transferencia = \Stripe\Transfer::create([
        'amount' => $monto_neto_centimos, 
        'currency' => 'eur',
        'destination' => $stripe_connect_id,
        'description' => 'Retiro neto Wirvux',
    ]);
    
    // 6. ACTUALIZAR BASE DE DATOS LOCAL CON BLINDAJE
    mysqli_begin_transaction($conexion);
    
    // UPDATE blindado: Resta solo si el saldo es suficiente en ese preciso instante
    $stmt_update = $conexion->prepare("UPDATE usuarios SET saldo_disponible = saldo_disponible - ? WHERE id = ? AND saldo_disponible >= ?");
    $stmt_update->bind_param("did", $monto_bruto_retirar, $id_autonomo, $monto_bruto_retirar);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows === 0) {
        mysqli_rollback($conexion);
        header("Location: retirar_fondos.php?error=saldo_insuficiente");
        exit();
    }
    $stmt_update->close();
    
    // Registrar el log
    $detalles = "Stripe ID: {$transferencia->id} | Neto: {$monto_neto_final}€ | Tasas: Stripe {$comision_stripe}€, Wirvux {$comision_wirvux}€";
    $stmt_log = $conexion->prepare("INSERT INTO retiros (id_autonomo, monto, detalles_pago, estado) VALUES (?, ?, ?, 'completado')");
    $stmt_log->bind_param("ids", $id_autonomo, $monto_bruto_retirar, $detalles);
    $stmt_log->execute();
    $stmt_log->close();
    
    mysqli_commit($conexion);
    header("Location: retirar_fondos.php?status=retiro_exitoso");
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Error de Stripe: " . $e->getMessage());
    header("Location: retirar_fondos.php?error=error_stripe_api");
}
exit();
?>
