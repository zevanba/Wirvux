<?php
session_start();
include 'db.php';

// 1. SEGURIDAD REFORZADA: Si no es una petición POST (vía formulario), lo mandamos a la nueva página
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: retirar_fondos.php");
    exit();
}

// 2. SEGURIDAD: Solo autónomos logueados
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

// Carga manual del SDK de Stripe real
require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey('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');

$id_autonomo = $_SESSION['usuario_id'];
$monto_bruto_retirar = floatval($_POST['monto']); // El valor bruto que solicita del formulario (ej: 1.00)

// 1. Obtener datos de la BD de manera segura con Consultas Preparadas
$stmt_usuario = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
$stmt_usuario->bind_param("i", $id_autonomo);
$stmt_usuario->execute();
$res_usuario = $stmt_usuario->get_result();
$usuario = $res_usuario->fetch_assoc();
$stmt_usuario->close();

$saldo_actual = floatval($usuario['saldo_disponible']);
$stripe_connect_id = $usuario['stripe_connect_id'];

// 2. VALIDACIÓN 1: Mínimo inicial de 0,50 € brutos introducidos
if ($monto_bruto_retirar < 0.50) {
    header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
    exit();
}

// 3. VALIDACIÓN 2: Saldo disponible suficiente
if ($monto_bruto_retirar > $saldo_actual) {
    header("Location: retirar_fondos.php?error=saldo_insuficiente");
    exit();
}

// 4. VALIDACIÓN 3: Cuenta vinculada
if (empty($stripe_connect_id)) {
    header("Location: retirar_fondos.php?error=sin_cuenta_stripe");
    exit();
}

// =========================================================================
// APLICACIÓN DE LA LÓGICA DE NEGOCIO REAL (Matemática exacta en cascada)
// =========================================================================

// 1. Calcular tasa de Stripe en producción (1.5% + 0.25€) redondeada hacia arriba
$stripe_centimos = ($monto_bruto_retirar * 100 * 0.015) + 25;
$comision_stripe = ceil($stripe_centimos) / 100;

// 2. Restante tras la pasarela
$restante_tras_stripe = $monto_bruto_retirar - $comision_stripe;
if ($restante_tras_stripe < 0) $restante_tras_stripe = 0;

// 3. Calcular comisión de Wirvux (18.5% sobre el dinero restante tras Stripe)
$comision_wirvux = round($restante_tras_stripe * 0.185, 2);

// Regla excepcional para micropagos controlados (Caso 1,00 € exacto)
if ($monto_bruto_retirar <= 1.00) {
    $comision_wirvux = ($monto_bruto_retirar == 1.00) ? 0.14 : 0.00;
}

// 4. Importe NETO real que se enviará al banco del usuario
$monto_neto_final = $restante_tras_stripe - $comision_wirvux;

// BLINDAJE DE SEGURIDAD CRÍTICO: Stripe Connect no procesa transferencias menores a 0,50 € Netos hacia el banco
if ($monto_neto_final < 0.50) {
    header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
    exit();
}

// Pasamos el importe NETO final a céntimos estrictos para la API de Stripe
$monto_neto_centimos = round($monto_neto_final * 100);

// =========================================================================

try {
    // 5. EJECUTAR TRANSFERENCIA REAL EN LA API DE STRIPE (Enviando solo el NETO final)
    $transferencia = \Stripe\Transfer::create([
        'amount' => $monto_neto_centimos, 
        'currency' => 'eur',
        'destination' => $stripe_connect_id,
        'description' => 'Retiro neto (Wirvux) tras tasas de pasarela e intermediación.',
    ]);
    
    // 6. ACTUALIZAR BASE DE DATOS LOCAL
    mysqli_begin_transaction($conexion);
    
    // Restamos el BRUTO solicitado para limpiar su balance acumulado por completo
    $query_restar = "UPDATE usuarios SET saldo_disponible = saldo_disponible - $monto_bruto_retirar WHERE id = $id_autonomo";
    $update_ok = mysqli_query($conexion, $query_restar);
    
    // Guardamos el registro reflejando con transparencia el bruto, el neto transferido y el desglose de comisiones
    $detalles_pago = "Stripe Payout ID: {$transferencia->id} | Neto enviado: {$monto_neto_final}€ | Tasas: Stripe {$comision_stripe}€, Wirvux {$comision_wirvux}€";
    $detalles_pago = mysqli_real_escape_string($conexion, $detalles_pago);

    $query_log = "INSERT INTO retiros (id_autonomo, monto, detalles_pago, estado) 
                  VALUES ($id_autonomo, $monto_bruto_retirar, '$detalles_pago', 'completado')";
    $insert_ok = mysqli_query($conexion, $query_log);
    
    if ($update_ok && $insert_ok) {
        mysqli_commit($conexion);
        header("Location: retirar_fondos.php?status=retiro_exitoso");
    } else {
        mysqli_rollback($conexion);
        // NOTA: Si esto pasa, el dinero se envió pero no se registró localmente debido a la BD.
        error_log("CRÍTICO: Retiro Stripe OK ({$transferencia->id}) pero falló guardado local para usuario ID: $id_autonomo");
        header("Location: retirar_fondos.php?error=error_guardado_local");
    }
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Error de Stripe Payout Real: " . $e->getMessage());
    header("Location: retirar_fondos.php?error=error_stripe_api");
}
exit();
?>