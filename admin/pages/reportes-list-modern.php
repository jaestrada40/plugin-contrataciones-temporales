<?php
/**
 * Página de Reportes - Diseño Moderno
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

// Cargar estilos y scripts modernos
wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
wp_enqueue_style('modern-admin-css', plugin_dir_url(__FILE__) . '../css/modern-admin.css', array('bootstrap-css'), '1.0.0');
wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true);

global $wpdb;

// Obtener estadísticas generales
$stats_general = $wpdb->get_row("
    SELECT 
        (SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin) as total_vacantes,
        (SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE estado = 'Activa') as vacantes_activas,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin) as total_aplicaciones,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE estado = 'Pendiente') as aplicaciones_pendientes,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE estado = 'Aceptada') as aplicaciones_aceptadas,
        (SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin) as total_direcciones,
        (SELECT COUNT(*) FROM {$wpdb->prefix}tipos_contrato_minfin) as total_tipos_contrato
", ARRAY_A);

// Calcular tasa de conversión
$tasa_conversion = $stats_general['total_aplicaciones'] > 0 ? 
    round(($stats_general['aplicaciones_aceptadas'] / $stats_general['total_aplicaciones']) * 100, 2) : 0;

// Obtener efectividad por dirección
$efectividad_direcciones = $wpdb->get_results("
    SELECT 
        d.nombre as direccion,
        COUNT(DISTINCT v.id) as total_vacantes,
        COUNT(DISTINCT CASE WHEN v.estado = 'Activa' THEN v.id END) as vacantes_activas,
        COUNT(a.id) as total_aplicaciones,
        COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas,
        CASE 
            WHEN COUNT(a.id) > 0 THEN ROUND((COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) / COUNT(a.id)) * 100, 2)
            ELSE 0 
        END as efectividad,
        CASE 
            WHEN COUNT(DISTINCT v.id) > 0 THEN ROUND(COUNT(a.id) / COUNT(DISTINCT v.id), 1)
            ELSE 0 
        END as promedio_aplicaciones
    FROM {$wpdb->prefix}direcciones_minfin d
    LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id
    LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
    GROUP BY d.id, d.nombre
    ORDER BY efectividad DESC, total_aplicaciones DESC
");

// Obtener tendencias mensuales
$tendencias_mensuales = $wpdb->get_results("
    SELECT 
        DATE_FORMAT(v.fecha_creacion, '%Y-%m') as mes,
        COUNT(DISTINCT v.id) as vacantes_creadas,
        COUNT(a.id) as aplicaciones_recibidas,
        COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
    WHERE v.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(v.fecha_creacion, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 12
");

// Obtener aplicaciones por estado
$aplicaciones_por_estado = $wpdb->get_results("
    SELECT estado, COUNT(*) as total 
    FROM {$wpdb->prefix}aplicaciones_minfin 
    GROUP BY estado 
    ORDER BY total DESC
");

// Obtener aplicaciones recientes
$aplicaciones_recientes = $wpdb->get_results("
    SELECT a.*, 
           CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
           v.titulo as vacante_titulo,
           d.nombre as direccion_nombre
    FROM {$wpdb->prefix}aplicaciones_minfin a
    LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id
    LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
    ORDER BY a.fecha_aplicacion DESC
    LIMIT 15
");

// Obtener vacantes más populares
$vacantes_populares = $wpdb->get_results("
    SELECT v.titulo, v.codigo, d.nombre as direccion_nombre, 
           tc.nombre as tipo_contrato,
           COUNT(a.id) as total_aplicaciones,
           v.estado, v.fecha_limite
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
    LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
    LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
    GROUP BY v.id
    ORDER BY total_aplicaciones DESC, v.fecha_creacion DESC
    LIMIT 15
");

// Obtener perfiles de candidatos
$perfiles_candidatos = $wpdb->get_results("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(fecha_nacimiento, '%Y-%m-%d'), CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
            WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(fecha_nacimiento, '%Y-%m-%d'), CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
            WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(fecha_nacimiento, '%Y-%m-%d'), CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
            WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(fecha_nacimiento, '%Y-%m-%d'), CURDATE()) > 45 THEN '45+'
            ELSE 'No especificado'
        END as rango_edad,
        COUNT(*) as total,
        COUNT(CASE WHEN estado = 'Aceptada' THEN 1 END) as aceptadas
    FROM {$wpdb->prefix}aplicaciones_minfin
    WHERE fecha_nacimiento IS NOT NULL AND fecha_nacimiento != ''
    GROUP BY rango_edad
    ORDER BY total DESC
");

// Filtros para reportes
$fecha_inicio = sanitize_text_field($_GET['fecha_inicio'] ?? date('Y-m-01'));
$fecha_fin = sanitize_text_field($_GET['fecha_fin'] ?? date('Y-m-d'));
$direccion_filtro = intval($_GET['direccion_id'] ?? 0);

// Obtener direcciones para filtro
$direcciones = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}direcciones_minfin ORDER BY nombre ASC");
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-chart-bar me-3"></i>Reportes y Estadísticas</h1>
            <p class="subtitle">Análisis detallado del sistema de vacantes y aplicaciones</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
            <button onclick="exportarDatos()" class="btn btn-primary">
                <i class="fas fa-download me-2"></i>Exportar
            </button>
        </div>
    </div>

    <!-- Tarjetas Principales - Formato Centrado como otras páginas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-primary">
                <div class="icon-wrapper">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div class="number"><?php echo $tasa_conversion; ?>%</div>
                <div class="label">Tasa de Conversión</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-success">
                <div class="icon-wrapper">
                    <i class="fas fa-users fa-lg"></i>
                </div>
                <div class="number"><?php echo round(intval($stats_general['total_aplicaciones']) / max(intval($stats_general['total_vacantes']), 1), 1); ?></div>
                <div class="label">Promedio Aplicaciones/Vacante</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-warning">
                <div class="icon-wrapper">
                    <i class="fas fa-clock fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats_general['aplicaciones_pendientes']); ?></div>
                <div class="label">Aplicaciones Pendientes</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-info">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats_general['aplicaciones_aceptadas']); ?></div>
                <div class="label">Aplicaciones Aceptadas</div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="report-tabs mb-4">
        <ul class="nav nav-tabs" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="efectividad-tab" data-bs-toggle="tab" data-bs-target="#efectividad" type="button" role="tab">
                    <i class="fas fa-chart-bar me-2"></i>Efectividad por Dirección
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tendencias-tab" data-bs-toggle="tab" data-bs-target="#tendencias" type="button" role="tab">
                    <i class="fas fa-chart-line me-2"></i>Tendencias Mensuales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="perfiles-tab" data-bs-toggle="tab" data-bs-target="#perfiles" type="button" role="tab">
                    <i class="fas fa-user-tie me-2"></i>Perfiles de Candidatos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vacantes-tab" data-bs-toggle="tab" data-bs-target="#vacantes" type="button" role="tab">
                    <i class="fas fa-briefcase me-2"></i>Reporte de Vacantes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="aplicaciones-tab" data-bs-toggle="tab" data-bs-target="#aplicaciones" type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Reporte de Aplicaciones
                </button>
            </li>
        </ul>
    </div>

    <!-- Contenido de las Pestañas -->
    <div class="tab-content" id="reportTabsContent">
        <!-- Efectividad por Dirección -->
        <div class="tab-pane fade show active" id="efectividad" role="tabpanel">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Efectividad por Dirección</h5>
                    <button onclick="exportarEfectividad()" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Exportar Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dirección</th>
                                    <th class="text-center">Total Vacantes</th>
                                    <th class="text-center">Vacantes Activas</th>
                                    <th class="text-center">Total Aplicaciones</th>
                                    <th class="text-center">Aplicaciones Aceptadas</th>
                                    <th class="text-center">% Efectividad</th>
                                    <th class="text-center">Promedio Aplicaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($efectividad_direcciones)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No hay datos de efectividad disponibles</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($efectividad_direcciones as $direccion): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($direccion->direccion); ?></strong></td>
                                    <td class="text-center"><?php echo intval($direccion->total_vacantes); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $direccion->vacantes_activas > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo intval($direccion->vacantes_activas); ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo intval($direccion->total_aplicaciones); ?></td>
                                    <td class="text-center"><?php echo intval($direccion->aplicaciones_aceptadas); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                                <?php 
                                                $efectividad = floatval($direccion->efectividad);
                                                $color = $efectividad >= 70 ? 'success' : ($efectividad >= 40 ? 'warning' : ($efectividad > 0 ? 'danger' : 'secondary'));
                                                ?>
                                                <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $efectividad; ?>%"></div>
                                            </div>
                                            <strong class="text-<?php echo $color; ?>"><?php echo $efectividad; ?>%</strong>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo floatval($direccion->promedio_aplicaciones); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tendencias Mensuales -->
        <div class="tab-pane fade" id="tendencias" role="tabpanel">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tendencias Mensuales (Últimos 12 meses)</h5>
                    <button onclick="exportarTendencias()" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Exportar Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mes</th>
                                    <th class="text-center">Vacantes Creadas</th>
                                    <th class="text-center">Aplicaciones Recibidas</th>
                                    <th class="text-center">Aplicaciones Aceptadas</th>
                                    <th class="text-center">Tasa de Aceptación</th>
                                    <th class="text-center">Tendencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tendencias_mensuales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No hay datos de tendencias disponibles</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php 
                                $meses_es = [
                                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                                ];
                                foreach ($tendencias_mensuales as $index => $tendencia): 
                                    $fecha_parts = explode('-', $tendencia->mes);
                                    $mes_nombre = $meses_es[$fecha_parts[1]] . ' ' . $fecha_parts[0];
                                    $tasa_aceptacion = $tendencia->aplicaciones_recibidas > 0 ? 
                                        round(($tendencia->aplicaciones_aceptadas / $tendencia->aplicaciones_recibidas) * 100, 1) : 0;
                                    
                                    // Calcular tendencia comparando con el mes anterior
                                    $tendencia_icon = 'fas fa-minus text-muted';
                                    if ($index < count($tendencias_mensuales) - 1) {
                                        $mes_anterior = $tendencias_mensuales[$index + 1];
                                        if ($tendencia->aplicaciones_recibidas > $mes_anterior->aplicaciones_recibidas) {
                                            $tendencia_icon = 'fas fa-arrow-up text-success';
                                        } elseif ($tendencia->aplicaciones_recibidas < $mes_anterior->aplicaciones_recibidas) {
                                            $tendencia_icon = 'fas fa-arrow-down text-danger';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo $mes_nombre; ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <?php echo intval($tendencia->vacantes_creadas); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <?php echo intval($tendencia->aplicaciones_recibidas); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <?php echo intval($tendencia->aplicaciones_aceptadas); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="progress me-2" style="width: 50px; height: 6px;">
                                                <div class="progress-bar bg-<?php echo $tasa_aceptacion >= 50 ? 'success' : ($tasa_aceptacion >= 25 ? 'warning' : 'danger'); ?>" 
                                                     style="width: <?php echo min($tasa_aceptacion, 100); ?>%"></div>
                                            </div>
                                            <small><strong><?php echo $tasa_aceptacion; ?>%</strong></small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <i class="<?php echo $tendencia_icon; ?>"></i>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perfiles de Candidatos -->
        <div class="tab-pane fade" id="perfiles" role="tabpanel">
            <div class="row g-4">
                <!-- Distribución por Edad -->
                <div class="col-md-6">
                    <div class="data-table">
                        <div class="card-header">
                            <h5 class="mb-0">Distribución por Edad</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Rango de Edad</th>
                                            <th class="text-center">Total Aplicaciones</th>
                                            <th class="text-center">Aceptadas</th>
                                            <th class="text-center">% Éxito</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($perfiles_candidatos)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="fas fa-user-tie fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No hay datos de perfiles disponibles</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($perfiles_candidatos as $perfil): 
                                            $porcentaje_exito = $perfil->total > 0 ? round(($perfil->aceptadas / $perfil->total) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($perfil->rango_edad); ?> años</strong></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <?php echo intval($perfil->total); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <?php echo intval($perfil->aceptadas); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div class="progress me-2" style="width: 40px; height: 6px;">
                                                        <div class="progress-bar bg-<?php echo $porcentaje_exito >= 50 ? 'success' : ($porcentaje_exito >= 25 ? 'warning' : 'danger'); ?>" 
                                                             style="width: <?php echo min($porcentaje_exito, 100); ?>%"></div>
                                                    </div>
                                                    <small><strong><?php echo $porcentaje_exito; ?>%</strong></small>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estados de Aplicaciones -->
                <div class="col-md-6">
                    <div class="data-table">
                        <div class="card-header">
                            <h5 class="mb-0">Estados de Aplicaciones</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Estado</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($aplicaciones_por_estado)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <div class="empty-state">
                                                    <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No hay datos de estados disponibles</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php 
                                        $total_aplicaciones_estados = array_sum(array_column($aplicaciones_por_estado, 'total'));
                                        foreach ($aplicaciones_por_estado as $estado): 
                                            $porcentaje = $total_aplicaciones_estados > 0 ? round(($estado->total / $total_aplicaciones_estados) * 100, 1) : 0;
                                            $badge_color = match($estado->estado) {
                                                'Aceptada' => 'success',
                                                'Pendiente' => 'warning',
                                                'Rechazada' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $badge_color; ?>-subtle text-<?php echo $badge_color; ?> border border-<?php echo $badge_color; ?>-subtle">
                                                    <?php echo esc_html($estado->estado); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <strong><?php echo intval($estado->total); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <div class="progress me-2" style="width: 50px; height: 6px;">
                                                        <div class="progress-bar bg-<?php echo $badge_color; ?>" style="width: <?php echo $porcentaje; ?>%"></div>
                                                    </div>
                                                    <small><strong><?php echo $porcentaje; ?>%</strong></small>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte de Vacantes -->
        <div class="tab-pane fade" id="vacantes" role="tabpanel">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Vacantes Más Populares</h5>
                    <button onclick="exportarVacantes()" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Exportar Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Vacante</th>
                                    <th>Dirección</th>
                                    <th>Tipo de Contrato</th>
                                    <th class="text-center">Aplicaciones</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Fecha Límite</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vacantes_populares)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-briefcase fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No hay vacantes disponibles</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($vacantes_populares as $vacante): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo esc_html($vacante->titulo); ?></div>
                                            <div class="small text-primary">Código: <?php echo esc_html($vacante->codigo); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <?php echo esc_html($vacante->direccion_nombre ?: 'No asignada'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <?php echo esc_html($vacante->tipo_contrato ?: 'No especificado'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                                                <?php echo intval($vacante->total_aplicaciones); ?>
                                            </span>
                                            <small class="text-muted">aplicaciones</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $estado_color = match($vacante->estado) {
                                            'Activa' => 'success',
                                            'Inactiva' => 'secondary',
                                            'Cerrada' => 'danger',
                                            default => 'warning'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $estado_color; ?>-subtle text-<?php echo $estado_color; ?> border border-<?php echo $estado_color; ?>-subtle">
                                            <?php echo esc_html($vacante->estado ?: 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($vacante->fecha_limite): ?>
                                        <div class="small">
                                            <?php 
                                            $fecha_limite = new DateTime($vacante->fecha_limite);
                                            $hoy = new DateTime();
                                            $dias_restantes = $hoy->diff($fecha_limite)->days;
                                            $vencida = $fecha_limite < $hoy;
                                            ?>
                                            <div class="<?php echo $vencida ? 'text-danger' : ($dias_restantes <= 7 ? 'text-warning' : 'text-muted'); ?>">
                                                <?php echo $fecha_limite->format('d/m/Y'); ?>
                                            </div>
                                            <?php if ($vencida): ?>
                                            <small class="text-danger">Vencida</small>
                                            <?php elseif ($dias_restantes <= 7): ?>
                                            <small class="text-warning"><?php echo $dias_restantes; ?> días</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted fst-italic">Sin fecha límite</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte de Aplicaciones -->
        <div class="tab-pane fade" id="aplicaciones" role="tabpanel">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Aplicaciones Recientes</h5>
                    <button onclick="exportarAplicaciones()" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Exportar Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Candidato</th>
                                    <th>Vacante</th>
                                    <th>Dirección</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Fecha Aplicación</th>
                                    <th class="text-center">Contacto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($aplicaciones_recientes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No hay aplicaciones disponibles</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($aplicaciones_recientes as $aplicacion): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-2">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo esc_html($aplicacion->nombre_completo); ?></div>
                                                <div class="small text-muted">ID: <?php echo $aplicacion->id; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium"><?php echo esc_html($aplicacion->vacante_titulo ?: 'Vacante no disponible'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <?php echo esc_html($aplicacion->direccion_nombre ?: 'No asignada'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $estado_color = match($aplicacion->estado) {
                                            'Aceptada' => 'success',
                                            'Pendiente' => 'warning',
                                            'Rechazada' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $estado_color; ?>-subtle text-<?php echo $estado_color; ?> border border-<?php echo $estado_color; ?>-subtle">
                                            <?php echo esc_html($aplicacion->estado ?: 'Sin estado'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="small">
                                            <?php 
                                            if ($aplicacion->fecha_aplicacion) {
                                                $fecha = new DateTime($aplicacion->fecha_aplicacion);
                                                echo $fecha->format('d/m/Y');
                                                echo '<br><span class="text-muted">' . $fecha->format('H:i') . '</span>';
                                            } else {
                                                echo '<span class="text-muted">No disponible</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="small">
                                            <?php if ($aplicacion->email): ?>
                                            <div class="text-primary">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo esc_html($aplicacion->email); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($aplicacion->telefono): ?>
                                            <div class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo esc_html($aplicacion->telefono); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Los estilos se cargan desde modern-admin.css -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Bootstrap Tabs
    const triggerTabList = document.querySelectorAll('#reportTabs button');
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', event => {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Animación de hover en las tarjetas de estadísticas
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Animación de aparición para las tarjetas
    const cards = document.querySelectorAll('.stats-card, .data-table');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Efecto en las barras de progreso
    const progressBars = document.querySelectorAll('.progress-bar');
    setTimeout(() => {
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });
    }, 1000);
});

// Función para exportar datos generales con mejor formato
function exportarDatos() {
    const datos = {
        fecha_generacion: new Date().toLocaleDateString('es-GT'),
        estadisticas_generales: <?php echo json_encode($stats_general); ?>
    };
    
    let excel = '';
    
    excel += '<html><head><meta charset="UTF-8"></head><body>';
    excel += '<table border="1" cellpadding="5" cellspacing="0">';
    
    // Encabezado del reporte
    excel += '<tr><td colspan="2" style="background-color:#0066cc;color:white;font-weight:bold;text-align:center;font-size:16px;">MINISTERIO DE FINANZAS PÚBLICAS</td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#004499;color:white;font-weight:bold;text-align:center;font-size:14px;">REPORTE GENERAL DE VACANTES Y APLICACIONES</td></tr>';
    excel += '<tr><td style="background-color:#f0f0f0;font-weight:bold;">Fecha de Generación:</td><td>' + datos.fecha_generacion + '</td></tr>';
    excel += '<tr><td style="background-color:#f0f0f0;font-weight:bold;">Hora de Generación:</td><td>' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td style="background-color:#f0f0f0;font-weight:bold;">Usuario:</td><td><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td colspan="2"></td></tr>';
    
    // Estadísticas principales
    excel += '<tr><td colspan="2" style="background-color:#e6f3ff;font-weight:bold;text-align:center;">ESTADÍSTICAS PRINCIPALES</td></tr>';
    excel += '<tr style="background-color:#f9f9f9;font-weight:bold;"><td>Concepto</td><td>Cantidad</td></tr>';
    excel += '<tr><td>Total Vacantes</td><td style="text-align:center;">' + (datos.estadisticas_generales.total_vacantes || 0) + '</td></tr>';
    excel += '<tr><td>Vacantes Activas</td><td style="text-align:center;">' + (datos.estadisticas_generales.vacantes_activas || 0) + '</td></tr>';
    excel += '<tr><td>Total Aplicaciones</td><td style="text-align:center;">' + (datos.estadisticas_generales.total_aplicaciones || 0) + '</td></tr>';
    excel += '<tr><td>Aplicaciones Pendientes</td><td style="text-align:center;">' + (datos.estadisticas_generales.aplicaciones_pendientes || 0) + '</td></tr>';
    excel += '<tr><td>Aplicaciones Aceptadas</td><td style="text-align:center;">' + (datos.estadisticas_generales.aplicaciones_aceptadas || 0) + '</td></tr>';
    excel += '<tr><td>Total Direcciones</td><td style="text-align:center;">' + (datos.estadisticas_generales.total_direcciones || 0) + '</td></tr>';
    excel += '<tr><td>Tipos de Contrato</td><td style="text-align:center;">' + (datos.estadisticas_generales.total_tipos_contrato || 0) + '</td></tr>';
    
    // Métricas calculadas
    excel += '<tr><td colspan="2"></td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#ffffcc;font-weight:bold;text-align:center;">MÉTRICAS CALCULADAS</td></tr>';
    excel += '<tr style="background-color:#f9f9f9;font-weight:bold;"><td>Concepto</td><td>Valor</td></tr>';
    const tasaConversion = <?php echo $tasa_conversion; ?>;
    excel += '<tr><td>Tasa de Conversión</td><td style="text-align:center;">' + tasaConversion + '%</td></tr>';
    excel += '<tr><td>Promedio Aplicaciones por Vacante</td><td style="text-align:center;">' + (datos.estadisticas_generales.total_aplicaciones / Math.max(datos.estadisticas_generales.total_vacantes, 1)).toFixed(1) + '</td></tr>';
    excel += '<tr><td>Porcentaje Vacantes Activas</td><td style="text-align:center;">' + ((datos.estadisticas_generales.vacantes_activas / Math.max(datos.estadisticas_generales.total_vacantes, 1)) * 100).toFixed(1) + '%</td></tr>';
    excel += '<tr><td>Porcentaje Aplicaciones Aceptadas</td><td style="text-align:center;">' + ((datos.estadisticas_generales.aplicaciones_aceptadas / Math.max(datos.estadisticas_generales.total_aplicaciones, 1)) * 100).toFixed(1) + '%</td></tr>';
    
    // Información del sistema
    excel += '<tr><td colspan="2"></td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#f0f8ff;font-weight:bold;text-align:center;">INFORMACIÓN DEL SISTEMA</td></tr>';
    excel += '<tr style="background-color:#f9f9f9;font-weight:bold;"><td>Concepto</td><td>Valor</td></tr>';
    excel += '<tr><td>Fecha del Reporte</td><td style="text-align:center;">' + new Date().toLocaleDateString('es-GT') + '</td></tr>';
    excel += '<tr><td>Hora del Reporte</td><td style="text-align:center;">' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td>Generado por</td><td style="text-align:center;"><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td>Versión del Plugin</td><td style="text-align:center;">1.0.0</td></tr>';
    
    excel += '</table></body></html>';
    
    const blob = new Blob(['\ufeff' + excel], { 
        type: 'application/vnd.ms-excel;charset=utf-8;' 
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Reporte_General_MINFIN_' + new Date().toISOString().split('T')[0] + '.xls');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Mostrar notificación mejorada
    mostrarNotificacion('📊 Reporte general exportado correctamente. El archivo incluye todas las estadísticas principales del sistema.', 'success');
}

// Sistema de notificaciones mejorado
function mostrarNotificacion(mensaje, tipo = 'success') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    `;
    
    const iconos = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="${iconos[tipo]} me-2"></i>
            <div class="flex-grow-1">${mensaje}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Función para exportar efectividad por dirección con datos reales
function exportarEfectividad() {
    const tabla = document.querySelector('#efectividad table tbody');
    const filas = tabla.querySelectorAll('tr');
    
    if (filas.length === 1 && filas[0].querySelector('.empty-state')) {
        mostrarNotificacion('No hay datos disponibles para exportar', 'warning');
        return;
    }
    
    let excel = '';
    
    // Encabezado HTML para mejor formato en Excel
    excel += '<html><head><meta charset="UTF-8"></head><body>';
    excel += '<table border="1" cellpadding="5" cellspacing="0">';
    
    // Encabezado del reporte
    excel += '<tr><td colspan="7" style="background-color:#0066cc;color:white;font-weight:bold;text-align:center;font-size:16px;">MINISTERIO DE FINANZAS PÚBLICAS</td></tr>';
    excel += '<tr><td colspan="7" style="background-color:#004499;color:white;font-weight:bold;text-align:center;font-size:14px;">REPORTE DE EFECTIVIDAD POR DIRECCIÓN</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Fecha de Generación:</td><td colspan="4">' + new Date().toLocaleDateString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Hora de Generación:</td><td colspan="4">' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Usuario:</td><td colspan="4"><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td colspan="7"></td></tr>';
    
    // Encabezados de columnas
    excel += '<tr style="background-color:#e6f3ff;font-weight:bold;">';
    excel += '<td>DIRECCIÓN</td><td>TOTAL VACANTES</td><td>VACANTES ACTIVAS</td><td>TOTAL APLICACIONES</td><td>APLICACIONES ACEPTADAS</td><td>% EFECTIVIDAD</td><td>PROMEDIO APLICACIONES</td>';
    excel += '</tr>';
    
    // Extraer datos de la tabla
    let totalVacantes = 0, totalAplicaciones = 0, totalAceptadas = 0;
    
    filas.forEach(fila => {
        if (!fila.querySelector('.empty-state')) {
            const celdas = fila.querySelectorAll('td');
            if (celdas.length >= 7) {
                const direccion = celdas[0].textContent.trim();
                const vacantes = celdas[1].textContent.trim();
                const vacantesActivas = celdas[2].textContent.trim();
                const aplicaciones = celdas[3].textContent.trim();
                const aceptadas = celdas[4].textContent.trim();
                const efectividad = celdas[5].textContent.trim().replace(/[^\d.%]/g, '');
                const promedio = celdas[6].textContent.trim();
                
                excel += '<tr>';
                excel += `<td>${direccion}</td><td style="text-align:center;">${vacantes}</td><td style="text-align:center;">${vacantesActivas}</td><td style="text-align:center;">${aplicaciones}</td><td style="text-align:center;">${aceptadas}</td><td style="text-align:center;">${efectividad}</td><td style="text-align:center;">${promedio}</td>`;
                excel += '</tr>';
                
                totalVacantes += parseInt(vacantes) || 0;
                totalAplicaciones += parseInt(aplicaciones) || 0;
                totalAceptadas += parseInt(aceptadas) || 0;
            }
        }
    });
    
    // Resumen
    excel += '<tr><td colspan="7"></td></tr>';
    excel += '<tr><td colspan="7" style="background-color:#ffffcc;font-weight:bold;text-align:center;">RESUMEN GENERAL</td></tr>';
    excel += `<tr><td colspan="3" style="background-color:#f9f9f9;font-weight:bold;">Total Direcciones:</td><td colspan="4">${filas.length}</td></tr>`;
    excel += `<tr><td colspan="3" style="background-color:#f9f9f9;font-weight:bold;">Total Vacantes:</td><td colspan="4">${totalVacantes}</td></tr>`;
    excel += `<tr><td colspan="3" style="background-color:#f9f9f9;font-weight:bold;">Total Aplicaciones:</td><td colspan="4">${totalAplicaciones}</td></tr>`;
    excel += `<tr><td colspan="3" style="background-color:#f9f9f9;font-weight:bold;">Total Aceptadas:</td><td colspan="4">${totalAceptadas}</td></tr>`;
    excel += `<tr><td colspan="3" style="background-color:#f9f9f9;font-weight:bold;">Efectividad Promedio:</td><td colspan="4">${totalAplicaciones > 0 ? ((totalAceptadas / totalAplicaciones) * 100).toFixed(2) : 0}%</td></tr>`;
    
    excel += '</table></body></html>';
    
    // Crear y descargar archivo XLS
    const blob = new Blob(['\ufeff' + excel], { 
        type: 'application/vnd.ms-excel;charset=utf-8;' 
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Efectividad_Direcciones_MINFIN_' + new Date().toISOString().split('T')[0] + '.xls');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Mostrar notificación mejorada
    mostrarNotificacion('📊 Reporte de efectividad exportado correctamente. El archivo se ha descargado automáticamente.', 'success');
}

// Función para exportar tendencias mensuales
function exportarTendencias() {
    const tabla = document.querySelector('#tendencias table tbody');
    const filas = tabla.querySelectorAll('tr');
    
    if (filas.length === 1 && filas[0].querySelector('.empty-state')) {
        mostrarNotificacion('No hay datos de tendencias disponibles para exportar', 'warning');
        return;
    }
    
    let excel = '';
    
    excel += '<html><head><meta charset="UTF-8"></head><body>';
    excel += '<table border="1" cellpadding="5" cellspacing="0">';
    
    excel += '<tr><td colspan="5" style="background-color:#0066cc;color:white;font-weight:bold;text-align:center;font-size:16px;">MINISTERIO DE FINANZAS PÚBLICAS</td></tr>';
    excel += '<tr><td colspan="5" style="background-color:#004499;color:white;font-weight:bold;text-align:center;font-size:14px;">REPORTE DE TENDENCIAS MENSUALES</td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#f0f0f0;font-weight:bold;">Fecha de Generación:</td><td colspan="3">' + new Date().toLocaleDateString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#f0f0f0;font-weight:bold;">Hora de Generación:</td><td colspan="3">' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="2" style="background-color:#f0f0f0;font-weight:bold;">Usuario:</td><td colspan="3"><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td colspan="5"></td></tr>';
    
    excel += '<tr style="background-color:#e6f3ff;font-weight:bold;">';
    excel += '<td>MES</td><td>VACANTES CREADAS</td><td>APLICACIONES RECIBIDAS</td><td>APLICACIONES ACEPTADAS</td><td>TASA DE ACEPTACIÓN</td>';
    excel += '</tr>';
    
    filas.forEach(fila => {
        if (!fila.querySelector('.empty-state')) {
            const celdas = fila.querySelectorAll('td');
            if (celdas.length >= 5) {
                const mes = celdas[0].textContent.trim();
                const vacantes = celdas[1].textContent.trim();
                const aplicaciones = celdas[2].textContent.trim();
                const aceptadas = celdas[3].textContent.trim();
                const tasa = celdas[4].textContent.trim().replace(/[^\d.%]/g, '');
                
                excel += '<tr>';
                excel += `<td>${mes}</td><td style="text-align:center;">${vacantes}</td><td style="text-align:center;">${aplicaciones}</td><td style="text-align:center;">${aceptadas}</td><td style="text-align:center;">${tasa}</td>`;
                excel += '</tr>';
            }
        }
    });
    
    excel += '</table></body></html>';
    
    const blob = new Blob(['\ufeff' + excel], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Tendencias_Mensuales_MINFIN_' + new Date().toISOString().split('T')[0] + '.xls');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mostrarNotificacion('📈 Reporte de tendencias exportado correctamente. El archivo se ha descargado automáticamente.', 'success');
}

// Función para exportar vacantes
function exportarVacantes() {
    const tabla = document.querySelector('#vacantes table tbody');
    const filas = tabla.querySelectorAll('tr');
    
    if (filas.length === 1 && filas[0].querySelector('.empty-state')) {
        mostrarNotificacion('No hay datos de vacantes disponibles para exportar', 'warning');
        return;
    }
    
    let excel = '';
    
    excel += '<html><head><meta charset="UTF-8"></head><body>';
    excel += '<table border="1" cellpadding="5" cellspacing="0">';
    
    excel += '<tr><td colspan="7" style="background-color:#0066cc;color:white;font-weight:bold;text-align:center;font-size:16px;">MINISTERIO DE FINANZAS PÚBLICAS</td></tr>';
    excel += '<tr><td colspan="7" style="background-color:#004499;color:white;font-weight:bold;text-align:center;font-size:14px;">REPORTE DE VACANTES POPULARES</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Fecha de Generación:</td><td colspan="4">' + new Date().toLocaleDateString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Hora de Generación:</td><td colspan="4">' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="3" style="background-color:#f0f0f0;font-weight:bold;">Usuario:</td><td colspan="4"><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td colspan="7"></td></tr>';
    
    excel += '<tr style="background-color:#e6f3ff;font-weight:bold;">';
    excel += '<td>VACANTE</td><td>CÓDIGO</td><td>DIRECCIÓN</td><td>TIPO DE CONTRATO</td><td>APLICACIONES</td><td>ESTADO</td><td>FECHA LÍMITE</td>';
    excel += '</tr>';
    
    filas.forEach(fila => {
        if (!fila.querySelector('.empty-state')) {
            const celdas = fila.querySelectorAll('td');
            if (celdas.length >= 6) {
                const vacante = celdas[0].querySelector('.fw-bold').textContent.trim();
                const codigo = celdas[0].querySelector('.text-primary').textContent.replace('Código: ', '').trim();
                const direccion = celdas[1].textContent.trim();
                const tipoContrato = celdas[2].textContent.trim();
                const aplicaciones = celdas[3].textContent.trim();
                const estado = celdas[4].textContent.trim();
                const fechaLimite = celdas[5].textContent.trim();
                
                excel += '<tr>';
                excel += `<td>${vacante}</td><td>${codigo}</td><td>${direccion}</td><td>${tipoContrato}</td><td style="text-align:center;">${aplicaciones}</td><td style="text-align:center;">${estado}</td><td style="text-align:center;">${fechaLimite}</td>`;
                excel += '</tr>';
            }
        }
    });
    
    excel += '</table></body></html>';
    
    const blob = new Blob(['\ufeff' + excel], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Vacantes_Populares_MINFIN_' + new Date().toISOString().split('T')[0] + '.xls');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mostrarNotificacion('💼 Reporte de vacantes exportado correctamente. El archivo se ha descargado automáticamente.', 'success');
}

// Función para exportar aplicaciones
function exportarAplicaciones() {
    const tabla = document.querySelector('#aplicaciones table tbody');
    const filas = tabla.querySelectorAll('tr');
    
    if (filas.length === 1 && filas[0].querySelector('.empty-state')) {
        mostrarNotificacion('No hay datos de aplicaciones disponibles para exportar', 'warning');
        return;
    }
    
    let excel = '';
    
    excel += '<html><head><meta charset="UTF-8"></head><body>';
    excel += '<table border="1" cellpadding="5" cellspacing="0">';
    
    excel += '<tr><td colspan="8" style="background-color:#0066cc;color:white;font-weight:bold;text-align:center;font-size:16px;">MINISTERIO DE FINANZAS PÚBLICAS</td></tr>';
    excel += '<tr><td colspan="8" style="background-color:#004499;color:white;font-weight:bold;text-align:center;font-size:14px;">REPORTE DE APLICACIONES RECIENTES</td></tr>';
    excel += '<tr><td colspan="4" style="background-color:#f0f0f0;font-weight:bold;">Fecha de Generación:</td><td colspan="4">' + new Date().toLocaleDateString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="4" style="background-color:#f0f0f0;font-weight:bold;">Hora de Generación:</td><td colspan="4">' + new Date().toLocaleTimeString('es-GT') + '</td></tr>';
    excel += '<tr><td colspan="4" style="background-color:#f0f0f0;font-weight:bold;">Usuario:</td><td colspan="4"><?php echo wp_get_current_user()->display_name; ?></td></tr>';
    excel += '<tr><td colspan="8"></td></tr>';
    
    excel += '<tr style="background-color:#e6f3ff;font-weight:bold;">';
    excel += '<td>CANDIDATO</td><td>ID</td><td>VACANTE</td><td>DIRECCIÓN</td><td>ESTADO</td><td>FECHA APLICACIÓN</td><td>EMAIL</td><td>TELÉFONO</td>';
    excel += '</tr>';
    
    filas.forEach(fila => {
        if (!fila.querySelector('.empty-state')) {
            const celdas = fila.querySelectorAll('td');
            if (celdas.length >= 6) {
                const candidato = celdas[0].querySelector('.fw-bold').textContent.trim();
                const id = celdas[0].querySelector('.text-muted').textContent.replace('ID: ', '').trim();
                const vacante = celdas[1].textContent.trim();
                const direccion = celdas[2].textContent.trim();
                const estado = celdas[3].textContent.trim();
                const fecha = celdas[4].textContent.trim().replace(/\s+/g, ' ');
                const contacto = celdas[5].textContent.trim();
                const email = contacto.includes('@') ? contacto.split('\n')[0] : '';
                const telefono = contacto.includes('@') ? contacto.split('\n')[1] || '' : contacto;
                
                excel += '<tr>';
                excel += `<td>${candidato}</td><td style="text-align:center;">${id}</td><td>${vacante}</td><td>${direccion}</td><td style="text-align:center;">${estado}</td><td style="text-align:center;">${fecha}</td><td>${email}</td><td>${telefono}</td>`;
                excel += '</tr>';
            }
        }
    });
    
    excel += '</table></body></html>';
    
    const blob = new Blob(['\ufeff' + excel], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Aplicaciones_Recientes_MINFIN_' + new Date().toISOString().split('T')[0] + '.xls');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mostrarNotificacion('📋 Reporte de aplicaciones exportado correctamente. El archivo se ha descargado automáticamente.', 'success');
}

// Estilos adicionales para impresión
const printStyles = `
    @media print {
        .btn, .search-form { display: none !important; }
        .data-table { break-inside: avoid; }
        .stats-card { break-inside: avoid; }
        body { font-size: 12px; }
        .page-header h1 { font-size: 18px; }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = printStyles;
document.head.appendChild(styleSheet);
</script>