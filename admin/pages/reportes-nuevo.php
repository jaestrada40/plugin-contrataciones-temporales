<?php
/**
 * Página de Reportería Avanzada - Versión Nueva
 * Replica exactamente la funcionalidad de Angular/.NET
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

global $wpdb;

// Obtener estadísticas generales
try {
    // Total direcciones
    $total_direcciones = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin");
    
    // Total vacantes
    $total_vacantes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin");
    $vacantes_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE estado = 'Activa' AND fecha_limite > NOW()");
    
    // Total aplicaciones
    $total_aplicaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin");
    $aplicaciones_aceptadas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE estado = 'Aceptada'");
    $aplicaciones_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE estado = 'Pendiente'");
    
    // Calcular métricas
    $tasa_conversion = $total_aplicaciones > 0 ? round(($aplicaciones_aceptadas / $total_aplicaciones) * 100, 2) : 0;
    $promedio_aplicaciones = $total_vacantes > 0 ? round($total_aplicaciones / $total_vacantes, 2) : 0;
    
    // Vacantes por vencer (próximos 7 días)
    $vacantes_por_vencer = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}vacantes_minfin 
        WHERE estado = 'Activa' 
        AND fecha_limite BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ");
    
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $total_direcciones = 0;
    $total_vacantes = 0;
    $vacantes_activas = 0;
    $total_aplicaciones = 0;
    $aplicaciones_aceptadas = 0;
    $aplicaciones_pendientes = 0;
    $tasa_conversion = 0;
    $promedio_aplicaciones = 0;
    $vacantes_por_vencer = 0;
}
?>

<div class="wrap reportes-moderno">
    <!-- Header -->
    <div class="reportes-header">
        <h1>Reportería Avanzada</h1>
        <p class="reportes-subtitle">Análisis detallado del sistema de vacantes</p>
    </div>

    <!-- Estadísticas Generales -->
    <div class="reportes-stats-grid">
        <div class="stat-card stat-blue">
            <div class="stat-icon">
                <i class="dashicons dashicons-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $tasa_conversion; ?>%</div>
                <div class="stat-label">Tasa de Conversión</div>
            </div>
        </div>
        
        <div class="stat-card stat-green">
            <div class="stat-icon">
                <i class="dashicons dashicons-groups"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $promedio_aplicaciones; ?></div>
                <div class="stat-label">Promedio Aplicaciones/Vacante</div>
            </div>
        </div>
        
        <div class="stat-card stat-orange">
            <div class="stat-icon">
                <i class="dashicons dashicons-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $vacantes_por_vencer; ?></div>
                <div class="stat-label">Vacantes por Vencer (7 días)</div>
            </div>
        </div>
        
        <div class="stat-card stat-cyan">
            <div class="stat-icon">
                <i class="dashicons dashicons-yes-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $aplicaciones_aceptadas; ?></div>
                <div class="stat-label">Aplicaciones Aceptadas</div>
            </div>
        </div>
    </div>

    <!-- Tabs de Reportes -->
    <div class="reportes-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" data-tab="efectividad">
                <i class="dashicons dashicons-chart-bar"></i>
                Efectividad por Dirección
            </button>
            <button class="tab-button" data-tab="tendencias">
                <i class="dashicons dashicons-chart-line"></i>
                Tendencias Mensuales
            </button>
            <button class="tab-button" data-tab="perfiles">
                <i class="dashicons dashicons-admin-users"></i>
                Perfiles de Candidatos
            </button>
            <button class="tab-button" data-tab="vacantes">
                <i class="dashicons dashicons-portfolio"></i>
                Reporte de Vacantes
            </button>
            <button class="tab-button" data-tab="aplicaciones">
                <i class="dashicons dashicons-media-document"></i>
                Reporte de Aplicaciones
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        
        <!-- Efectividad por Dirección -->
        <div class="tab-panel active" id="efectividad">
            <div class="section-header">
                <h2>Efectividad por Dirección</h2>
                <button class="export-excel-btn" onclick="exportarReporte('efectividad')">
                    <i class="dashicons dashicons-download"></i>
                    Exportar Excel
                </button>
            </div>
            
            <div class="table-container">
                <table class="reportes-table" id="tabla-efectividad">
                    <thead>
                        <tr>
                            <th>Dirección</th>
                            <th>Total Vacantes</th>
                            <th>Vacantes Activas</th>
                            <th>Total Aplicaciones</th>
                            <th>Aplicaciones Aceptadas</th>
                            <th>% Efectividad</th>
                            <th>Promedio Aplicaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="loading-cell">
                                <div class="loading-spinner"></div>
                                <span>Cargando datos...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tendencias Mensuales -->
        <div class="tab-panel" id="tendencias">
            <div class="section-header">
                <h2>Tendencias Mensuales</h2>
                <button class="export-excel-btn" onclick="exportarReporte('tendencias')">
                    <i class="dashicons dashicons-download"></i>
                    Exportar Excel
                </button>
            </div>
            
            <div class="table-container">
                <table class="reportes-table" id="tabla-tendencias">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Total Aplicaciones</th>
                            <th>Aceptadas</th>
                            <th>Rechazadas</th>
                            <th>Pendientes</th>
                            <th>Revisadas</th>
                            <th>% Aceptación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="loading-cell">
                                <div class="loading-spinner"></div>
                                <span>Cargando datos...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Perfiles de Candidatos -->
        <div class="tab-panel" id="perfiles">
            <div class="section-header">
                <h2>Perfiles de Candidatos</h2>
                <button class="export-excel-btn" onclick="exportarReporte('perfiles')">
                    <i class="dashicons dashicons-download"></i>
                    Exportar Excel
                </button>
            </div>
            
            <div class="table-container">
                <table class="reportes-table" id="tabla-perfiles">
                    <thead>
                        <tr>
                            <th>Nivel Educativo</th>
                            <th>Rango Experiencia</th>
                            <th>Total Candidatos</th>
                            <th>Aceptados</th>
                            <th>% Aceptación</th>
                            <th>Promedio Experiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="loading-cell">
                                <div class="loading-spinner"></div>
                                <span>Cargando datos...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reporte de Vacantes -->
        <div class="tab-panel" id="vacantes">
            <div class="section-header">
                <h2>Reporte de Vacantes</h2>
                <button class="export-excel-btn" onclick="exportarReporte('vacantes')">
                    <i class="dashicons dashicons-download"></i>
                    Exportar Excel
                </button>
            </div>
            
            <div class="table-container">
                <table class="reportes-table" id="tabla-vacantes">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Dirección</th>
                            <th>Tipo Contrato</th>
                            <th>Fecha Límite</th>
                            <th>Aplicaciones</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="loading-cell">
                                <div class="loading-spinner"></div>
                                <span>Cargando datos...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reporte de Aplicaciones -->
        <div class="tab-panel" id="aplicaciones">
            <div class="section-header">
                <h2>Reporte de Aplicaciones</h2>
                <div class="filters-and-export">
                    <select id="filtro-estado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Revisada">Revisada</option>
                        <option value="Aceptada">Aceptada</option>
                        <option value="Rechazada">Rechazada</option>
                    </select>
                    <select id="filtro-direccion" class="filter-select">
                        <option value="">Todas las direcciones</option>
                    </select>
                    <button class="export-excel-btn" onclick="exportarReporte('aplicaciones')">
                        <i class="dashicons dashicons-download"></i>
                        Excel
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="reportes-table" id="tabla-aplicaciones">
                    <thead>
                        <tr>
                            <th>Candidato</th>
                            <th>Contacto</th>
                            <th>Vacante</th>
                            <th>Perfil</th>
                            <th>Fecha Aplicación</th>
                            <th>Estado</th>
                            <th>Días en Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="loading-cell">
                                <div class="loading-spinner"></div>
                                <span>Cargando datos...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos principales para Reportería Avanzada */
.reportes-moderno {
    background: #f8f9fa;
    padding: 20px;
    margin: 0 -20px;
    min-height: 100vh;
}

/* Header */
.reportes-header {
    margin-bottom: 30px;
}

.reportes-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 5px 0;
}

.reportes-subtitle {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

/* Grid de estadísticas */
.reportes-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: none;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    flex-shrink: 0;
}

.stat-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1.2;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
    line-height: 1.3;
}

/* Colores de las tarjetas */
.stat-blue .stat-icon { background: #4285f4; }
.stat-green .stat-icon { background: #34a853; }
.stat-orange .stat-icon { background: #ff9800; }
.stat-cyan .stat-icon { background: #00bcd4; }

/* Tabs */
.reportes-tabs {
    margin-bottom: 20px;
}

.tab-buttons {
    display: flex;
    background: white;
    border-radius: 8px;
    padding: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-x: auto;
    gap: 4px;
}

.tab-button {
    background: transparent;
    border: none;
    padding: 12px 16px;
    border-radius: 6px;
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-button:hover {
    background: #f8f9fa;
    color: #495057;
}

.tab-button.active {
    background: #4285f4;
    color: white;
}

.tab-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Contenido de tabs */
.tab-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-panel {
    display: none;
    padding: 0;
}

.tab-panel.active {
    display: block;
}

/* Header de sección */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.section-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.export-excel-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s ease;
}

.export-excel-btn:hover {
    background: #218838;
}

.export-excel-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Contenedor de tabla */
.table-container {
    padding: 24px;
    overflow-x: auto;
}

/* Tabla de reportes */
.reportes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.reportes-table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    font-size: 13px;
    white-space: nowrap;
}

.reportes-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
    color: #495057;
    vertical-align: middle;
}

.reportes-table tbody tr:hover {
    background: #f8f9fa;
}

.reportes-table tbody tr:last-child td {
    border-bottom: none;
}

/* Loading */
.loading-cell {
    text-align: center;
    padding: 40px 16px !important;
    color: #6c757d;
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e9ecef;
    border-top: 2px solid #4285f4;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge.bg-success { background: #28a745 !important; color: white; }
.badge.bg-warning { background: #ffc107 !important; color: #212529; }
.badge.bg-danger { background: #dc3545 !important; color: white; }
.badge.bg-info { background: #17a2b8 !important; color: white; }
.badge.bg-primary { background: #007bff !important; color: white; }
.badge.bg-secondary { background: #6c757d !important; color: white; }

/* Progress bars */
.progress-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-bar-mini {
    width: 60px;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-fill.bg-success { background: #28a745; }
.progress-fill.bg-warning { background: #ffc107; }
.progress-fill.bg-danger { background: #dc3545; }

/* Responsive */
@media (max-width: 768px) {
    .reportes-moderno {
        padding: 15px;
        margin: 0 -15px;
    }
    
    .reportes-stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .tab-buttons {
        flex-direction: column;
        gap: 2px;
    }
    
    .section-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .table-container {
        padding: 16px;
    }
    
    .reportes-table {
        font-size: 13px;
    }
    
    .reportes-table th,
    .reportes-table td {
        padding: 8px 12px;
    }
}

/* Filtros y exportación */
.filters-and-export {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-select {
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    background: white;
    color: #495057;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: #4285f4;
    box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.2);
}

/* Estados de texto */
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }
.fw-bold { font-weight: 600 !important; }

/* Ajustes para datos específicos */
.candidate-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.candidate-info small {
    font-size: 12px;
    color: #6c757d;
}

.vacancy-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.vacancy-info .badge {
    align-self: flex-start;
    margin-bottom: 4px;
}

.vacancy-info small {
    font-size: 12px;
    line-height: 1.3;
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.profile-info small {
    font-size: 12px;
    color: #6c757d;
}

.applications-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.applications-info .badge {
    align-self: flex-start;
}

.applications-info small {
    font-size: 11px;
    display: flex;
    gap: 4px;
}
</style>

<script>
// Variables globales
let reportesData = {
    efectividad: [],
    tendencias: [],
    perfiles: [],
    vacantes: [],
    aplicaciones: []
};

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    cargarReporteEfectividad();
    cargarDirecciones();
    
    // Event listeners para tabs
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Actualizar botones activos
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Actualizar paneles activos
            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            
            // Cargar datos según el tab
            switch(tabId) {
                case 'tendencias':
                    cargarReporteTendencias();
                    break;
                case 'perfiles':
                    cargarReportePerfiles();
                    break;
                case 'vacantes':
                    cargarReporteVacantes();
                    break;
                case 'aplicaciones':
                    cargarReporteAplicaciones();
                    break;
            }
        });
    });
    
    // Event listeners para filtros
    setTimeout(() => {
        const filtroEstado = document.getElementById('filtro-estado');
        const filtroDireccion = document.getElementById('filtro-direccion');
        
        if (filtroEstado) {
            filtroEstado.addEventListener('change', cargarReporteAplicaciones);
        }
        
        if (filtroDireccion) {
            filtroDireccion.addEventListener('change', cargarReporteAplicaciones);
        }
    }, 100);
});

// Función para cargar reporte de efectividad
function cargarReporteEfectividad() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_reporte_efectividad',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportesData.efectividad = data.data;
            renderTablaEfectividad(data.data);
        } else {
            console.error('Error:', data.data);
            mostrarError('tabla-efectividad', 'Error al cargar datos de efectividad');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('tabla-efectividad', 'Error de conexión');
    });
}

// Función para renderizar tabla de efectividad
function renderTablaEfectividad(datos) {
    const tbody = document.querySelector('#tabla-efectividad tbody');
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = datos.map(item => `
        <tr>
            <td><strong>${item.direccion}</strong></td>
            <td>${item.total_vacantes}</td>
            <td><span class="badge bg-success">${item.vacantes_activas}</span></td>
            <td>${item.total_aplicaciones}</td>
            <td>${item.aplicaciones_aceptadas}</td>
            <td>
                <div class="progress-container">
                    <div class="progress-bar-mini">
                        <div class="progress-fill ${getProgressClass(item.porcentaje_efectividad)}" 
                             style="width: ${item.porcentaje_efectividad}%"></div>
                    </div>
                    <span class="fw-bold">${item.porcentaje_efectividad}%</span>
                </div>
            </td>
            <td>${item.promedio_aplicaciones}</td>
        </tr>
    `).join('');
}

// Función para obtener clase de progreso
function getProgressClass(percentage) {
    if (percentage >= 70) return 'bg-success';
    if (percentage >= 40) return 'bg-warning';
    return 'bg-danger';
}

// Función para mostrar errores
function mostrarError(tablaId, mensaje) {
    const tbody = document.querySelector(`#${tablaId} tbody`);
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${mensaje}</td></tr>`;
}

// Función para exportar reportes
function exportarReporte(tipo) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo admin_url('admin-ajax.php'); ?>';
    form.target = '_blank';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'exportar_reporte_nuevo';
    
    const tipoInput = document.createElement('input');
    tipoInput.type = 'hidden';
    tipoInput.name = 'tipo_reporte';
    tipoInput.value = tipo;
    
    const nonceInput = document.createElement('input');
    nonceInput.type = 'hidden';
    nonceInput.name = 'nonce';
    nonceInput.value = '<?php echo wp_create_nonce('exportar_reporte'); ?>';
    
    form.appendChild(actionInput);
    form.appendChild(tipoInput);
    form.appendChild(nonceInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Función para cargar reporte de tendencias
function cargarReporteTendencias() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_reporte_tendencias',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportesData.tendencias = data.data;
            renderTablaTendencias(data.data);
        } else {
            mostrarError('tabla-tendencias', 'Error al cargar tendencias');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('tabla-tendencias', 'Error de conexión');
    });
}

// Función para cargar reporte de perfiles
function cargarReportePerfiles() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_reporte_perfiles',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportesData.perfiles = data.data;
            renderTablaPerfiles(data.data);
        } else {
            mostrarError('tabla-perfiles', 'Error al cargar perfiles');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('tabla-perfiles', 'Error de conexión');
    });
}

// Función para cargar reporte de vacantes
function cargarReporteVacantes() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_reporte_vacantes',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportesData.vacantes = data.data;
            renderTablaVacantes(data.data);
        } else {
            mostrarError('tabla-vacantes', 'Error al cargar vacantes');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('tabla-vacantes', 'Error de conexión');
    });
}

// Función para cargar reporte de aplicaciones
function cargarReporteAplicaciones() {
    const estado = document.getElementById('filtro-estado').value;
    const direccionId = document.getElementById('filtro-direccion').value;
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_reporte_aplicaciones',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>',
            estado: estado,
            direccion_id: direccionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            reportesData.aplicaciones = data.data;
            renderTablaAplicaciones(data.data);
        } else {
            mostrarError('tabla-aplicaciones', 'Error al cargar aplicaciones');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('tabla-aplicaciones', 'Error de conexión');
    });
}

// Función para cargar direcciones
function cargarDirecciones() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_direcciones',
            nonce: '<?php echo wp_create_nonce('reportes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('filtro-direccion');
            data.data.forEach(direccion => {
                const option = document.createElement('option');
                option.value = direccion.id;
                option.textContent = direccion.nombre;
                select.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error cargando direcciones:', error);
    });
}

// Funciones para renderizar tablas
function renderTablaTendencias(datos) {
    const tbody = document.querySelector('#tabla-tendencias tbody');
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = datos.map(item => `
        <tr>
            <td><strong>${item.mes_nombre} ${item.anio}</strong></td>
            <td>${item.total_aplicaciones}</td>
            <td><span class="badge bg-success">${item.aceptadas}</span></td>
            <td><span class="badge bg-danger">${item.rechazadas}</span></td>
            <td><span class="badge bg-warning">${item.pendientes}</span></td>
            <td><span class="badge bg-info">${item.revisadas}</span></td>
            <td>
                <span class="fw-bold ${item.porcentaje_aceptacion >= 50 ? 'text-success' : 'text-warning'}">
                    ${item.porcentaje_aceptacion}%
                </span>
            </td>
        </tr>
    `).join('');
}

function renderTablaPerfiles(datos) {
    const tbody = document.querySelector('#tabla-perfiles tbody');
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = datos.map(item => `
        <tr>
            <td><strong>${item.nivel_educativo}</strong></td>
            <td>${item.rango_experiencia}</td>
            <td>${item.total_candidatos}</td>
            <td>${item.aceptados}</td>
            <td>
                <span class="fw-bold ${item.porcentaje_aceptacion >= 30 ? 'text-success' : 'text-warning'}">
                    ${item.porcentaje_aceptacion}%
                </span>
            </td>
            <td>${item.promedio_experiencia} años</td>
        </tr>
    `).join('');
}

function renderTablaVacantes(datos) {
    const tbody = document.querySelector('#tabla-vacantes tbody');
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = datos.map(item => `
        <tr>
            <td><span class="badge bg-secondary">${item.codigo}</span></td>
            <td><strong>${item.titulo}</strong></td>
            <td>${item.direccion}</td>
            <td>${item.tipo_contrato || 'No especificado'}</td>
            <td>
                <span class="${item.dias_por_vencer <= 7 && item.dias_por_vencer > 0 ? 'text-danger fw-bold' : ''}">
                    ${formatDate(item.fecha_limite)}
                </span>
                ${item.dias_por_vencer > 0 ? `<div><small class="text-muted">${item.dias_por_vencer} días restantes</small></div>` : ''}
            </td>
            <td>
                <div class="applications-info">
                    <span class="badge bg-primary">${item.total_aplicaciones} total</span>
                    <small>
                        <span class="badge bg-success">${item.aplicaciones_aceptadas}</span>
                        <span class="badge bg-warning">${item.aplicaciones_pendientes}</span>
                    </small>
                </div>
            </td>
            <td>
                <span class="${getEstadoBadgeClass(item.estado_calculado)}">${item.estado_calculado}</span>
            </td>
        </tr>
    `).join('');
}

function renderTablaAplicaciones(datos) {
    const tbody = document.querySelector('#tabla-aplicaciones tbody');
    
    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = datos.map(item => `
        <tr>
            <td><strong>${item.nombre_completo}</strong></td>
            <td>
                <div class="candidate-info">
                    <small>${item.email}</small>
                    <small>${item.telefono}</small>
                </div>
            </td>
            <td>
                <div class="vacancy-info">
                    <span class="badge bg-secondary">${item.vacante_codigo}</span>
                    <small>${item.vacante_titulo}</small>
                    <small class="text-muted">${item.direccion}</small>
                </div>
            </td>
            <td>
                <div class="profile-info">
                    <small>${item.nivel_educativo || 'No especificado'}</small>
                    <small>${item.profesion || 'No especificado'}</small>
                    <small>${item.experiencia_laboral || 0} años exp.</small>
                </div>
            </td>
            <td>${formatDate(item.fecha_aplicacion)}</td>
            <td>
                <span class="${getEstadoBadgeClass(item.estado)}">${item.estado}</span>
            </td>
            <td>
                <span class="${item.dias_en_estado > 30 ? 'text-danger fw-bold' : ''}">
                    ${item.dias_en_estado} días
                </span>
            </td>
        </tr>
    `).join('');
}

// Funciones auxiliares
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getEstadoBadgeClass(estado) {
    switch (estado) {
        case 'Activa': return 'badge bg-success';
        case 'Vencida': return 'badge bg-danger';
        case 'Inactiva': return 'badge bg-secondary';
        case 'Pendiente': return 'badge bg-warning';
        case 'Revisada': return 'badge bg-info';
        case 'Aceptada': return 'badge bg-success';
        case 'Rechazada': return 'badge bg-danger';
        default: return 'badge bg-secondary';
    }
}
</script>