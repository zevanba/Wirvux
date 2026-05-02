<?php
session_start();
include 'db.php';

// Establecemos el encabezado para que el navegador sepa que respondemos con JSON
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) { 
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.']);
    exit(); 
}

$id_autonomo = $_SESSION['usuario_id'];
$id_mensaje = isset($_POST['id_mensaje']) ? intval($_POST['id_mensaje']) : 0;

if ($id_mensaje <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de mensaje no válido.']);
    exit();
}

// 1. Buscamos el mensaje para extraer la información de la propuesta
$query_msg = "SELECT mensaje, id_emisor FROM mensajes WHERE id = $id_mensaje";
$res_msg = mysqli_query($conexion, $query_msg);
$msg_data = mysqli_fetch_assoc($res_msg);

if ($msg_data) {
    // 2. Extraemos el título del proyecto del texto del mensaje
    // La expresión /Proyecto:\s*(.+)/ permite capturar el título aunque haya espacios tras los dos puntos
    preg_match('/Proyecto:\s*(.+)/', $msg_data['mensaje'], $coincidencias);
    $titulo_trabajo = isset($coincidencias[1]) ? trim($coincidencias[1]) : '';
    $id_cliente = $msg_data['id_emisor'];

    if (!empty($titulo_trabajo)) {
        $titulo_limpio = mysqli_real_escape_string($conexion, $titulo_trabajo);
        
        // 3. Actualizamos el trabajo en la base de datos
        // CORRECCIÓN: Se cambia 'nombre' por 'titulo' para coincidir con la tabla de la DB
        $sql_update = "UPDATE trabajos SET 
                       id_autonomo = $id_autonomo, 
                       estado = 'en_progreso' 
                       WHERE titulo = '$titulo_limpio' 
                       AND id_cliente = $id_cliente 
                       AND (id_autonomo IS NULL OR id_autonomo = 0)";

        if (mysqli_query($conexion, $sql_update)) {
            if (mysqli_affected_rows($conexion) > 0) {
                // Éxito: el trabajo se asignó correctamente
                echo json_encode(['success' => true]);
            } else {
                // El trabajo existe pero no cumple las condiciones (ya tiene autónomo asignado)
                echo json_encode(['success' => false, 'error' => 'Este trabajo ya ha sido aceptado o no está disponible.']);
            }
        } else {
            // Error de base de datos
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la base de datos: ' . mysqli_error($conexion)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo identificar el título del proyecto en el mensaje.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'El mensaje de la propuesta no existe.']);
}
?>