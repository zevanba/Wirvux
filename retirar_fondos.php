<?php
session_start();
include 'db.php';

// Seguridad: Solo autónomos logueados
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_autonomo = $_SESSION['usuario_id'];

// Obtener el protocolo y dominio dinámicamente para las redirecciones de Stripe
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$dominio_actual = $protocolo . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
// Limpiamos barras invertidas si estamos en local de Windows
$dominio_actual = rtrim(str_replace('\\', '/', $dominio_actual), '/');

// INTEGRACIÓN REAL DE STRIPE CONNECT PARA PRODUCCIÓN
if (isset($_POST['acc_action']) && $_POST['acc_action'] === 'vincular_stripe') {
    
    // 1. Cargar el SDK de Stripe y configurar tu clave secreta de producción
    require_once __DIR__ . '/stripe-php/init.php';
    \Stripe\Stripe::setApiKey('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');

    try {
        // 2. Crear una cuenta Express real en los servidores de Stripe
        $cuenta = \Stripe\Account::create([
            'type' => 'express',
            'country' => 'ES', // España
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
        ]);

        $stripe_connect_id_real = $cuenta->id;

        // 3. Guardar el ID auténtico devuelto por Stripe en tu base de datos (Consulta Preparada)
        $stmt_vincular = $conexion->prepare("UPDATE usuarios SET stripe_connect_id = ? WHERE id = ?");
        $stmt_vincular->bind_param("si", $stripe_connect_id_real, $id_autonomo);
        $stmt_vincular->execute();
        $stmt_vincular->close();

        // 4. Generar el enlace de Onboarding apuntando de forma dinámica a tu servidor
        $enlace_onboarding = \Stripe\AccountLink::create([
            'account' => $stripe_connect_id_real,
            'refresh_url' => $dominio_actual . '/retirar_fondos.php?error=onboarding_reiniciado',
            'return_url' => $dominio_actual . '/retirar_fondos.php?status=cuenta_vinculada',
            'type' => 'account_onboarding',
        ]);

        // 5. Redirigir al autónomo a la pasarela segura de Stripe para que vincule su banco
        header("Location: " . $enlace_onboarding->url);
        exit();

    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Error al crear cuenta Connect: " . $e->getMessage());
        header("Location: retirar_fondos.php?error=error_api_connect");
        exit();
    }
}

// Obtener los datos actuales del autónomo de forma segura con consultas preparadas
$stmt_saldo = $conexion->prepare("SELECT saldo_disponible, stripe_connect_id FROM usuarios WHERE id = ?");
$stmt_saldo->bind_param("i", $id_autonomo);
$stmt_saldo->execute();
$res_saldo = $stmt_saldo->get_result();
$usuario = $res_saldo->fetch_assoc();
$stmt_saldo->close();

$saldo_actual = floatval($usuario['saldo_disponible']);
$stripe_connect_id = $usuario['stripe_connect_id'];

// NUEVA LÓGICA DE NEGOCIO EN PRODUCCIÓN (BASADA EN 1,00 €)
if ($saldo_actual > 0) {
    // 1. Tasa estricta de Stripe Europa en Producción (1.5% + 0.25€) redondeando al céntimo superior
    $stripe_centimos = ($saldo_actual * 100 * 0.015) + 25;
    $comision_stripe = ceil($stripe_centimos) / 100;

    // 2. Dinero líquido restante en la plataforma tras la pasarela
    $restante_tras_stripe = $saldo_actual - $comision_stripe;
    if ($restante_tras_stripe < 0) $restante_tras_stripe = 0;

    // 3. Comisión de Wirvux: 18,5% sobre lo que ha quedado limpio de Stripe
    $comision_wirvux = round($restante_tras_stripe * 0.185, 2);

    // Protegemos el saldo en importes ultra bajos para evitar quiebres de flujo
    if ($saldo_actual <= 1.00) {
        $comision_wirvux = ($saldo_actual == 1.00) ? 0.14 : 0.00;
    }

    $saldo_neto_final = $restante_tras_stripe - $comision_wirvux;
    if ($saldo_neto_final < 0) $saldo_neto_final = 0;
} else {
    $comision_stripe = 0.00;
    $comision_wirvux = 0.00;
    $saldo_neto_final = 0.00;
}
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
        --primary-color: #635bff;
        --bg-color: #f4f7f6;
        --text-main: #212529;
        --text-light: #6c757d;
        --card-bg: #ffffff;
        --box-bg: #f8f9fa;
        --box-border: #e9ecef;
        --info-bg: #f4f6ff;
        --info-border: #d6deff;
        --input-border: #ccc;
    }

    body.dark-mode {
        --bg-color: #1f2937;
        --text-main: #e0e0e0;
        --text-light: #b0b0b0;
        --card-bg: #111827;
        --box-bg: #1f2937;
        --box-border: #374151;
        --info-bg: #1e1b4b;
        --info-border: #312e81;
        --input-border: #4b5563;
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--text-main);
        margin: 0;
        padding: 0;
        transition: background-color 0.3s, color 0.3s;
    }

    .report-container {
        background: var(--card-bg);
        max-width: 500px;
        margin: 50px auto;
        padding: 30px 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: background-color 0.3s;
    }

    .back-link {
        text-decoration: none;
        color: var(--primary-color);
        display: inline-block;
        margin-bottom: 20px;
        font-weight: 600;
        transition: color 0.2s;
    }

    .report-header h2 {
        font-size: 1.8rem;
        margin: 0 0 8px 0;
        color: var(--text-main);
    }

    .balance-box {
        background-color: var(--box-bg);
        border: 1px solid var(--box-border);
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        transition: background-color 0.3s, border-color 0.3s;
    }

    .info-box {
        background-color: var(--info-bg);
        border: 1px solid var(--info-border);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        transition: background-color 0.3s, border-color 0.3s;
    }

    .input-monto {
        width: 100%;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid var(--input-border);
        background-color: var(--card-bg);
        color: var(--text-main);
        font-size: 1.1em;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.3s, background-color 0.3s;
    }

    .input-monto:focus {
        border-color: var(--primary-color);
    }
    </style>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link" data-key="back_link">← Volver al Panel</a>
        
        <header class="report-header" style="margin-bottom: 25px;">
            <h2 data-key="header_title">Retirar Fondos de la Plataforma</h2>
            <p style="color: var(--text-light); font-size: 0.9em;" data-key="header_desc">El dinero se enviará de forma instantánea a tu cuenta bancaria vinculada a través de Stripe Connect desde el balance de la empresa.</p>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'cuenta_vinculada'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;" data-key="status_linked">
                <strong>¡Conexión Exitosa!</strong> Tu cuenta de Stripe Connect ha sido vinculada correctamente a Wirvux. Ya puedes retirar fondos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'retiro_exitoso'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #c3e6cb; font-size: 0.9em;" data-key="status_success">
                <strong>¡Retiro Completado!</strong> El dinero ha sido transferido con éxito a tu Cuenta de stripe. En unos minutos estará reflejado.
            </div>
            <?php 
                $saldo_actual = 0.00; 
                $comision_stripe = 0.00;
                $comision_wirvux = 0.00;
                $saldo_neto_final = 0.00;
            ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #f5c6cb; font-size: 0.9em;">
                <strong data-key="err_label">Error:</strong> 
                <span data-error-key="<?php echo htmlspecialchars($_GET['error']); ?>">
                <?php 
                    switch($_GET['error']) {
                        case 'minimo_no_alcanzado': echo 'El importe mínimo para retirar fondos es de 1,00 €.'; break;
                        case 'saldo_insuficiente': echo 'No dispones de suficiente saldo en tu balance actual.'; break;
                        case 'sin_cuenta_stripe': echo 'No tienes ninguna cuenta de Stripe Connect vinculada.'; break;
                        case 'error_stripe_api': echo 'La transferencia no se pudo procesar. Esto ocurre si tu banco requiere verificaciones adicionales o si los fondos del proyecto aún están retenidos en el periodo de liquidación estándar de Stripe (2 días hábiles).'; break;
                        case 'error_api_connect': echo 'No se pudo conectar con Stripe para registrar la cuenta. Inténtalo de nuevo.'; break;
                        case 'onboarding_reiniciado': echo 'El proceso de registro en Stripe fue cancelado o reiniciado.'; break;
                        default: echo 'Ocurrió un error inesperado.'; break;
                    }
                ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="balance-box">
            <span style="font-size: 0.9em; color: var(--text-light); display: block; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;" data-key="box_title">Presupuesto Bruto de Proyectos</span>
            <strong style="font-size: 2.2em; color: var(--text-main); display: block; margin-bottom: 12px;"><?php echo number_format($saldo_actual, 2); ?> €</strong>
            
            <div style="border-top: 1px dashed var(--text-light); padding-top: 10px; margin-top: 10px; text-align: left; font-size: 0.88em; line-height: 1.6em;">
                <div style="display: flex; justify-content: space-between; color: var(--text-light);">
                    <span data-key="fee_stripe">Tasa de Pasarela (Stripe 1.5% + 0.25€):</span>
                    <span style="color: #635bff; font-weight: bold;">- <?php echo number_format($comision_stripe, 2); ?> €</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; color: var(--text-light); margin-bottom: 4px;">
                    <span data-key="fee_wirvux">Comisión por Servicio de Intermediación (Wirvux 18.5%):</span>
                    <span style="color: #c53030; font-weight: bold;">- <?php echo number_format($comision_wirvux, 2); ?> €</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; color: var(--text-main); font-weight: bold; font-size: 1.05em; margin-top: 6px; padding-top: 6px; border-top: 1px solid var(--box-border);">
                    <span data-key="total_net">Total Neto a Recibir en tu Banco:</span>
                    <span style="color: #48bb78;"><?php echo number_format($saldo_neto_final, 2); ?> €</span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: <?php echo empty($stripe_connect_id) ? '12px' : '0'; ?>;">
                <div style="font-size: 1.5em; color: #635bff;">🏦</div>
                <div style="flex-grow: 1;">
                    <span style="font-size: 0.8em; color: var(--text-light); display: block; font-weight: bold; text-transform: uppercase;" data-key="dest_title">Destino del ingreso</span>
                    <?php if (!empty($stripe_connect_id)): ?>
                        <strong style="font-size: 0.95em; color: var(--text-main);" data-key="dest_linked">Cuenta de Stripe Vinculada</strong>
                        <span style="font-size: 0.85em; color: #635bff; display: block; font-family: monospace;"><?php echo htmlspecialchars($stripe_connect_id); ?></span>
                    <?php else: ?>
                        <strong style="font-size: 0.95em; color: #c53030;" data-key="dest_unlinked">Sin cuenta configurada</strong>
                        <span style="font-size: 0.85em; color: var(--text-light); display: block;" data-key="dest_req">Requerido para poder realizar transferencias bancarias.</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($stripe_connect_id)): ?>
                <form action="retirar_fondos.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="acc_action" value="vincular_stripe">
                    <button type="submit" id="btn_link_stripe" data-key="btn_link"
                            style="width: 100%; background-color: transparent; color: #635bff; border: 1px solid #635bff; padding: 8px; border-radius: 4px; font-weight: bold; font-size: 0.9em; cursor: pointer; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#635bff'; this.style.color='#fff';" 
                            onmouseout="this.style.backgroundColor='transparent'; this.style.color='#635bff';">
                        🔗 Enlazar mi cuenta con Stripe Connect
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <form action="solicitar_retiro.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-size: 0.9em; display:block; margin-bottom: 6px; font-weight: bold;" data-key="input_label">Cantidad bruta a procesar (€):</label>
                <input type="number" name="monto" class="input-monto" step="0.01" min="1.00" max="<?php echo $saldo_actual; ?>" placeholder="0.00" required 
                       <?php echo empty($stripe_connect_id) ? 'disabled' : ''; ?>>
                <small style="color: var(--text-light); display: block; margin-top: 5px;" data-key="input_min">Mínimo requerido: 1,00 €</small>
            </div>
            
            <button type="submit" data-key="btn_submit" <?php echo empty($stripe_connect_id) ? 'disabled style="background-color: #a0aec0; cursor: not-allowed;"' : 'style="background-color: #635bff; cursor: pointer;"'; ?> 
                    style="color: white; border: none; padding: 14px; border-radius: 6px; width: 100%; font-size: 1em; font-weight: bold; transition: background 0.2s;">
                Confirmar Cobro Instantáneo
            </button>
        </form>
    </div>

    <script>
    const translations = {
        'es': {
            'title_page': 'Retirar Fondos | Wirvux',
            'back_link': '← Volver al Panel',
            'header_title': 'Retirar Fondos de la Plataforma',
            'header_desc': 'El dinero se enviará de forma instantánea a tu cuenta bancaria vinculada a través de Stripe Connect desde el balance de la empresa.',
            'status_linked': '<strong>¡Conexión Exitosa!</strong> Tu cuenta de Stripe Connect ha sido vinculada correctamente a Wirvux. Ya puedes retirar fondos.',
            'status_success': '<strong>¡Retiro Completado!</strong> El dinero ha sido transferido con éxito a tu banco. En unos minutos estará reflejado.',
            'err_label': 'Error:',
            'minimo_no_alcanzado': 'El importe mínimo para retirar fondos es de 1,00 €.',
            'saldo_insuficiente': 'No dispones de suficiente saldo en tu balance actual.',
            'sin_cuenta_stripe': 'No tienes ninguna cuenta de Stripe Connect vinculada.',
            'error_stripe_api': 'La transferencia no se pudo procesar. Esto ocurre si tu banco requiere verificaciones adicionales o si los fondos del proyecto aún están retenidos en el periodo de liquidación estándar de Stripe (2 días hábiles).',
            'error_api_connect': 'No se pudo conectar con Stripe para registrar la cuenta. Inténtalo de nuevo.',
            'onboarding_reiniciado': 'El proceso de registro en Stripe fue cancelado o reiniciado.',
            'unexpected': 'Ocurrió un error inesperado.',
            'box_title': 'Presupuesto Bruto de Proyectos',
            'fee_stripe': 'Tasa de Pasarela (Stripe 1.5% + 0.25€):',
            'fee_wirvux': 'Comisión por Servicio de Intermediación (Wirvux 18.5%):',
            'total_net': 'Total Neto a Recibir en tu Banco:',
            'dest_title': 'Destino del ingreso',
            'dest_linked': 'Cuenta de Stripe Vinculada',
            'dest_unlinked': 'Sin cuenta configurada',
            'dest_req': 'Requerido para poder realizar transferencias bancarias.',
            'btn_link': '🔗 Enlazar mi cuenta con Stripe Connect',
            'input_label': 'Cantidad bruta a procesar (€):',
            'input_min': 'Mínimo requerido: 1,00 €',
            'btn_submit': 'Confirmar Cobro Instantáneo'
        },
        'en': {
            'title_page': 'Withdraw Funds | Wirvux',
            'back_link': '← Back to Dashboard',
            'header_title': 'Withdraw Funds from the Platform',
            'header_desc': 'Money will be sent instantly to your linked bank account via Stripe Connect from the company balance.',
            'status_linked': '<strong>Connection Successful!</strong> Your Stripe Connect account has been successfully linked to Wirvux. You can now withdraw funds.',
            'status_success': '<strong>Withdrawal Completed!</strong> The money has been successfully transferred to your bank. It will be reflected in a few minutes.',
            'err_label': 'Error:',
            'minimo_no_alcanzado': 'The minimum amount to withdraw funds is €1.00.',
            'saldo_insuficiente': 'You do not have enough balance in your current account.',
            'sin_cuenta_stripe': 'You do not have a linked Stripe Connect account.',
            'error_stripe_api': 'The transfer could not be processed. This occurs if your bank requires additional verifications or if the project funds are still held within Stripe\'s standard settlement period (2 business days).',
            'error_api_connect': 'Could not connect to Stripe to register the account. Please try again.',
            'onboarding_reiniciado': 'The Stripe registration process was canceled or restarted.',
            'unexpected': 'An unexpected error occurred.',
            'box_title': 'Gross Project Budget',
            'fee_stripe': 'Gateway Fee (Stripe 1.5% + €0.25):',
            'fee_wirvux': 'Intermediation Service Fee (Wirvux 18.5%):',
            'total_net': 'Total Net Amount to Receive in Your Bank:',
            'dest_title': 'Payout Destination',
            'dest_linked': 'Linked Stripe Account',
            'dest_unlinked': 'No account configured',
            'dest_req': 'Required to be able to make bank transfers.',
            'btn_link': '🔗 Link my account with Stripe Connect',
            'input_label': 'Gross amount to process (€):',
            'input_min': 'Minimum required: €1.00',
            'btn_submit': 'Confirm Instant Payout'
        }
    };

    function applyTranslations() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const texts = translations[lang];

        // Traducir elementos estándar con data-key
        document.querySelectorAll('[data-key]').forEach(el => {
            const key = el.getAttribute('data-key');
            if (texts[key]) el.innerText = texts[key];
        });

        // Traducir el bloque dinámico de errores devueltos en la URL por GET
        document.querySelectorAll('[data-error-key]').forEach(el => {
            const errorKey = el.getAttribute('data-error-key');
            if (texts[errorKey]) {
                el.innerText = texts[errorKey];
            } else {
                el.innerText = texts['unexpected'];
            }
        });

        document.title = texts['title_page'];
    }

    (function() {
        // Cargar Tema desde el sessionStorage
        const savedTheme = sessionStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');

        // Aplicar Idioma
        applyTranslations();
    })();
    </script>
</body>
</html>