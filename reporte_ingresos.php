<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'autonomo') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$anio_actual = date('Y');

/**
 * Aplica la lógica estricta a CADA trabajo individual:
 * Neto = Presupuesto - (1.5% + 0.25)
 * Se usa PHP_ROUND_HALF_DOWN para evitar que el 0,735 se transforme en 0,74
 */
function calcularNetoPorTrabajo($presupuesto) {
    $presupuesto = floatval($presupuesto);
    
    $comision_porcentaje = $presupuesto * 0.015; // 1,5%
    $comision_fija = 0.25;                       // 0,25 €
    
    $neto = $presupuesto - ($comision_porcentaje + $comision_fija);
    
    // Forzamos el redondeo hacia abajo si el tercer decimal es un 5
    return ($neto > 0) ? round($neto, 2, PHP_ROUND_HALF_DOWN) : 0;
}

// 1. Consulta para desglose mensual del AÑO ACTUAL
$ingresos_por_mes = [];
$query_mensual = "SELECT MONTH(fecha_creacion) as mes, presupuesto 
                  FROM trabajos 
                  WHERE id_autonomo = ? AND estado = 'completado' AND YEAR(fecha_creacion) = ?
                  ORDER BY mes ASC";

if ($stmt = mysqli_prepare($conexion, $query_mensual)) {
    mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $anio_actual);
    mysqli_stmt_execute($stmt);
    $res_mensual = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res_mensual)) {
        $mes = $row['mes'];
        
        $neto_trabajo = calcularNetoPorTrabajo($row['presupuesto']);
        
        if (!isset($ingresos_por_mes[$mes])) {
            $ingresos_por_mes[$mes] = 0;
        }
        $ingresos_por_mes[$mes] += $neto_trabajo;
    }
    mysqli_stmt_close($stmt);
}

$meses_claves = ["", "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"];
$meses_nombres_es = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];


// 2. Consulta para desglose ANUAL (Histórico últimos 7 años)
$ingresos_por_anio = [];
$query_anios_disponibles = "SELECT DISTINCT YEAR(fecha_creacion) as anio 
                            FROM trabajos 
                            WHERE id_autonomo = ? AND estado = 'completado' 
                            ORDER BY anio DESC LIMIT 7";

if ($stmt_anios = mysqli_prepare($conexion, $query_anios_disponibles)) {
    mysqli_stmt_bind_param($stmt_anios, "i", $id_usuario);
    mysqli_stmt_execute($stmt_anios);
    $res_anios = mysqli_stmt_get_result($stmt_anios);
    
    $lista_anios = [];
    while($r = mysqli_fetch_assoc($res_anios)) {
        $lista_anios[] = $r['anio'];
    }
    mysqli_stmt_close($stmt_anios);

    if (!empty($lista_anios)) {
        $in_clause = implode(',', $lista_anios);
        $query_anual = "SELECT YEAR(fecha_creacion) as anio, presupuesto 
                        FROM trabajos 
                        WHERE id_autonomo = ? AND estado = 'completado' AND YEAR(fecha_creacion) IN ($in_clause)
                        ORDER BY anio DESC";
        
        if ($stmt_anual = mysqli_prepare($conexion, $query_anual)) {
            mysqli_stmt_bind_param($stmt_anual, "i", $id_usuario);
            mysqli_stmt_execute($stmt_anual);
            $res_anual = mysqli_stmt_get_result($stmt_anual);
            
            while ($row_anio = mysqli_fetch_assoc($res_anual)) {
                $anio = $row_anio['anio'];
                
                $neto_trabajo = calcularNetoPorTrabajo($row_anio['presupuesto']);
                
                if (!isset($ingresos_por_anio[$anio])) {
                    $ingresos_por_anio[$anio] = 0;
                }
                $ingresos_por_anio[$anio] += $neto_trabajo;
            }
            mysqli_stmt_close($stmt_anual);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title data-key="title_page">Reporte de Ingresos | Wirvux</title>
    <style>
    :root {
        --primary-color: #635bff;
        --bg-color: #f4f7f6;
        --text-main: #212529;
        --text-light: #6c757d;
        --card-bg: #ffffff;
        --table-border: #e9ecef;
        --total-row-bg: #f8f9fa;
    }
    body.dark-mode { --bg-color: #1f2937; --text-main: #e0e0e0; --text-light: #b0b0b0; --card-bg: #111827; --table-border: #374151; --total-row-bg: #1f2937; }
    body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--text-main); margin: 0; padding: 0; transition: background-color 0.3s, color 0.3s; }
    .report-container { background: var(--card-bg); max-width: 600px; margin: 50px auto; padding: 30px 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .back-link { text-decoration: none; color: var(--primary-color); display: inline-block; margin-bottom: 20px; font-weight: 600; }
    .report-header h2 { font-size: 1.6rem; margin: 0 0 15px 0; color: var(--text-main); }
    .report-table { width: 100%; border-collapse: collapse; margin-top: 10px; color: var(--text-main); }
    .report-table th, .report-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
    .report-table th { background-color: var(--total-row-bg); color: var(--text-light); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
    .total-row { background-color: var(--total-row-bg); font-weight: bold; }
    .divider { border: 0; height: 1px; background: var(--table-border); margin: 35px 0; }
    </style>
</head>
<body>
    <div class="report-container">
        <a href="area_autonomo.php" class="back-link" data-key="btn_back">← Volver al Panel</a>
        <header class="report-header">
            <h2><span data-key="title_monthly">Ingresos Mensuales</span> (<?php echo htmlspecialchars($anio_actual); ?>)</h2>
        </header>
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr><th data-key="th_month">Mes</th><th data-key="th_total">Total Neto</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $total_del_anio = 0;
                    if(count($ingresos_por_mes) > 0):
                        foreach($ingresos_por_mes as $mes_num => $total_mes): 
                            $total_del_anio += $total_mes;
                    ?>
                        <tr>
                            <td data-month="<?php echo $meses_claves[$mes_num]; ?>"><?php echo $meses_nombres_es[$mes_num]; ?></td>
                            <td><?php echo number_format($total_mes, 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                        <tr class="total-row">
                            <td><strong data-key="label_annual_total">Total Anual</strong></td>
                            <td><strong><?php echo number_format($total_del_anio, 2, ',', '.'); ?> €</strong></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align: center;" data-key="empty_year">No hay registros este año.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <hr class="divider">
        <header class="report-header">
            <h2 data-key="title_history">Histórico de Ingresos (Últimos 7 años)</h2>
        </header>
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr><th data-key="th_year">Año</th><th data-key="th_total_annual">Total Anual Neto</th></tr>
                </thead>
                <tbody>
                    <?php 
                    if(count($ingresos_por_anio) > 0):
                        foreach($ingresos_por_anio as $anio_num => $total_anio): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($anio_num); ?></td>
                            <td><?php echo number_format($total_anio, 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                        <tr><td colspan="2" style="text-align: center;" data-key="empty_history">No hay registros históricos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    const translations = {
        'es': {
            'title_page': 'Reporte de Ingresos | Wirvux', 'btn_back': '← Volver al Panel', 'title_monthly': 'Ingresos Mensuales', 'th_month': 'Mes', 'th_total': 'Total Neto', 'label_annual_total': 'Total Anual', 'empty_year': 'No hay registros este año.', 'title_history': 'Histórico de Ingresos (Últimos 7 años)', 'th_year': 'Año', 'th_total_annual': 'Total Anual Neto', 'empty_history': 'No hay registros históricos.', 'footer_archive': 'Historial de Servicios', 'jan': 'Enero', 'feb': 'Febrero', 'mar': 'Marzo', 'apr': 'Abril', 'may': 'Mayo', 'jun': 'Junio', 'jul': 'Julio', 'aug': 'Agosto', 'sep': 'Septiembre', 'oct': 'Octubre', 'nov': 'Noviembre', 'dec': 'Diciembre'
        },
        'en': {
            'title_page': 'Income Report | Wirvux', 'btn_back': '← Back to Dashboard', 'title_monthly': 'Monthly Income', 'th_month': 'Month', 'th_total': 'Net Total', 'label_annual_total': 'Annual Total', 'empty_year': 'No records this year.', 'title_history': 'Income History (Last 7 years)', 'th_year': 'Year', 'th_total_annual': 'Annual Net Total', 'empty_history': 'No historical records found.', 'footer_archive': 'Service History', 'jan': 'January', 'feb': 'February', 'mar': 'March', 'apr': 'April', 'may': 'May', 'jun': 'June', 'jul': 'July', 'aug': 'August', 'sep': 'September', 'oct': 'October', 'nov': 'November', 'dec': 'December'
        }
    };
    function loadPreferences() {
        const lang = sessionStorage.getItem('lang') || 'es';
        const theme = sessionStorage.getItem('theme') || 'light';
        document.querySelectorAll('[data-key]').forEach(el => { const key = el.getAttribute('data-key'); if (translations[lang][key]) el.innerText = translations[lang][key]; });
        document.querySelectorAll('[data-month]').forEach(el => { const monthKey = el.getAttribute('data-month'); if (translations[lang][monthKey]) el.innerText = translations[lang][monthKey]; });
        document.title = translations[lang]['title_page'];
        if (theme === 'dark') document.body.classList.add('dark-mode');
        else document.body.classList.remove('dark-mode');
    }
    window.onload = loadPreferences;
    </script>
</body>
</html>
