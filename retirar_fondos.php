<?php
session_start();
include 'db.php';

// Seguridad: Solo autónomos logueados
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_autonomo = $_SESSION['usuario_id'];

// Cargar el SDK de Stripe de forma global (Solo se usa para la transferencia del retiro)
require_once __DIR__ . '/stripe-php/init.php';
\Stripe\Stripe::setApiKey('la sk');

// ===================================================
// ACCIÓN 1: VINCULAR CUENTA REAL (MANUAL POR INPUT)
// ===================================================
if (isset($_POST['acc_action']) && $_POST['acc_action'] === 'guardar_stripe_manual') {
    // Sanitizar el ID de Stripe introducido por el usuario
    $stripe_id_introducido = trim($_POST['stripe_connect_id_input']);

    if (!empty($stripe_id_introducido)) {
        // Guardamos exactamente el ID real que el usuario nos ha dicho
        $stmt_vincular = $conexion->prepare("UPDATE usuarios SET stripe_connect_id = ? WHERE id = ?");
        $stmt_vincular->bind_param("si", $stripe_id_introducido, $id_autonomo);
        
        if ($stmt_vincular->execute()) {
            $stmt_vincular->close();
            header("Location: retirar_fondos.php?status=cuenta_vinculada");
            exit();
        } else {
            die("Error al guardar el ID en la base de datos.");
        }
    } else {
        header("Location: retirar_fondos.php?error=id_vacio");
        exit();
    }
}

// ===================================================
// ACCIÓN 2: PROCESAR EL RETIRO DE FONDOS
// ===================================================
if (isset($_POST['action_retiro']) && $_POST['action_retiro'] === 'ejecutar_payout') {
    $monto_solicitado = floatval($_POST['monto']);

    // Validar datos actuales del autónomo
    $stmt_check = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
    $stmt_check->bind_param("i", $id_autonomo);
    $stmt_check->execute();
    $usuario_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $saldo_disponible_db = floatval($usuario_check['saldo_disponible']);
    $stripe_connect_id_db = $usuario_check['stripe_connect_id'];

    if (empty($stripe_connect_id_db)) {
        header("Location: retirar_fondos.php?error=sin_cuenta_stripe");
        exit();
    }
    if ($monto_solicitado < 1.00) {
        header("Location: retirar_fondos.php?error=minimo_no_alcanzado");
        exit();
    }
    if ($monto_solicitado > $saldo_disponible_db) {
        header("Location: retirar_fondos.php?error=saldo_insuficiente");
        exit();
    }

    // --- CÁLCULO: 20% FIJO PLATAFORMA ---
    $comision_wirvux = round($monto_solicitado * 0.20, 2);
    $saldo_neto_transferir = $monto_solicitado - $comision_wirvux;
    $monto_centimos_stripe = intval(round($saldo_neto_transferir * 100));

    try {
        // Ejecutar la transferencia hacia la cuenta real que se le dijo
        $transferencia = \Stripe\Transfer::create([
            'amount' => $monto_centimos_stripe,
            'currency' => 'eur',
            'destination' => $stripe_connect_id_db, // Usa el ID exacto guardado
            'description' => 'Retiro de fondos desde plataforma Wirvux',
        ]);

        if (isset($transferencia->id)) {
            // Descontar saldo bruto
            $stmt_update_saldo = $conexion->prepare("UPDATE usuarios SET saldo_disponible = saldo_disponible - ? WHERE id = ?");
            $stmt_update_saldo->bind_param("di", $monto_solicitado, $id_autonomo);
            
            if ($stmt_update_saldo->execute()) {
                $stmt_update_saldo->close();
                header("Location: retirar_fondos.php?status=retiro_exitoso");
                exit();
            } else {
                die("Error crítico al actualizar saldo.");
            }
        } else {
            header("Location: retirar_fondos.php?error=error_stripe_api");
            exit();
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        header("Location: retirar_fondos.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Obtener datos para renderizar la vista
$stmt_saldo = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
$stmt_saldo->bind_param("i", $id_autonomo);
$stmt_saldo->execute();
$res_saldo = $stmt_saldo->get_result();
$usuario = $res_saldo->fetch_assoc();
$stmt_saldo->close();

$saldo_actual = floatval($usuario['saldo_disponible']);
$stripe_connect_id = $usuario['stripe_connect_id'];

// Cálculo visual
$comision_wirvux = ($saldo_actual > 0) ? round($saldo_actual * 0.20, 2) : 0.00;
$saldo_neto_final = $saldo_actual - $comision_wirvux;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title data-key="title_page">Retirar Fondos | Wirvux</title>
    <style>
    :root {
        --primary-color: #635bff; --bg-color: #f4f7f6; --text-main: #212529; --text-light: #6c757d;
        --card-bg: #ffffff; --box-bg: #f8f9fa; --box-border: #e9ecef; --info-bg: #f4f6ff; --info-border: #d6deff; --input-border: #ccc;
    }
    body.dark-mode {
        --bg-color: #1f2937; --text-main: #e0e0e0; --text-light: #b0b0b0; --card-bg: #111827;
        --box-bg: #1f2937; --box-border: #374151; --info-bg: #1e1b4b; --info-border: #312e81; --input-border: #4b5563;
    }
    body { background-color: var(--bg-color); font-family: 'Segoe UI', sans-serif; color: var(--text-main); margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
    .report-container { background: var(--card-bg); max-width: 500px; margin: 50px auto; padding: 30px 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .back-link { text-decoration: none; color: var(--primary-color); display: inline-block; margin-bottom: 20px; font-weight: 600; }
    .report-header h2 { font-size: 1.8rem; margin: 0 0 8px 0; }
    .balance-box { background-color: var(--box-bg); border: 1px solid var(--box-border); padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    .info-box { background-color: var(--info-bg); border: 1px solid var(--info-border); padding: 15px; border-radius: 10px; margin-bottom: 25px; }
    .input-monto, .input-stripe { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--input-border); background-color: var(--card-bg); color: var(--text-main); font-size: 1.1em; box-sizing: border-box; outline: none; }
    .input-monto:focus, .input-stripe:focus { border-color: var(--primary-color); }
    .btn-action { width: 100%; background-color: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; font-size: 1em; }
    </style>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link" data-key="back_link">← Volver al Panel</a>
        
        <header class="report-header" style="margin-bottom: 25px;">
            <h2 data-key="header_title">Retirar Fondos de la Plataforma</h2>
            <p style="color: var(--text-light); font-size: 0.9em;" data-key="header_desc">El dinero se enviará a la cuenta de Stripe Connect que indiques abajo.</p>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'cuenta_vinculada'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;">
                <strong>¡Cuenta Guardada!</strong> El ID de Stripe se ha enlazado correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'retiro_exitoso'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;" data-key="status_success">
                <strong>¡Retiro Completado!</strong> Transferencia procesada con éxito.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #f5c6cb; font-size: 0.9em;">
                <strong data-key="err_label">Error:</strong> 
                <span>
                <?php 
                    switch($_GET['error']) {
                        case 'id_vacio': echo 'Por favor, introduce un ID de Stripe válido.'; break;
                        case 'minimo_no_alcanzado': echo 'El importe mínimo para retirar fondos es de 1,00 €.'; break;
                        case 'saldo_insuficiente': echo 'No dispones de suficiente saldo.'; break;
                        case 'sin_cuenta_stripe': echo 'Debes configurar una cuenta de Stripe abajo.'; break;
                        case 'error_stripe_api': echo 'La transferencia no se pudo procesar por Stripe.'; break;
                        default: echo htmlspecialchars($_GET['error']); break;
                    }
                ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="balance-box">
            <span style="font-size: 0.9em; color: var(--text-light); display: block; text-transform: uppercase; font-weight: bold;" data-key="box_title">Presupuesto Bruto de Proyectos</span>
            <strong style="font-size: 2.2em; color: var(--text-main); display: block; margin-bottom: 12px;"><?php echo number_format($saldo_actual, 2); ?> €</strong>
            <div style="border-top: 1px dashed var(--text-light); padding-top: 10px; font-size: 0.88em; line-height: 1.6em;">
                <div style="display: flex; justify-content: space-between;">
                    <span data-key="fee_wirvux">Comisión Wirvux (20%):</span>
                    <span style="color: #c53030; font-weight: bold;">- <?php echo number_format($comision_wirvux, 2); ?> €</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.05em; border-top: 1px solid var(--box-border); margin-top: 6px; padding-top: 6px;">
                    <span data-key="total_net">Total Neto a Recibir:</span>
                    <span style="color: #48bb78;"><?php echo number_format($saldo_neto_final, 2); ?> €</span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <span style="font-size: 0.85em; color: var(--text-light); display: block; font-weight: bold; margin-bottom: 8px;">⚙️ CONFIGURACIÓN DE TU CUENTA STRIPE REAL</span>
            <form action="retirar_fondos.php" method="POST">
                <input type="hidden" name="acc_action" value="guardar_stripe_manual">
                <input type="text" name="stripe_connect_id_input" class="input-stripe" style="font-family: monospace; font-size: 0.95em;" placeholder="Escribe tu ID de cuenta (Ej: acct_1XYZ...)" value="<?php echo htmlspecialchars($stripe_connect_id); ?>" required>
                <button type="submit" class="btn-action" style="background-color: #2d3748;">💾 Guardar / Actualizar Cuenta Real</button>
            </form>
            <?php if(!empty($stripe_connect_id)): ?>
                <small style="color: #48bb78; display: block; margin-top: 8px; font-weight: bold;">✓ Actualmente enviando pagos a esta cuenta.</small>
            <?php endif; ?>
        </div>

        <form action="retirar_fondos.php" method="POST">
            <input type="hidden" name="action_retiro" value="ejecutar_payout">
            <div style="margin-bottom: 15px;">
                <label style="font-size: 0.9em; display:block; margin-bottom: 6px; font-weight: bold;" data-key="input_label">Cantidad bruta a procesar (€):</label>
                <input type="number" name="monto" class="input-monto" step="0.01" min="1.00" max="<?php echo $saldo_actual; ?>" placeholder="0.00" required <?php echo empty($stripe_connect_id) ? 'disabled' : ''; ?>>
            </div>
            <button type="submit" data-key="btn_submit" <?php echo empty($stripe_connect_id) ? 'disabled style="background-color: #a0aec0;"' : 'style="background-color: #635bff; cursor: pointer;"'; ?> style="color: white; border: none; padding: 14px; border-radius: 6px; width: 100%; font-size: 1em; font-weight: bold;">
                Confirmar Cobro Instantáneo
            </button>
        </form>
    </div>

    <script>
    const translations = {
        'es': {
            'title_page': 'Retirar Fondos | Wirvux', 'back_link': '← Volver al Panel', 'header_title': 'Retirar Fondos de la Plataforma', 'header_desc': 'El dinero se enviará a la cuenta de Stripe Connect que indiques abajo.',
            'status_success': '<strong>¡Retiro Completado!</strong> Fondos transferidos con éxito.', 'err_label': 'Error:', 'box_title': 'Presupuesto Bruto', 'fee_wirvux': 'Comisión Wirvux (20%):', 'total_net': 'Total Neto:', 'input_label': 'Cantidad bruta (€):', 'btn_submit': 'Confirmar Cobro Instantáneo'
        },
        'en': {
            'title_page': 'Withdraw Funds | Wirvux', 'back_link': '← Back to Dashboard', 'header_title': 'Withdraw Funds', 'header_desc': 'Money will be sent to the Stripe Connect account you specify below.',
            'status_success': '<strong>Withdrawal Completed!</strong> Funds transferred successfully.', 'err_label': 'Error:', 'box_title': 'Gross Budget', 'fee_wirvux': 'Wirvux Fee (20%):', 'total_net': 'Total Net:', 'input_label': 'Gross amount (€):', 'btn_submit': 'Confirm Instant Payout'
        }
    };
    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });
        document.title = texts['title_page'];
    }
    (function() {
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');
        applyTranslations();
    })();
    </script>
</body>
</html>
