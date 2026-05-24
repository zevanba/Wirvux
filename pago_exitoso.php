<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';

// Carga manual de PHPMailer desde tu carpeta
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. SEGURIDAD: Verificar que existan datos activos del flujo de pago en la sesión
if (!isset($_SESSION['stripe_checkout_trabajo']) || !isset($_SESSION['usuario_id'])) {
    header("Location: area_cliente.php");
    exit();
}

$id_cliente = $_SESSION['usuario_id'];
$id_trabajo = intval($_SESSION['stripe_checkout_trabajo']);
$id_autonomo_sesion = intval($_SESSION['stripe_checkout_autonomo']);
$monto_total = floatval($_SESSION['stripe_checkout_monto']);

// =========================================================================
// CONSULTA DIRECTA DE DATOS EN BASE A TUS CAPTURAS (Campos: titulo, categoria, descripcion)
// =========================================================================
$query_completa = "SELECT 
                    t.titulo AS proyecto_titulo, 
                    t.categoria AS proyecto_sector, 
                    t.descripcion AS proyecto_desc,
                    u_cliente.email AS cliente_email,
                    u_auto.nombre AS autonomo_nombre,
                    u_auto.email AS autonomo_email
                   FROM trabajos t
                   INNER JOIN usuarios u_cliente ON t.id_cliente = u_cliente.id
                   LEFT JOIN usuarios u_auto ON t.id_autonomo = u_auto.id
                   WHERE t.id = $id_trabajo AND t.id_cliente = $id_cliente";

$res_completa = mysqli_query($conexion, $query_completa);
$datos_factura = mysqli_fetch_assoc($res_completa);

if (!$datos_factura) {
    die("Error crítico: No se pudieron recuperar los detalles del proyecto para la factura.");
}

// Asignamos el email del cliente obtenido directamente de la base de datos
$para = $datos_factura['cliente_email'];

// 2. --- LÓGICA DE COMISIONES HASTA EL 20% ---
$comision_total = $monto_total * 0.20;       
$neto_autonomo = $monto_total - $comision_total; 

// Tarifa Stripe Europa fija: 1.5% + 0.25 €
$comision_stripe = ($monto_total * 0.015) + 0.25;

// Lo que se queda Wirvux es el resto de la comisión total una vez pagado Stripe
$comision_wirvux_limpia = $comision_total - $comision_stripe;

// CORRECCIÓN MATEMÁTICA PARA PROYECTOS ULTRA BAJOS (Como el de 2,00 €)
if ($comision_wirvux_limpia < 0) {
    $comision_wirvux_limpia = 0;
    // Si la comisión de la plataforma se vuelve negativa, el autónomo se lleva el total menos el coste real de Stripe
    $neto_autonomo = $monto_total - $comision_stripe;
}

// Aseguramos que el neto nunca quede por debajo de cero en escenarios extremos
if ($neto_autonomo < 0) {
    $neto_autonomo = 0;
}

// A. ACTUALIZACIÓN DEL PROYECTO: Cambiamos el estado a 'completado' (Columna 'estado' de tu captura)
$query_update = "UPDATE trabajos 
                 SET estado = 'completado' 
                 WHERE id = $id_trabajo AND id_cliente = $id_cliente";

if (mysqli_query($conexion, $query_update)) {
    
    // B. INGRESAR EN EL BALANCE DEL AUTÓNOMO (Columna 'saldo_disponible' de tu captura)
    $update_saldo = "UPDATE usuarios 
                     SET saldo_disponible = saldo_disponible + $neto_autonomo 
                     WHERE id = $id_autonomo_sesion";
    mysqli_query($conexion, $update_saldo);

    // C. HISTORIAL DE PAGOS: Registramos la auditoría financiera
    $insert_pago = "INSERT INTO pagos (id_trabajo, id_autonomo, monto_total, comision_stripe, comision_wirvux, neto_autonomo, estado_pago) 
                    VALUES ($id_trabajo, $id_autonomo_sesion, $monto_total, $comision_stripe, $comision_wirvux_limpia, $neto_autonomo, 'en_balance')";
    mysqli_query($conexion, $insert_pago);


    // =========================================================================
    // ENVIAR GMAIL CON PHPMAILER (SMTP DE GMAIL)
    // =========================================================================
    // =========================================================================
    // ENVIAR GMAIL CON PHPMAILER (SMTP DE GMAIL)
    // =========================================================================
    if (!empty($para)) {
        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor SMTP de Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wirvux@gmail.com';             
            $mail->Password   = 'dauo kwnl vldr jdad';             
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Remitente y Destinatario
            $mail->setFrom('wirvux@gmail.com', 'Wirvux');       
            $mail->addAddress($para); 

            // Contenido del Correo empleando los campos reales de tu tabla
            $mail->isHTML(true);
            $mail->Subject = "Factura de proyecto completado - Wirvux (Ref: #$id_trabajo)";
            
            $titulo_proyecto   = htmlspecialchars($datos_factura['proyecto_titulo'] ?? 'Proyecto sin título');
            $sector_proyecto   = htmlspecialchars($datos_factura['proyecto_sector'] ?? 'No especificado');
            $nombre_autonomo   = htmlspecialchars($datos_factura['autonomo_nombre'] ?? 'Autónomo asignado');
            $email_autonomo    = htmlspecialchars($datos_factura['autonomo_email'] ?? '');
            $descripcion       = nl2br(htmlspecialchars($datos_factura['proyecto_desc'] ?? 'Sin descripción disponible.'));
            $precio_formateado = number_format($monto_total, 2, ',', '.') . " €";

            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; }
                    .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 3px solid #007bff; }
                    .header h1 { margin: 0; color: #007bff; font-size: 24px; }
                    .section { margin: 20px 0; }
                    .table-datos { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    .table-datos td { padding: 8px; border-bottom: 1px solid #eee; }
                    .table-datos td.label { font-weight: bold; width: 30%; color: #555; }
                    .precio { font-size: 20px; color: #28a745; font-weight: bold; }
                    .descripcion-box { background-color: #fafafa; padding: 12px; border-left: 3px solid #6c757d; margin-top: 5px; font-style: italic; }
                    .footer { text-align: center; font-size: 12px; color: #777; margin-top: 30px; border-top: 1px solid #e0e0e0; padding-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Wirvux</h1>
                        <p>Justificante de Pago y Facturación</p>
                    </div>
                    
                    <div class='section'>
                        <p>Hola,</p>
                        <p>Te confirmamos que el pago de tu proyecto se ha procesado correctamente y el estado ha cambiado a <strong>Completado</strong>. A continuación tienes los detalles del servicio:</p>
                        
                        <table class='table-datos'>
                            <tr>
                                <td class='label'>Proyecto:</td>
                                <td>$titulo_proyecto</td>
                            </tr>
                            <tr>
                                <td class='label'>Sector:</td>
                                <td>$sector_proyecto</td>
                            </tr>
                            <tr>
                                <td class='label'>Autónomo:</td>
                                <td>$nombre_autonomo ($email_autonomo)</td>
                            </tr>
                            <tr>
                                <td class='label'>Importe Total:</td>
                                <td class='precio'>$precio_formateado</td>
                            </tr>
                        </table>
                    </div>

                    <div class='section'>
                        <strong>Descripción del Proyecto:</strong>
                        <div class='descripcion-box'>$descripcion</div>
                    </div>

                    <div class='footer'>
                        <p>Este correo electrónico sirve como un recibo oficial de la transacción en Wirvux.</p>
                        <p>&copy; " . date('Y') . " Wirvux. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log("Error de PHPMailer en Wirvux: {$mail->ErrorInfo}");
        }
    } else {
        error_log("Wirvux Error: El email del cliente está vacío.");
    }
    // =========================================================================

    // 3. LIMPIAR SESIÓN (Solo los datos temporales del checkout de Stripe)
    unset($_SESSION['stripe_checkout_trabajo']);
    unset($_SESSION['stripe_checkout_autonomo']);
    unset($_SESSION['stripe_checkout_monto']);

    // 4. ÉXITO: Redirigimos al cliente
    header("Location: ver_propuestas.php?id=$id_trabajo&status=success");
    exit();

} else {
    die("Error crítico al asentar el pago en la base de datos: " . mysqli_error($conexion));
}
?>