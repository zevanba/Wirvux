<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




session_start();
include 'db.php';

// Identificamos al profesional que se está visitando
$id_autonomo = isset($_GET['id']) ? intval($_GET['id']) : 0;

// MANTENEMOS tipo_usuario para la base de datos ya que así te funcionaba la carga del perfil
$query = "SELECT * FROM usuarios WHERE id = $id_autonomo AND tipo_usuario = 'autonomo'";
$res = mysqli_query($conexion, $query);
$aut = mysqli_fetch_assoc($res);

if (!$aut) {
    echo "<div style='text-align:center; margin-top:100px; font-family:\"Segoe UI\", sans-serif; color: #444;'>
            <i class='fas fa-search' style='font-size: 4rem; color: #ccc; margin-bottom: 20px;'></i>
            <h2 style='font-weight: 600;'>Perfil no encontrado</h2>
            <p style='color: #888;'>El profesional que buscas no existe o ha sido desactivado.</p>
            <br>
            <a href='profesionales.php' style='color: #007bff; text-decoration: none; font-weight: bold;'>← Volver al directorio</a>
          </div>";
    exit;
}

// Lista de trabajos para el menú desplegable
$mis_trabajos_libres = [];

// CAMBIO AQUÍ: Usamos $_SESSION['tipo'] porque ahí es donde guardas el rol del cliente
if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente') {
    $mi_id_cliente = $_SESSION['usuario_id'];
    
    // Traemos trabajos que pertenecen a este cliente y no tienen autónomo asignado
    $query_trabajos = "SELECT id, titulo FROM trabajos WHERE id_cliente = $mi_id_cliente AND (id_autonomo IS NULL OR id_autonomo = 0)";
    $res_trabajos = mysqli_query($conexion, $query_trabajos);
    
    if ($res_trabajos) {
        while ($fila = mysqli_fetch_assoc($res_trabajos)) {
            $mis_trabajos_libres[] = $fila;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($aut['nombre']); ?> | Perfil Profesional</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #0056b3;
            --success: #28a745;
            --light-bg: #f8faff;
            --text-dark: #2d3436;
            --text-gray: #636e72;
            --white: #ffffff;
            --shadow: 0 15px 35px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.05);
        }

        body { 
            background-color: var(--light-bg); 
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .perfil-wrapper { 
            max-width: 850px; 
            margin: 60px auto; 
            background: var(--white); 
            border-radius: 24px; 
            overflow: hidden; 
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.02);
        }

        .perfil-banner { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); 
            height: 160px; 
            position: relative;
        }

        .perfil-banner::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: var(--white);
            clip-path: ellipse(60% 100% at 50% 100%);
        }

        .perfil-info { 
            padding: 0 50px 50px; 
            margin-top: -85px; 
            text-align: center; 
            position: relative;
            z-index: 2;
        }

        .foto-grande { 
            width: 160px; 
            height: 160px; 
            border-radius: 30%; 
            border: 6px solid var(--white); 
            object-fit: cover; 
            background: var(--white); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .header-content h1 {
            margin: 20px 0 5px;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .tag-categoria { 
            background: rgba(0, 123, 255, 0.08); 
            color: var(--primary); 
            padding: 6px 18px; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: 700; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0;
        }

        .especialidad-sub { 
            color: var(--text-gray); 
            font-size: 1.1rem;
            display: block; 
            margin-bottom: 30px; 
        }

        .content-section { 
            text-align: left; 
            margin-top: 40px; 
            padding-top: 20px;
        }

        .descripcion-texto { 
            padding: 25px;
            background: #fcfcfd;
            border-radius: 18px;
            border: 1px solid #f1f3f5;
            color: #4b5563;
        }

        .hitos-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 15px; 
            margin-top: 20px; 
        }

        .hito-card { 
            background: var(--white); 
            padding: 16px 20px; 
            border-radius: 16px; 
            border: 1px solid #f1f3f5;
            display: flex; 
            align-items: center; 
            gap: 15px; 
            transition: 0.3s;
        }

        .dropdown-proyectos {
            display: none;
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #eee;
            margin-top: 10px;
            text-align: left;
            overflow: hidden;
            z-index: 10;
        }

        .proyecto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #f8f9fa;
        }

        .proyecto-item:hover { background: #f0f7ff; }

        .btn-enviar-peq {
            background: var(--success);
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .actions-container {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .btn-proponer {
            background: var(--primary);
            color: white;
            border: none;
            padding: 18px 30px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            max-width: 400px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .btn-ir-listado {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%; 
            max-width: 400px;
            background: transparent; 
            color: var(--primary); 
            padding: 15px 30px; 
            border-radius: 16px; 
            border: 2px solid var(--primary);
            text-decoration: none; 
            font-weight: 700; 
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-ir-listado:hover {
            background: rgba(0, 123, 255, 0.05);
            transform: translateY(-2px);
        }

        .btn-contactar { 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%; 
            max-width: 400px;
            background: var(--success); 
            color: var(--white); 
            padding: 18px 30px; 
            border-radius: 16px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 1.1rem;
            transition: 0.3s;
        }

        .back-link { 
            color: var(--text-gray); 
            text-decoration: none; 
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    <div class="perfil-wrapper">
        <div class="perfil-banner"></div>
        <div class="perfil-info">
            <img src="<?php echo !empty($aut['foto']) ? $aut['foto'] : 'img/default-avatar.png'; ?>" class="foto-grande" alt="Avatar">
            
            <div class="header-content">
                <h1><?php echo htmlspecialchars($aut['nombre'] . " " . $aut['apellidos']); ?></h1>
                <div class="tag-categoria">
                    <i class="fas fa-briefcase"></i> 
                    <?php echo htmlspecialchars($aut['categoria_principal']); ?>
                </div>
                <span class="especialidad-sub">
                    <?php echo htmlspecialchars($aut['especialidad']); ?>
                </span>
            </div>

            <div class="content-section">
                <h3><i class="fas fa-quote-left" style="color: var(--primary);"></i> Perfil Profesional</h3>
                <div class="descripcion-texto">
                    <?php echo !empty($aut['descripcion']) ? nl2br(htmlspecialchars($aut['descripcion'])) : 'Este profesional aún no ha redactado su presentación.'; ?>
                </div>
            </div>

            <div class="actions-container">

                <!-- BLOQUE PARA CLIENTES CORREGIDO -->
                <?php if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente'): ?>
                    
                    <button onclick="toggleProyectos()" class="btn-proponer">
                        <i class="fas fa-paper-plane"></i> Proponer un Proyecto
                    </button>

                   <div id="lista-proyectos-dropdown" class="dropdown-proyectos">
    <div style="padding: 12px; background: #f8f9fa; font-size: 0.85rem; font-weight: bold; border-bottom: 1px solid #eee;">
        TUS TRABAJOS DISPONIBLES
    </div>
    <?php if (!empty($mis_trabajos_libres)): ?>
        <?php foreach ($mis_trabajos_libres as $t): ?>
            <div class="proyecto-item">
                <span style="font-size: 0.9rem; font-weight: 500;"><?php echo htmlspecialchars($t['titulo']); ?></span>
                
                <!-- Cambio de <a> a <button> para abrir el modal -->
                <button type="button" 
                        onclick="abrirModalPropuesta('<?php echo $t['id']; ?>', '<?php echo addslashes(htmlspecialchars($t['titulo'])); ?>')" 
                        class="btn-enviar-peq" 
                        style="border: none; cursor: pointer; display: inline-block;">
                    Proponer
                </button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="padding: 20px; text-align: center; color: #888; font-size: 0.9rem;">
            No tienes trabajos libres.
        </div>
    <?php endif; ?>
</div>

                    <!--<a href="mis_proyectos.php?filtro=sin_asignar" class="btn-ir-listado">
                        <i class="fas fa-tasks"></i> Gestionar mis proyectos libres
                    </a>-->

                <?php endif; ?>

                <a href="mensajes.php?con=<?php echo $aut['id']; ?>" class="btn-contactar">
                    <i class="fas fa-comment-dots"></i> Iniciar Conversación
                </a>
                
                <a href="profesionales.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Explorar otros expertos
                </a>
            </div>
        </div>
    </div>

    <script>
    function toggleProyectos() {
        const dropdown = document.getElementById('lista-proyectos-dropdown');
        dropdown.style.display = (dropdown.style.display === "none" || dropdown.style.display === "") ? "block" : "none";
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-proponer') && !event.target.closest('.btn-proponer')) {
            const dropdown = document.getElementById('lista-proyectos-dropdown');
            if (dropdown && dropdown.style.display === "block") {
                dropdown.style.display = "none";
            }
        }
    }


    function abrirModalPropuesta(id, titulo) {
    // 1. Asigna el ID del proyecto al campo oculto del formulario
    document.getElementById('modal_id_proyecto').value = id;
    
    // 2. Muestra el nombre del proyecto dentro del modal para que el usuario sepa cuál eligió
    document.getElementById('modal_titulo_proyecto').innerText = titulo;
    
    // 3. Cambia el estilo del modal de 'none' a 'block' para que aparezca
    document.getElementById('modalPropuesta').style.display = "block";
    
    // 4. Opcional: cierra el dropdown de proyectos al abrir el modal
    document.getElementById('lista-proyectos-dropdown').style.display = "none";
}

function cerrarModal() {
    // Oculta el modal de nuevo
    document.getElementById('modalPropuesta').style.display = "none";
}

// Cerrar el modal automáticamente si el usuario hace clic fuera de la caja blanca
window.onclick = function(event) {
    var modal = document.getElementById('modalPropuesta');
    if (event.target == modal) {
        cerrarModal();
    }
}
    </script>



<!-- VENTANA EMERGENTE (MODAL) -->
<div id="modalPropuesta" style="display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter: blur(3px);">
    <div style="background:#fff; margin:10% auto; padding:30px; border-radius:20px; width:90%; max-width:450px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position:relative;">
        
        <h2 style="margin-top:0; color:#333; font-family: 'Inter', sans-serif;">Enviar Propuesta</h2>
        <p style="color:#666; font-size:0.9rem;">Personaliza el mensaje para el profesional.</p>

        <form action="procesar_propuesta.php" method="POST">
            <!-- Campos ocultos necesarios para procesar la base de datos -->
            <input type="hidden" name="id_proyecto" id="modal_id_proyecto">
            <input type="hidden" name="id_autonomo" value="<?php echo $id_autonomo; ?>">

            <!-- Visualización del proyecto seleccionado -->
            <div style="background:#f1f3f5; padding:12px; border-radius:10px; margin-bottom:20px; border-left:4px solid #007bff;">
                <span style="font-size:0.8rem; color:#666; display:block;">Proyecto seleccionado:</span>
                <strong id="modal_titulo_proyecto" style="color:#000;"></strong>
            </div>

            <!-- Campo de mensaje -->
            <label style="display:block; margin-bottom:8px; font-weight:600;">Tu mensaje:</label>
            <textarea name="mensaje_propuesta" rows="5" 
                      style="width:100%; border:1px solid #ddd; border-radius:10px; padding:12px; box-sizing:border-box; font-family:inherit; resize:none;" 
                      placeholder="Cuéntale brevemente sobre el proyecto que quieres que haga..." required></textarea>

            <!-- Botones de acción -->
            <div style="margin-top:25px; display:flex; gap:12px;">
                <button type="submit" style="flex:2; background:#28a745; color:white; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;">Confirmar Propuesta</button>
                <button type="button" onclick="cerrarModal()" style="flex:1; background:#eee; color:#333; border:none; padding:14px; border-radius:10px; font-weight:bold; cursor:pointer;">Cancelar</button>
            </div>
        </form>
    </div>
</div>



<?php if (isset($_GET['status']) && $_GET['status'] === 'propuesta_enviada'): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin: 20px auto; max-width: 850px; text-align: center; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle"></i> ¡Propuesta enviada con éxito! El profesional ha recibido un mensaje con los detalles.
    </div>
<?php endif; ?>


</body>
</html>