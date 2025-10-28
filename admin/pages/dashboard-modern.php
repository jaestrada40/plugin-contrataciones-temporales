<?php
/**
 * Dashboard Principal - Diseño Moderno
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

// Cargar estilos modernos
wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
wp_enqueue_style('modern-admin-css', plugin_dir_url(__FILE__) . '../css/modern-admin.css', array('bootstrap-css'), '1.0.0');

global $wpdb;

// Obtener estadísticas generales
$stats = $wpdb->get_row("
    SELECT 
        (SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin) as total_vacantes,
        (SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE estado = 'Activa') as vacantes_activas,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin) as total_aplicaciones,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE estado = 'Pendiente') as aplicaciones_pendientes,
        (SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin WHERE activa = 1) as direcciones_activas,
        (SELECT COUNT(*) FROM {$wpdb->prefix}tipos_contrato_minfin WHERE activo = 1) as tipos_contrato_activos
", ARRAY_A);

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
    LIMIT 5
");

// Obtener vacantes recientes
$vacantes_recientes = $wpdb->get_results("
    SELECT v.*, d.nombre as direccion_nombre, COUNT(a.id) as total_aplicaciones
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
    LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
    WHERE v.estado = 'Activa'
    GROUP BY v.id
    ORDER BY v.fecha_creacion DESC
    LIMIT 5
");

// Obtener estadísticas por dirección
$stats_direcciones = $wpdb->get_results("
    SELECT d.nombre, COUNT(v.id) as total_vacantes, COUNT(a.id) as total_aplicaciones
    FROM {$wpdb->prefix}direcciones_minfin d
    LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id AND v.estado = 'Activa'
    LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
    WHERE d.activa = 1
    GROUP BY d.id, d.nombre
    ORDER BY total_vacantes DESC
    LIMIT 5
");

// Calcular porcentajes y tendencias
$total_apps = intval($stats['total_aplicaciones']);
$apps_pendientes = intval($stats['aplicaciones_pendientes']);
$porcentaje_pendientes = $total_apps > 0 ? round(($apps_pendientes / $total_apps) * 100, 1) : 0;

$total_vacantes = intval($stats['total_vacantes']);
$vacantes_activas = intval($stats['vacantes_activas']);
$porcentaje_activas = $total_vacantes > 0 ? round(($vacantes_activas / $total_vacantes) * 100, 1) : 0;
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1>Dashboard</h1>
            <p class="subtitle">Bienvenido al sistema de gestión de vacantes del MINFIN</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success">
                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Sistema Activo
            </span>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas - Mismo formato que Vacantes -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-primary">
                <div class="icon-wrapper">
                    <i class="fas fa-building fa-lg"></i>
                </div>
                <div class="number"><?php echo $stats['direcciones_activas']; ?></div>
                <div class="label">Direcciones</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-success">
                <div class="icon-wrapper">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div class="number"><?php echo $vacantes_activas; ?></div>
                <div class="label">Vacantes Activas</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-info">
                <div class="icon-wrapper">
                    <i class="fas fa-users fa-lg"></i>
                </div>
                <div class="number"><?php echo $total_apps; ?></div>
                <div class="label">Total Aplicaciones</div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-warning">
                <div class="icon-wrapper">
                    <i class="fas fa-clock fa-lg"></i>
                </div>
                <div class="number"><?php echo $apps_pendientes; ?></div>
                <div class="label">Pendientes</div>
            </div>
        </div>
    </div>



    <div class="row g-4 mb-4">
        <!-- Acciones Rápidas -->
        <div class="col-md-6">
            <div class="data-table">
                <div class="card-header">
                    <h5><i class="fas fa-plus me-2"></i>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?page=vacantes-list&action=add" class="btn btn-primary d-flex align-items-center">
                            <i class="fas fa-briefcase me-2"></i>
                            Ver Vacantes
                        </a>
                        <a href="?page=direcciones-list" class="btn btn-outline-primary d-flex align-items-center">
                            <i class="fas fa-building me-2"></i>
                            Gestionar Direcciones
                        </a>
                        <a href="?page=aplicaciones-list" class="btn btn-outline-info d-flex align-items-center">
                            <i class="fas fa-users me-2"></i>
                            Ver Aplicaciones
                        </a>
                        <a href="?page=reportes" class="btn btn-outline-secondary d-flex align-items-center">
                            <i class="fas fa-chart-bar me-2"></i>
                            Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas por Dirección -->
        <div class="col-md-6">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-chart-bar me-2"></i>Estadísticas por Dirección</h5>
                    <small class="text-muted">1-3 de 3</small>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats_direcciones)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($stats_direcciones as $dir): ?>
                        <div class="list-group-item border-0 px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-semibold"><?php echo esc_html($dir->nombre); ?></h6>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning-subtle text-warning">
                                        <?php echo intval($dir->total_vacantes); ?> vacantes
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-building fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No hay estadísticas disponibles</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Vacantes Recientes -->
        <div class="col-md-6">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-briefcase me-2"></i>Vacantes Recientes</h5>
                    <a href="?page=vacantes-list" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($vacantes_recientes)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($vacantes_recientes as $vacante): ?>
                        <div class="list-group-item border-0 px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                            <i class="fas fa-briefcase text-success"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold"><?php echo esc_html($vacante->titulo); ?></h6>
                                        <p class="mb-1 small text-muted"><?php echo esc_html($vacante->direccion_nombre); ?></p>
                                        <small class="text-muted">
                                            <span class="badge bg-success">Activa</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info-subtle text-info fs-6 mb-1">
                                        <?php echo intval($vacante->total_aplicaciones); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">aplicaciones</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-briefcase fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No hay vacantes activas</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Aplicaciones Recientes -->
        <div class="col-md-6">
            <div class="data-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-users me-2"></i>Aplicaciones Recientes</h5>
                    <a href="?page=aplicaciones-list" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($aplicaciones_recientes)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($aplicaciones_recientes as $app): ?>
                        <div class="list-group-item border-0 px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold"><?php echo esc_html($app->nombre_completo); ?></h6>
                                        <p class="mb-1 small text-muted"><?php echo esc_html($app->vacante_titulo); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($app->fecha_aplicacion)); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $badge_class = array(
                                        'Pendiente' => 'warning',
                                        'Revisado' => 'info',
                                        'Aceptado' => 'success',
                                        'Rechazado' => 'danger'
                                    );
                                    $class = $badge_class[$app->estado] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>-subtle text-<?php echo $class; ?>">
                                        <?php echo esc_html($app->estado); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No hay aplicaciones recientes</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Los estilos se cargan desde modern-admin.css -->

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Efecto hover en tarjetas de estadísticas
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Actualizar números con animación (simulado)
    const numbers = document.querySelectorAll('.stats-number, .number');
    numbers.forEach(number => {
        const finalValue = parseInt(number.textContent);
        let currentValue = 0;
        const increment = finalValue / 30;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                number.textContent = finalValue;
                clearInterval(timer);
            } else {
                number.textContent = Math.floor(currentValue);
            }
        }, 50);
    });
});
</script>