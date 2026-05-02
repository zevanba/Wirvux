<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];

// 1. Obtener sectores
$query_sectores = "SELECT DISTINCT categoria_principal FROM usuarios 
                   WHERE tipo_usuario = 'autonomo' 
                   AND categoria_principal IS NOT NULL 
                   AND categoria_principal != ''";
$res_sectores = mysqli_query($conexion, $query_sectores);

$sector_elegido = isset($_GET['sector']) ? mysqli_real_escape_string($conexion, $_GET['sector']) : '';

// 2. Consulta principal
$query_prof = "SELECT id, nombre, apellidos, especialidad, foto, descripcion, categoria_principal 
               FROM usuarios 
               WHERE tipo_usuario = 'autonomo'";

if ($sector_elegido != '') {
    $query_prof .= " AND categoria_principal = '$sector_elegido'";
}

$res_prof = mysqli_query($conexion, $query_prof);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Expertos | Wirvux</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --bg-color: #f4f7f6;
            --text-main: #333;
            --text-light: #666;
            --card-bg: #ffffff;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h2 {
            font-size: 2.5rem;
            color: var(--text-main);
            margin-bottom: 10px;
        }

        /* Estilo del Filtro */
        .filter-container {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
        }

        .filter-container select {
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
            outline: none;
            cursor: pointer;
            min-width: 250px;
            transition: border-color 0.3s;
        }

        .filter-container select:focus {
            border-color: var(--primary-color);
        }

        /* Grid de Profesionales */
        .grid-profesionales {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Tarjeta */
        .card-expert {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            cursor: pointer;
        }

        .card-expert:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.1);
        }

        .card-expert img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f0f0f0;
            margin-bottom: 15px;
        }

        .card-expert h3 {
            margin: 10px 0 5px;
            font-size: 1.25rem;
            color: var(--text-main);
        }

        .expert-tag {
            background: #e7f3ff;
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .expert-desc {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
            height: 54px; /* Limitado a 3 líneas aprox */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-view-profile {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            width: 100%;
            transition: background 0.3s;
        }

        .card-expert:hover .btn-view-profile {
            background-color: #0056b3;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 12px;
            color: var(--text-light);
        }
    </style>
</head>
<body>

    <div class="main-content">
        <header class="page-header">
            <h2>Encuentra al Experto Ideal</h2>
            <p>Explora perfiles verificados y conecta con el talento que necesitas.</p>
        </header>

        <!-- Filtro con nuevo estilo -->
        <div class="filter-container">
            <i class="fas fa-filter" style="color: var(--primary-color);"></i>
            <form action="profesionales.php" method="GET">
                <select name="sector" onchange="this.form.submit()">
                    <option value="">Todas las categorías</option>
                    <?php while($s = mysqli_fetch_assoc($res_sectores)): ?>
                        <option value="<?php echo htmlspecialchars($s['categoria_principal']); ?>" 
                            <?php echo ($sector_elegido == $s['categoria_principal']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['categoria_principal']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="grid-profesionales">
            <?php if(mysqli_num_rows($res_prof) > 0): ?>
                <?php while($aut = mysqli_fetch_assoc($res_prof)): ?>
                    <div class="card-expert" onclick="window.location.href='perfil_autonomo.php?id=<?php echo $aut['id']; ?>'">
                        <img src="<?php echo !empty($aut['foto']) ? $aut['foto'] : 'img/default-avatar.png'; ?>" alt="Foto de perfil">
                        <h3><?php echo htmlspecialchars($aut['nombre'] . " " . $aut['apellidos']); ?></h3>
                        <span class="expert-tag"><?php echo htmlspecialchars($aut['especialidad']); ?></span>
                        
                        <p class="expert-desc">
                            <?php echo !empty($aut['descripcion']) ? htmlspecialchars($aut['descripcion']) : 'Sin descripción disponible actualmente.'; ?>
                        </p>
                        
                        <button class="btn-view-profile">Ver perfil completo</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search-minus" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i>
                    <p>No se encontraron profesionales con estos criterios. Intenta con otra categoría.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>