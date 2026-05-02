<?php

// Activamos reporte de errores para saber exactamente qué falla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_SESSION['usuario_id'];
    $id_autonomo = intval($_POST['id_autonomo']);
    $id_proyecto = intval($_POST['id_proyecto']);
    $mensaje_usuario = mysqli_real_escape_string($conexion, $_POST['mensaje_propuesta']);

    // 1. Obtener datos del proyecto para el mensaje automático
    $q_proy = "SELECT titulo, descripcion FROM trabajos WHERE id = $id_proyecto AND id_cliente = $id_cliente";
    $res_proy = mysqli_query($conexion, $q_proy);
    $proyecto = mysqli_fetch_assoc($res_proy);

    if ($proyecto) {
        $titulo_p = $proyecto['titulo'];
        $desc_p = $proyecto['descripcion'];

        // 2. Vincular el autónomo al proyecto
        $update = "UPDATE trabajos SET id_autonomo = $id_autonomo WHERE id = $id_proyecto";
        mysqli_query($conexion, $update);

        // 3. Preparar el cuerpo del mensaje completo
        // Combinamos el mensaje personalizado del cliente con los datos técnicos del proyecto
        $mensaje_final = "--- NUEVA PROPUESTA DE PROYECTO ---\n";
        $mensaje_final .= "Proyecto: " . $titulo_p . "\n";
        $mensaje_final .= "Descripción: " . $desc_p . "\n";
        $mensaje_final .= "-----------------------------------\n";
        $mensaje_final .= "MENSAJE DEL CLIENTE:\n" . $mensaje_usuario;
        
        $mensaje_final = mysqli_real_escape_string($conexion, $mensaje_final);

        // 4. Insertar en la tabla de mensajes
        // Ajusta los nombres de las columnas según tu tabla (ej: id_emisor, id_receptor, texto, fecha)
        $query_msg = "INSERT INTO mensajes (id_emisor, id_receptor, mensaje, fecha_envio) 
                      VALUES ($id_cliente, $id_autonomo, '$mensaje_final', NOW())";
        
        if (mysqli_query($conexion, $query_msg)) {
            // Éxito: Redirigir al perfil con aviso
            header("Location: perfil_autonomo.php?id=$id_autonomo&status=propuesta_enviada");
        } else {
            echo "Error al enviar el mensaje: " . mysqli_error($conexion);
        }
    } else {
        echo "Proyecto no encontrado.";
    }
} else {
    header("Location: profesionales.php");
}
?>