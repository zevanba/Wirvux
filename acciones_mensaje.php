<?php
session_start();
include 'db.php';

$id_user = $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? '';
$id_msg = intval($_POST['id'] ?? 0);

if ($accion == 'editar') {
    $nuevo_texto = mysqli_real_escape_string($conexion, $_POST['mensaje']);
    // Solo permite editar si no está eliminado
    $query = "UPDATE mensajes SET mensaje = '$nuevo_texto', editado = 1 
              WHERE id = $id_msg AND id_emisor = $id_user AND eliminado = 0";
    $success = mysqli_query($conexion, $query);
    echo json_encode(['success' => $success]);

} elseif ($accion == 'eliminar') {
    // Marcamos como eliminado en lugar de borrar la fila
    $query = "UPDATE mensajes SET eliminado = 1, mensaje = 'Mensaje eliminado' 
              WHERE id = $id_msg AND id_emisor = $id_user";
    $success = mysqli_query($conexion, $query);
    echo json_encode(['success' => $success]);
}
?>