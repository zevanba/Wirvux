<?php
// Configuración de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

$mi_id = $_SESSION['usuario_id'];
$mi_tipo = $_SESSION['tipo']; // cliente o autonomo
$id_con_quien = isset($_GET['con']) ? intval($_GET['con']) : 0;
$id_proyecto_volver = isset($_GET['proy']) ? intval($_GET['proy']) : 0;

// 1. Obtener lista de contactos
$query_contactos = "SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                    JOIN mensajes m ON (u.id = m.id_emisor OR u.id = m.id_receptor) 
                    WHERE (m.id_emisor = $mi_id OR m.id_receptor = $mi_id) AND u.id != $mi_id";
$res_contactos = mysqli_query($conexion, $query_contactos);

// 2. Obtener mensajes de la conversación y datos del contacto
$res_msjs = null;
$nombre_chat = ""; 
$foto_chat = "";
$tipo_chat = "";

if ($id_con_quien > 0) {
    // MODIFICADO: Traemos nombre, foto y tipo del usuario con el que hablamos
    $res_nombre = mysqli_query($conexion, "SELECT nombre, foto, tipo FROM usuarios WHERE id = $id_con_quien");
    $user_chat = mysqli_fetch_assoc($res_nombre);
    $nombre_chat = $user_chat['nombre'] ?? "Usuario";
    $foto_chat = $user_chat['foto'] ?? "";
    // Limpiamos el valor para evitar fallos por mayúsculas o espacios
    $tipo_chat = isset($user_chat['tipo']) ? strtolower(trim($user_chat['tipo'])) : "";

    $query_msjs = "SELECT m.*, r.mensaje as texto_respuesta, t.id_autonomo as trabajo_asignado_a
                   FROM mensajes m 
                   LEFT JOIN mensajes r ON m.id_respuesta = r.id
                   LEFT JOIN trabajos t ON (m.mensaje LIKE CONCAT('%Proyecto: ', t.titulo, '%') AND t.id_cliente = m.id_emisor)
                   WHERE (m.id_emisor = $mi_id AND m.id_receptor = $id_con_quien) 
                   OR (m.id_emisor = $id_con_quien AND m.id_receptor = $mi_id) 
                   ORDER BY m.fecha_envio ASC";
    $res_msjs = mysqli_query($conexion, $query_msjs);
}

$url_panel = ($mi_tipo === 'cliente') ? 'area_cliente.php' : 'area_autonomo.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title data-key="title_page">Mensajes | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos nuevos para la cabecera del chat */
        .chat-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: #fff;
            border-bottom: 1px solid #eee;
        }
        .chat-header-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            transition: opacity 0.2s;
        }
        .chat-header-link:hover {
            opacity: 0.8;
        }
        .img-perfil-chat {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
        }
        .icon-perfil-chat {
            font-size: 40px;
            color: #ccc;
        }

        @keyframes highlight-fade {
            0% { background-color: #fff3cd; transform: scale(1.02); }
            100% { background-color: transparent; transform: scale(1); }
        }
        .mensaje-resaltado {
            animation: highlight-fade 2s ease-out;
            border: 2px solid #ffc107 !important;
            z-index: 10;
        }
        .propuesta-box {
            margin-top: 10px;
            padding: 12px;
            border-top: 1px dashed #ccc;
            text-align: center;
        }
        .btn-aceptar-proy {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-aceptar-proy:hover { background: #218838; }
        
        .status-aceptado {
            display: inline-block;
            background: #e7f4e4;
            color: #2e7d32;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body class="sticky-footer-body">

    <nav class="navbar">
        <div class="nav-container">
            <h1 class="logo">WIRVUX <span class="sub-logo" data-key="nav_chats">CHATS</span></h1>
            <div class="nav-links">
                <a href="<?php echo $url_panel; ?>" class="btn-back" data-key="btn_back">Panel Principal</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="chat-wrapper">
            <div class="chat-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-comments"></i> <span data-key="sidebar_title">Conversaciones</span></h3>
                </div>
                <div class="contacts-scroll">
                    <?php while($c = mysqli_fetch_assoc($res_contactos)): ?>
                        <a href="mensajes.php?con=<?php echo $c['id']; ?>&proy=<?php echo $id_proyecto_volver; ?>" 
                           class="chat-contact-item <?php echo ($id_con_quien == $c['id']) ? 'active' : ''; ?>">
                            <div class="contact-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <small data-key="view_chat">Ver chat</small>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="chat-main">
                <?php if($id_con_quien > 0): ?>
                    <div class="chat-header">
                        <?php if ($tipo_chat === 'cliente'): ?>
                            <!-- ENLACE ACTIVO SOLO PARA AUTÓNOMOS -->
                            <a href="perfil_autonomo.php?id=<?php echo $id_con_quien; ?>" class="chat-header-link">
                                <?php if(!empty($foto_chat) && file_exists($foto_chat)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_chat); ?>" alt="Foto" class="img-perfil-chat">
                                <?php else: ?>
                                    <i class="fas fa-user-circle icon-perfil-chat"></i>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($nombre_chat); ?> <small style="color:#007bff; font-weight:normal;">(Ver Perfil)</small></strong>
                            </a>
                        <?php else: ?>
                            <!-- DIV SIMPLE PARA CLIENTES (SIN ENLACE) -->
                            <div class="chat-header-link" style="cursor: default;">
                                <?php if(!empty($foto_chat) && file_exists($foto_chat)): ?>
                                    <img src="<?php echo htmlspecialchars($foto_chat); ?>" alt="Foto" class="img-perfil-chat">
                                <?php else: ?>
                                    <i class="fas fa-user-circle icon-perfil-chat"></i>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($nombre_chat); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-messages">
                        <?php if($res_msjs): ?>
                        <?php while($m = mysqli_fetch_assoc($res_msjs)): ?>
                            <?php 
                                $mi_mensaje = ($m['id_emisor'] == $mi_id);
                                $tipo_clase = $mi_mensaje ? 'msg-sent' : 'msg-received';
                                $fue_eliminado = (isset($m['eliminado']) && $m['eliminado'] == 1);
                                $es_propuesta = (strpos($m['mensaje'], '--- NUEVA PROPUESTA DE PROYECTO ---') !== false);
                                $ya_aceptado = (!empty($m['trabajo_asignado_a']) && $m['trabajo_asignado_a'] != 0);
                            ?>

                            <div class="message <?php echo $tipo_clase; ?>" id="msg-container-<?php echo $m['id']; ?>">
                                
                                <?php if(!$fue_eliminado && !empty($m['texto_respuesta'])): ?>
                                    <div class="reply-bubble" onclick="irAlMensaje(<?php echo $m['id_respuesta']; ?>)" 
                                         style="cursor: pointer; background: rgba(0,0,0,0.05); padding: 5px 10px; border-radius: 5px; margin-bottom: 5px; font-size: 0.85rem; border-left: 3px solid #007bff;">
                                        <i class="fas fa-reply"></i> 
                                        <em>"<?php echo htmlspecialchars(substr($m['texto_respuesta'], 0, 50)); ?>..."</em>
                                    </div>
                                <?php endif; ?>

                                <div class="text" id="text-<?php echo $m['id']; ?>">
                                    <?php if($fue_eliminado): ?>
                                        <em style="color: #999; font-size: 0.85rem;"> <i class="fas fa-ban"></i> Mensaje eliminado</em>
                                    <?php else: ?>
                                        <?php echo nl2br(htmlspecialchars($m['mensaje'])); ?>
                                    <?php endif; ?>
                                </div>

                                <?php if($es_propuesta && !$fue_eliminado): ?>
                                    <div class="propuesta-box">
                                        <?php if($ya_aceptado): ?>
                                            <span class="status-aceptado">
                                                <i class="fas fa-check-circle"></i> Trabajo Aceptado y Asignado
                                            </span>
                                        <?php elseif($mi_tipo === 'autonomo' && !$mi_mensaje): ?>
                                            <button class="btn-aceptar-proy" onclick="confirmarPropuesta(<?php echo $m['id']; ?>)">
                                                <i class="fas fa-handshake"></i> Aceptar y Empezar Trabajo
                                            </button>
                                        <?php else: ?>
                                            <small style="color: #888;">Esperando aceptación...</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="meta">
                                    <small>
                                        <?php echo date('H:i', strtotime($m['fecha_envio'])); ?>
                                        <?php if(isset($m['editado']) && $m['editado'] && !$fue_eliminado): ?> 
                                            <span id="edit-tag-<?php echo $m['id']; ?>" style="font-style:italic; opacity:0.7;">(editado)</span> 
                                        <?php endif; ?>
                                    </small>

                                    <div class="msg-actions" id="actions-<?php echo $m['id']; ?>">
                                        <?php if(!$fue_eliminado): ?>
                                            <button onclick="setReplying(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars(str_replace(["\r", "\n"], ' ', $m['mensaje'])); ?>')" title="Responder">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                            <?php if($mi_mensaje): ?>
                                                <button onclick="activarEdicionInline(<?php echo $m['id']; ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="eliminarMensajeLogico(<?php echo $m['id']; ?>)" title="Eliminar" style="color:#ff4d4d;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div id="reply-preview" style="display:none;">
                        <div class="reply-bar">
                            <span><i class="fas fa-reply"></i> <span data-key="reply_to">Respondiendo a</span>: <strong id="reply-text"></strong></span>
                            <button onclick="cancelReply()">&times;</button>
                        </div>
                    </div>

                    <form action="enviar_mensaje.php" method="POST" class="chat-form">
                        <input type="hidden" name="id_receptor" value="<?php echo $id_con_quien; ?>">
                        <input type="hidden" name="id_respuesta" id="id_respuesta_input" value="">
                        <input type="text" name="texto" id="mensaje_input" placeholder="Escribe tu mensaje aquí..." required autocomplete="off">
                        <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
                    </form>

                <?php else: ?>
                    <div class="chat-welcome">
                        <i class="fas fa-comments"></i>
                        <p data-key="welcome_chat">Selecciona una conversación para empezar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="text-center">
        <p>&copy; 2026 Wirvux - <span data-key="footer_text">Mensajería Segura</span></p>
    </footer>

   <script>
    const translations = {
        'es': { 'reply_to': 'Respondiendo a', 'placeholder_input': 'Escribe tu mensaje aquí...' },
        'en': { 'reply_to': 'Replying to', 'placeholder_input': 'Type your message here...' }
    };

    function setReplying(id, text) {
        document.getElementById('id_respuesta_input').value = id;
        document.getElementById('reply-text').innerText = text.substring(0, 50) + "...";
        document.getElementById('reply-preview').style.display = 'block';
        document.getElementById('mensaje_input').focus();
    }

    function cancelReply() {
        document.getElementById('id_respuesta_input').value = "";
        document.getElementById('reply-preview').style.display = 'none';
    }

    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const msgInput = document.getElementById('mensaje_input');
        if(msgInput && translations[lang]) msgInput.placeholder = translations[lang]['placeholder_input'];
        const chatBox = document.querySelector('.chat-messages');
        if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    }

    window.onload = loadPreferences;

    function irAlMensaje(id) {
        const elemento = document.getElementById('msg-container-' + id);
        if (elemento) {
            elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elemento.classList.add('mensaje-resaltado');
            setTimeout(() => { elemento.classList.remove('mensaje-resaltado'); }, 2000);
        }
    }

    function activarEdicionInline(id) {
        const textDiv = document.getElementById('text-' + id);
        const actionsDiv = document.getElementById('actions-' + id);
        let fullText = textDiv.innerText;
        const separador = "MENSAJE DEL CLIENTE:";
        let estructura = "";
        let texto = fullText.replace('(editado)', '').trim();

        if (fullText.includes(separador)) {
            let partes = fullText.split(separador);
            estructura = partes[0] + separador + "\n";
            texto = partes[1].trim();
        }

        textDiv.dataset.oldHtml = textDiv.innerHTML;
        textDiv.dataset.protegido = estructura;
        if(actionsDiv) actionsDiv.style.display = 'none';

        textDiv.innerHTML = `
            <textarea id="input-edit-${id}" style="width:100%; border:1px solid #007bff; border-radius:5px; padding:8px; min-height:80px;">${texto}</textarea>
            <div style="margin-top:5px;">
                <button onclick="guardarEdicion(${id})" style="background:#28a745; color:white; border:none; padding:4px 10px; border-radius:3px;">Guardar</button>
                <button onclick="cancelarEdicion(${id})" style="background:#6c757d; color:white; border:none; padding:4px 10px; border-radius:3px;">X</button>
            </div>`;
    }

    function cancelarEdicion(id) {
        const textDiv = document.getElementById('text-' + id);
        textDiv.innerHTML = textDiv.dataset.oldHtml;
        document.getElementById('actions-' + id).style.display = 'block';
    }

    function guardarEdicion(id) {
        const textDiv = document.getElementById('text-' + id);
        const nuevoTexto = document.getElementById('input-edit-' + id).value;
        const mensajeFinal = (textDiv.dataset.protegido || "") + nuevoTexto;

        let formData = new FormData();
        formData.append('accion', 'editar');
        formData.append('id', id);
        formData.append('mensaje', mensajeFinal);

        fetch('acciones_mensaje.php', { method: 'POST', body: formData })
        .then(res => res.json()).then(data => { if(data.success) location.reload(); });
    }

    function eliminarMensajeLogico(id) {
        if (confirm("¿Eliminar mensaje?")) {
            let formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id', id);
            fetch('acciones_mensaje.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => { if(data.success) location.reload(); });
        }
    }

    function confirmarPropuesta(mensajeId) {
        if (confirm("¿Aceptar este trabajo?")) {
            let formData = new FormData();
            formData.append('id_mensaje', mensajeId);
            fetch('aceptar_trabajo.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if (data.success) { alert("¡Trabajo aceptado!"); location.reload(); }
                else { alert("Error: " + data.error); }
            });
        }
    }
</script>
</body>
</html>