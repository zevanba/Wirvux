<?php
session_start();
include 'db.php';

// 1. Verificación de seguridad real
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    die("No tienes permiso para estar aquí.");
}

$id_usuario = $_SESSION['usuario_id'];
$descripcion = mysqli_real_escape_string($conexion, $_POST['nueva_descripcion']);

// 2. Lógica de la foto (solo si se sube una nueva)
if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === 0) {
    $ruta_destino = "img/perfiles/" . $id_usuario . "_" . $_FILES['nueva_foto']['name'];
    move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $ruta_destino);
    
    // Actualizar foto y descripción
    $query = "UPDATE usuarios SET descripcion = '$descripcion', foto = '$ruta_destino' WHERE id = $id_usuario";
} else {
    // Actualizar solo descripción
    $query = "UPDATE usuarios SET descripcion = '$descripcion' WHERE id = $id_usuario";
}

mysqli_query($conexion, $query);
header("Location: perfil_autonomo.php?id=$id_usuario");
exit;