<?php
/**
 * Dashboard - Vacantes MINFIN (Igual al de Angular/.NET)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

global $wpdb;

// Obtener estadísticas
$stats = array(
    'direcciones' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_direcciones"),
    'vacantes_activas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_vacantes WHERE estado = 'Activa'"),
    'total_aplicaciones' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_aplicaciones"),
    'aplicaciones_pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_aplicaciones WHERE estado = 'Pendiente'")
);

// Obtener vacantes recientes
$vacantes_recientes = $wpdb->get_results("
    SELECT v.*, d.nombre as direccion_nombre, 
           (SELECT COUNT(*) FROM {$wpdb->prefix}vs_aplicaciones a WHERE a.vacante_id = v.id) as total_aplicaciones
    FROM {$wpdb->prefix}vs_vacantes v
    LEFT JOIN {$wpdb->prefix}vs_direcciones d ON v.direccion_id = d.id
    ORDER BY v.fecha_creacion DESC
    LIMIT 3
");

// Obtener aplicaciones recientes
$aplicaciones_recientes = $wpdb->get_results("
    SELECT a.*, v.codigo as vacante_codigo, v.titulo as vacante_titulo
    FROM {$wpdb->prefix}vs_aplicaciones a
    LEFT JOIN {$wpdb->prefix}vs_vacantes v ON a.vacante_id = v.id
    ORDER BY a.fecha_aplicacion DESC
    LIMIT 3
");

// Obtener estadísticas por dirección
$stats_direcciones = $wpdb->get_results("
    SELECT d.nombre, COUNT(v.id) as total_vacantes
    FROM {$wpdb->prefix}vs_direcciones d
    LEFT JOIN {$wpdb->prefix}vs_vacantes v ON d.id = v.direccion_id
    GROUP BY d.id, d.nombre
    ORDER BY total_vacantes DESC
    LIMIT 3
");
?>

<div class="wrap dashboard-container" style="margin-right: 0; padding-right: 20px;">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p class="dashboard-subtitle">Resumen del sistema de vacantes</p>
    </div>
    
    <!-- Tarjetas de estadísticas principales -->
    <div class="dashboard-stats-grid">
        <div class="stat-card stat-blue">
            <div class="stat-content">
                <div class="stat-label">Direcciones</div>
                <div class="stat-number"><?php echo $stats['direcciones']; ?></div>
            </div>
            <div class="stat-icon">
                <span class="dashicons dashicons-building"></span>
            </div>
        </div>
        
        <div class="stat-card stat-green">
            <div class="stat-content">
                <div class="stat-label">Vacantes Activas</div>
                <div class="stat-number"><?php echo $stats['vacantes_activas']; ?></div>
            </div>
            <div class="stat-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
        </div>
        
        <div class="stat-card stat-cyan">
            <div class="stat-content">
                <div class="stat-label">Total Aplicaciones</div>
                <div class="stat-number"><?php echo $stats['total_aplicaciones']; ?></div>
            </div>
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
        </div>
        
        <div class="stat-card stat-orange">
            <div class="stat-content">
                <div class="stat-label">Pendientes</div>
                <div class="stat-number"><?php echo $stats['aplicaciones_pendientes']; ?></div>
            </div>
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
        </div>
    </div></div>
    
    <div class="dashboard-content">
        <!-- Acciones Rápidas -->
        <div class="dashboard-section">
            <div class="section-header">
                <span class="dashicons dashicons-plus-alt"></span>
                <h3>Acciones Rápidas</h3>
            </div>
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=vacantes-list'); ?>" class="quick-action">
                    <span class="dashicons dashicons-portfolio"></span>
                    Ver Vacantes
                </a>
                <a href="<?php echo admin_url('admin.php?page=direcciones-list'); ?>" class="quick-action">
                    <span class="dashicons dashicons-building"></span>
                    Gestionar Direcciones
                </a>
                <a href="<?php echo admin_url('admin.php?page=aplicaciones-list'); ?>" class="quick-action">
                    <span class="dashicons dashicons-groups"></span>
                    Ver Aplicaciones
                </a>
                <a href="<?php echo admin_url('admin.php?page=reportes'); ?>" class="quick-action">
                    <span class="dashicons dashicons-chart-bar"></span>
                    Ver Reportes
                </a>
            </div>
        </div>
        
        <!-- Estadísticas por Dirección -->
        <div class="dashboard-section">
            <div class="section-header">
                <span class="dashicons dashicons-chart-bar"></span>
                <h3>Estadísticas por Dirección</h3>
                <span class="section-count">1-3 de <?php echo count($stats_direcciones); ?></span>
            </div>
            <div class="stats-list">
                <?php foreach ($stats_direcciones as $index => $stat): ?>
                    <div class="stats-item">
                        <div class="stats-info">
                            <div class="stats-name"><?php echo esc_html($stat->nombre); ?></div>
                            <div class="stats-count"><?php echo $stat->total_vacantes; ?> vacantes</div>
                        </div>
                        <div class="stats-bar">
                            <div class="stats-progress stats-progress-<?php echo $index + 1; ?>" style="width: <?php echo min(($stat->total_vacantes / max(1, $stats['vacantes_activas'])) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="dashboard-bottom">
        <!-- Vacantes Recientes -->
        <div class="dashboard-section">
            <div class="section-header">
                <span class="dashicons dashicons-portfolio"></span>
                <h3>Vacantes Recientes</h3>
                <span class="section-count">1-3 de <?php echo count($vacantes_recientes); ?></span>
                <a href="<?php echo admin_url('admin.php?page=vacantes-list'); ?>" class="section-link">Ver todas</a>
            </div>
            <div class="recent-items">
                <?php foreach ($vacantes_recientes as $vacante): ?>
                    <div class="recent-item">
                        <div class="item-header">
                            <div class="item-title"><?php echo esc_html($vacante->titulo); ?></div>
                            <div class="item-count"><?php echo $vacante->total_aplicaciones; ?> aplicaciones</div>
                        </div>
                        <div class="item-subtitle"><?php echo esc_html($vacante->direccion_nombre); ?></div>
                        <div class="item-meta">
                            <span class="item-badge badge-code">
                                <?php echo esc_html($vacante->codigo); ?>
                            </span>
                            <span class="item-badge badge-<?php echo $vacante->estado === 'Activa' ? 'active' : 'inactive'; ?>">
                                <?php echo esc_html($vacante->estado); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Aplicaciones Recientes -->
        <div class="dashboard-section">
            <div class="section-header">
                <span class="dashicons dashicons-groups"></span>
                <h3>Aplicaciones Recientes</h3>
                <span class="section-count">1-3 de <?php echo count($aplicaciones_recientes); ?></span>
                <a href="<?php echo admin_url('admin.php?page=aplicaciones-list'); ?>" class="section-link">Ver todas</a>
            </div>
            <div class="recent-items">
                <?php foreach ($aplicaciones_recientes as $aplicacion): ?>
                    <div class="recent-item">
                        <div class="item-header">
                            <div class="item-title"><?php echo esc_html($aplicacion->candidato); ?></div>
                            <div class="item-date"><?php echo human_time_diff(strtotime($aplicacion->fecha_aplicacion), current_time('timestamp')); ?></div>
                        </div>
                        <div class="item-subtitle"><?php echo esc_html($aplicacion->vacante_titulo); ?></div>
                        <div class="item-meta">
                            <span class="item-badge badge-code">
                                <?php echo esc_html($aplicacion->vacante_codigo); ?>
                            </span>
                            <span class="item-badge badge-<?php echo strtolower($aplicacion->estado); ?>">
                                <?php echo esc_html($aplicacion->estado); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    width: 100%;
    margin: 0;
    padding: 0;
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 5px 0;
}

.dashboard-subtitle {
    color: #64748b;
    margin: 0;
    font-size: 14px;
}

.dashboard-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

/* En pantallas medianas, usar 2 columnas */
@media (max-width: 1200px) {
    .dashboard-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.stat-card {
    border-radius: 8px;
    padding: 20px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    min-height: 80px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-blue { background: #4285f4; }
.stat-green { background: #34a853; }
.stat-cyan { background: #1ba1e2; }
.stat-orange { background: #ff9800; }

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: white;
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: rgba(255,255,255,0.9);
    font-size: 13px;
    margin: 0;
    font-weight: 400;
}

.dashboard-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.dashboard-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.dashboard-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.section-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    flex: 1;
}

.section-count {
    font-size: 12px;
    color: #64748b;
}

.section-link {
    font-size: 12px;
    color: #3b82f6;
    text-decoration: none;
}

.section-link:hover {
    color: #2563eb;
}

.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    color: #475569;
    font-size: 14px;
    transition: all 0.2s;
}

.quick-action:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stats-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.stats-item:last-child {
    border-bottom: none;
}

.stats-info {
    flex: 1;
}

.stats-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
    margin-bottom: 2px;
}

.stats-count {
    font-size: 12px;
    color: #64748b;
}

.stats-bar {
    width: 120px;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
    margin-left: 16px;
}

.stats-progress {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.stats-progress-1 { background: #ff9800; }
.stats-progress-2 { background: #ff9800; }
.stats-progress-3 { background: #ff9800; }

.recent-items {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.recent-item {
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.recent-item:last-child {
    border-bottom: none;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 4px;
}

.item-title {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
    flex: 1;
}

.item-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}

.item-meta {
    display: flex;
    gap: 8px;
}

.item-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.badge-code { 
    background: #64748b; 
    color: white; 
}

.badge-active { 
    background: #10b981; 
    color: white; 
}

.badge-inactive { 
    background: #ef4444; 
    color: white; 
}

.badge-pendiente { 
    background: #f59e0b; 
    color: white; 
}

.badge-aceptada { 
    background: #10b981; 
    color: white; 
}

.badge-rechazada { 
    background: #ef4444; 
    color: white; 
}

.item-count, .item-date {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
}

/* Hacer que el dashboard use todo el ancho */
.wrap.dashboard-container {
    max-width: none !important;
    width: calc(100vw - 180px) !important; /* Ancho completo menos sidebar */
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* En pantallas grandes, ajustar para sidebar colapsado */
@media (min-width: 961px) {
    .folded .wrap.dashboard-container {
        width: calc(100vw - 36px) !important;
    }
}

/* Responsive para móviles */
@media (max-width: 960px) {
    .wrap.dashboard-container {
        width: 100% !important;
    }
}

@media (max-width: 768px) {
    .dashboard-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-content,
    .dashboard-bottom {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .wrap.dashboard-container {
        width: 100% !important;
        padding: 10px !important;
    }
}
</style>