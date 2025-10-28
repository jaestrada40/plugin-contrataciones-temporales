<?php
/**
 * Página de Gestión de Aplicaciones - Diseño Bootstrap (Angular Style)
 * Replica exactamente el diseño de Angular/.NET
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

global $wpdb;

// Obtener vacantes para filtros
try {
    $vacantes = $wpdb->get_results("SELECT id, codigo, titulo FROM {$wpdb->prefix}vacantes_minfin ORDER BY codigo");
} catch (Exception $e) {
    $vacantes = array();
}
?>

<div class="wrap" style="margin: 0; padding: 0;">
    <div class="container-fluid py-4" style="max-width: none; padding-left: 20px; padding-right: 20px;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Gestión de Aplicaciones</h1>
                <p class="text-muted mb-0">Administra las aplicaciones recibidas</p>
            </div>
            <div>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="
                    console.log('🧪 Test AJAX Inline');
                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'cargar_aplicaciones_admin',
                        nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>'
                    }).done(function(r) {
                        console.log('✅ Respuesta:', r);
                        alert('AJAX OK: ' + (r.success ? r.data.length + ' aplicaciones' : 'Error'));
                        if (r.success) {
                            allAplicaciones = r.data || [];
                            updateStats();
                            applyFilters();
                        }
                    }).fail(function(x,s,e) {
                        console.error('❌ Error:', e);
                        alert('Error AJAX: ' + e);
                    });
                ">
                    <i class="fas fa-bolt me-1"></i>Test AJAX
                </button>
                <button class="btn btn-outline-success btn-sm me-2" onclick="
                    console.log('📊 Cargando datos inline');
                    allAplicaciones = [
                        {id:1, nombres:'Juan', apellidos:'García', email:'juan@test.com', telefono:'12345678', estado:'Pendiente', vacante_codigo:'TEST-001', vacante_titulo:'Desarrollador', fecha_aplicacion:'2024-10-23', profesion:'Ingeniero', experiencia_laboral:3},
                        {id:2, nombres:'María', apellidos:'Rodríguez', email:'maria@test.com', telefono:'87654321', estado:'Revisada', vacante_codigo:'TEST-002', vacante_titulo:'Analista', fecha_aplicacion:'2024-10-22', profesion:'Contadora', experiencia_laboral:5}
                    ];
                    updateStats();
                    applyFilters();
                    alert('✅ Cargados 2 datos de prueba');
                ">
                    <i class="fas fa-sync me-1"></i>Datos Prueba
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="alert('jQuery: ' + (typeof jQuery !== 'undefined') + '\\nBootstrap: ' + (typeof bootstrap !== 'undefined') + '\\nAplicaciones: ' + allAplicaciones.length)">
                    <i class="fas fa-info me-1"></i>Info Sistema
                </button>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="card mb-4" id="filters-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Buscar aplicaciones</label>
                        <input type="text" class="form-control" id="search-input" 
                               placeholder="Buscar por nombre, email, teléfono, vacante...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filtrar por estado</label>
                        <select class="form-select" id="filter-estado">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Revisada">Revisada</option>
                            <option value="Aceptada">Aceptada</option>
                            <option value="Rechazada">Rechazada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Filtrar por vacante</label>
                        <select class="form-select" id="filter-vacante">
                            <option value="">Todas las vacantes</option>
                            <?php foreach ($vacantes as $vacante): ?>
                            <option value="<?php echo $vacante->id; ?>">
                                <?php echo esc_html($vacante->codigo . ' - ' . $vacante->titulo); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-outline-secondary w-100 me-2" onclick="clearFilters()">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </button>
                        <button class="btn btn-outline-success w-100" onclick="exportarAplicaciones()">
                            <i class="fas fa-download me-2"></i>Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1" id="stat-pendientes">0</h4>
                        <small>Pendientes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1" id="stat-revisadas">0</h4>
                        <small>Revisadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1" id="stat-aceptadas">0</h4>
                        <small>Aceptadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1" id="stat-rechazadas">0</h4>
                        <small>Rechazadas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading-spinner" class="loading-spinner text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando aplicaciones...</p>
        </div>

        <!-- Aplicaciones Table -->
        <div class="card" id="aplicaciones-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="aplicaciones-table">
                        <thead class="table-light">
                            <tr>
                                <th>Candidato</th>
                                <th>Vacante</th>
                                <th>Contacto</th>
                                <th>Experiencia</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="aplicaciones-tbody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando datos...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="empty-state" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted" id="empty-message">No se encontraron aplicaciones</h5>
                    <button class="btn btn-outline-primary mt-2" onclick="clearFilters()" id="clear-filters-btn" style="display: none;">
                        <i class="fas fa-times me-2"></i>Limpiar Filtros
                    </button>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-between align-items-center mt-3" id="pagination-container" style="display: none;">
                    <div>
                        <span class="text-muted" id="pagination-info-text">Mostrando 0 aplicaciones</span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination-controls">
                            <!-- Se genera dinámicamente -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div><!-
- Modal para Ver Detalle de Aplicación -->
<div class="modal fade" id="aplicacionModal" tabindex="-1" aria-labelledby="aplicacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aplicacionModalLabel">Detalle de Aplicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Contenido se genera dinámicamente -->
            </div>
            <div class="modal-footer">
                <div class="btn-group me-auto" role="group">
                    <button type="button" class="btn btn-warning btn-sm" onclick="updateEstado('Pendiente')">
                        <i class="fas fa-clock me-1"></i>Pendiente
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="updateEstado('Revisada')">
                        <i class="fas fa-eye me-1"></i>Revisada
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="updateEstado('Aceptada')">
                        <i class="fas fa-check me-1"></i>Aceptada
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="updateEstado('Rechazada')">
                        <i class="fas fa-times me-1"></i>Rechazada
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let allAplicaciones = [];
let filteredAplicaciones = [];
let currentPage = 0;
let itemsPerPage = 10;
let totalPages = 0;
let selectedAplicacion = null;
let aplicacionModal = null;

// Filtros
let searchTerm = '';
let filterEstado = '';
let filterVacante = '';

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de aplicaciones Bootstrap...');
    console.log('🌐 Bootstrap disponible:', typeof bootstrap !== 'undefined');
    console.log('📚 jQuery disponible:', typeof jQuery !== 'undefined');
    console.log('📍 URL actual:', window.location.href);
    console.log('🔧 Admin AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
    console.log('🔑 Nonce:', '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>');
    
    // Verificar si Bootstrap está disponible
    if (typeof bootstrap !== 'undefined') {
        // Inicializar modal de Bootstrap
        aplicacionModal = new bootstrap.Modal(document.getElementById('aplicacionModal'));
        console.log('✅ Modal de Bootstrap inicializado');
    } else {
        console.warn('⚠️ Bootstrap no está disponible');
    }
    
    // Cargar datos iniciales
    console.log('📊 Iniciando carga de aplicaciones...');
    loadAplicaciones();
    
    // Event listeners para filtros
    document.getElementById('search-input').addEventListener('input', debounce(onSearchChange, 300));
    document.getElementById('filter-estado').addEventListener('change', onFilterChange);
    document.getElementById('filter-vacante').addEventListener('change', onFilterChange);
    
    console.log('✅ Event listeners configurados');
});

// Función para cargar aplicaciones - CON TIMEOUT
function loadAplicaciones() {
    console.log('🚀 Cargando aplicaciones...');
    showLoading(true);
    
    // Timeout de 15 segundos para evitar carga infinita
    const loadTimeout = setTimeout(function() {
        console.error('⏰ TIMEOUT: Carga tardó más de 15 segundos');
        showError('La carga está tardando demasiado. Usa el botón "Test AJAX" para diagnosticar.');
        showLoading(false);
    }, 15000);
    
    // Usar jQuery que es más confiable en WordPress
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'cargar_aplicaciones_admin',
        nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>'
    })
    .done(function(response) {
        clearTimeout(loadTimeout);
        console.log('✅ Respuesta recibida:', response);
        
        if (response.success) {
            allAplicaciones = response.data || [];
            console.log('📊 Aplicaciones cargadas:', allAplicaciones.length);
            
            // Actualizar interfaz
            updateStats();
            applyFilters();
            
            if (allAplicaciones.length === 0) {
                showError('No hay aplicaciones registradas');
            }
        } else {
            console.error('❌ Error del servidor:', response.data);
            showError('Error: ' + (response.data || 'Error desconocido'));
        }
    })
    .fail(function(xhr, status, error) {
        clearTimeout(loadTimeout);
        console.error('💥 Error AJAX:', status, error);
        console.error('📄 Respuesta completa:', xhr.responseText);
        showError('Error de conexión: ' + error);
    })
    .always(function() {
        console.log('🏁 Finalizando carga');
        showLoading(false);
    });
}

// Función para actualizar estadísticas
function updateStats() {
    const stats = {
        pendientes: allAplicaciones.filter(a => a.estado === 'Pendiente').length,
        revisadas: allAplicaciones.filter(a => a.estado === 'Revisada').length,
        aceptadas: allAplicaciones.filter(a => a.estado === 'Aceptada').length,
        rechazadas: allAplicaciones.filter(a => a.estado === 'Rechazada').length
    };
    
    document.getElementById('stat-pendientes').textContent = stats.pendientes;
    document.getElementById('stat-revisadas').textContent = stats.revisadas;
    document.getElementById('stat-aceptadas').textContent = stats.aceptadas;
    document.getElementById('stat-rechazadas').textContent = stats.rechazadas;
}

// Función para aplicar filtros
function applyFilters() {
    filteredAplicaciones = allAplicaciones.filter(aplicacion => {
        // Filtro de búsqueda
        if (searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            const matchesSearch = 
                (aplicacion.nombres + ' ' + aplicacion.apellidos).toLowerCase().includes(searchLower) ||
                aplicacion.email.toLowerCase().includes(searchLower) ||
                aplicacion.telefono.includes(searchTerm) ||
                (aplicacion.vacante_titulo && aplicacion.vacante_titulo.toLowerCase().includes(searchLower)) ||
                (aplicacion.vacante_codigo && aplicacion.vacante_codigo.toLowerCase().includes(searchLower));
            
            if (!matchesSearch) return false;
        }
        
        // Filtro de estado
        if (filterEstado && aplicacion.estado !== filterEstado) {
            return false;
        }
        
        // Filtro de vacante
        if (filterVacante && aplicacion.vacante_id != filterVacante) {
            return false;
        }
        
        return true;
    });
    
    // Resetear página actual
    currentPage = 0;
    
    // Actualizar vista
    updateTable();
    updatePagination();
}

// Función para actualizar la tabla
function updateTable() {
    const tbody = document.getElementById('aplicaciones-tbody');
    const emptyState = document.getElementById('empty-state');
    const tableContainer = document.querySelector('.table-responsive');
    
    if (filteredAplicaciones.length === 0) {
        // Mostrar estado vacío
        tableContainer.style.display = 'none';
        emptyState.style.display = 'block';
        
        // Actualizar mensaje
        const emptyMessage = document.getElementById('empty-message');
        const clearFiltersBtn = document.getElementById('clear-filters-btn');
        
        if (allAplicaciones.length === 0) {
            emptyMessage.textContent = 'No hay aplicaciones registradas';
            clearFiltersBtn.style.display = 'none';
        } else {
            emptyMessage.textContent = 'No se encontraron aplicaciones con los filtros aplicados';
            clearFiltersBtn.style.display = 'inline-block';
        }
        
        return;
    }
    
    // Mostrar tabla
    tableContainer.style.display = 'block';
    emptyState.style.display = 'none';
    
    // Calcular elementos de la página actual
    const startIndex = currentPage * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredAplicaciones.length);
    const pageAplicaciones = filteredAplicaciones.slice(startIndex, endIndex);
    
    // Generar HTML de la tabla
    tbody.innerHTML = pageAplicaciones.map(aplicacion => `
        <tr>
            <td>
                <div>
                    <strong>${aplicacion.nombres || ''} ${aplicacion.apellidos || ''}</strong>
                    <br><small class="text-muted">DPI: ${aplicacion.dpi || 'N/A'}</small>
                    ${aplicacion.profesion ? `<br><small class="text-muted">${aplicacion.profesion}</small>` : ''}
                </div>
            </td>
            <td>
                <div>
                    <span class="badge bg-secondary">${aplicacion.vacante_codigo || 'N/A'}</span>
                    <br><strong>${aplicacion.vacante_titulo || 'Sin título'}</strong>
                </div>
            </td>
            <td>
                <div>
                    <small><i class="fas fa-envelope me-1"></i>${aplicacion.email || 'N/A'}</small>
                    <br><small><i class="fas fa-phone me-1"></i>${aplicacion.telefono || 'N/A'}</small>
                </div>
            </td>
            <td>
                <div>
                    <span class="badge bg-info">${aplicacion.experiencia_laboral || 0} años</span>
                    ${aplicacion.nivel_educativo ? `<br><small class="text-muted">${aplicacion.nivel_educativo}</small>` : ''}
                </div>
            </td>
            <td>
                <small>${formatDate(aplicacion.fecha_aplicacion)}</small>
            </td>
            <td>
                <span class="badge ${getEstadoBadgeClass(aplicacion.estado)}">${aplicacion.estado || 'Pendiente'}</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewAplicacion(${aplicacion.id})" title="Ver Detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="cambiarEstado(${aplicacion.id})" title="Cambiar Estado">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}// E
vent handlers
function onSearchChange() {
    searchTerm = document.getElementById('search-input').value;
    applyFilters();
}

function onFilterChange() {
    filterEstado = document.getElementById('filter-estado').value;
    filterVacante = document.getElementById('filter-vacante').value;
    applyFilters();
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-estado').value = '';
    document.getElementById('filter-vacante').value = '';
    
    searchTerm = '';
    filterEstado = '';
    filterVacante = '';
    
    applyFilters();
}

// Función para ver detalle de aplicación
function viewAplicacion(id) {
    selectedAplicacion = allAplicaciones.find(a => a.id == id);
    if (!selectedAplicacion) {
        console.error('Aplicación no encontrada:', id);
        return;
    }
    
    const modalTitle = document.getElementById('aplicacionModalLabel');
    const modalBody = document.getElementById('modal-body');
    
    modalTitle.textContent = `Aplicación de ${selectedAplicacion.nombres} ${selectedAplicacion.apellidos}`;
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Información Personal</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Nombre Completo:</strong><br>
                            ${selectedAplicacion.nombres} ${selectedAplicacion.apellidos}
                        </div>
                        <div class="mb-2">
                            <strong>DPI:</strong><br>
                            ${selectedAplicacion.dpi}
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong><br>
                            ${selectedAplicacion.email}
                        </div>
                        <div class="mb-2">
                            <strong>Teléfono:</strong><br>
                            ${selectedAplicacion.telefono}
                        </div>
                        ${selectedAplicacion.profesion ? `
                        <div class="mb-2">
                            <strong>Profesión:</strong><br>
                            ${selectedAplicacion.profesion}
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Información de la Vacante</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Código:</strong><br>
                            <span class="badge bg-secondary">${selectedAplicacion.vacante_codigo}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Título:</strong><br>
                            ${selectedAplicacion.vacante_titulo}
                        </div>
                        <div class="mb-2">
                            <strong>Estado Actual:</strong><br>
                            <span class="badge ${getEstadoBadgeClass(selectedAplicacion.estado)}">${selectedAplicacion.estado}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Fecha de Aplicación:</strong><br>
                            ${formatDate(selectedAplicacion.fecha_aplicacion)}
                        </div>
                        ${selectedAplicacion.nivel_educativo ? `
                        <div class="mb-2">
                            <strong>Nivel Educativo:</strong><br>
                            ${selectedAplicacion.nivel_educativo}
                        </div>
                        ` : ''}
                        <div class="mb-2">
                            <strong>Experiencia Laboral:</strong><br>
                            ${selectedAplicacion.experiencia_laboral || 0} años
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ${selectedAplicacion.comentarios ? `
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Comentarios</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${selectedAplicacion.comentarios}</p>
                    </div>
                </div>
            </div>
        </div>
        ` : ''}
        ${selectedAplicacion.cv_file ? `
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Archivos Adjuntos</h6>
                    </div>
                    <div class="card-body">
                        <a href="${selectedAplicacion.cv_file}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>Ver CV
                        </a>
                    </div>
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    aplicacionModal.show();
}

// Función para actualizar estado desde el modal
function updateEstado(nuevoEstado) {
    if (!selectedAplicacion) return;
    
    updateEstadoDirecto(selectedAplicacion.id, nuevoEstado);
}

// Función para actualizar estado directamente
function updateEstadoDirecto(id, nuevoEstado) {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'actualizar_estado_aplicacion',
            nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>',
            aplicacion_id: id,
            estado: nuevoEstado
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar datos locales
            const aplicacion = allAplicaciones.find(a => a.id == id);
            if (aplicacion) {
                aplicacion.estado = nuevoEstado;
            }
            
            // Actualizar vista
            updateStats();
            applyFilters();
            
            // Cerrar modal si está abierto
            if (selectedAplicacion && selectedAplicacion.id == id) {
                selectedAplicacion.estado = nuevoEstado;
                aplicacionModal.hide();
            }
            
            showNotification(`Estado actualizado a: ${nuevoEstado}`, 'success');
        } else {
            showNotification('Error al actualizar estado', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

function cambiarEstado(id) {
    const estados = ['Pendiente', 'Revisada', 'Aceptada', 'Rechazada'];
    const aplicacion = allAplicaciones.find(a => a.id == id);
    if (!aplicacion) return;
    
    const currentIndex = estados.indexOf(aplicacion.estado);
    const nextIndex = (currentIndex + 1) % estados.length;
    const nuevoEstado = estados[nextIndex];
    
    updateEstadoDirecto(id, nuevoEstado);
}

// Función para exportar aplicaciones
function exportarAplicaciones() {
    const aplicacionesParaExportar = filteredAplicaciones.length > 0 ? filteredAplicaciones : allAplicaciones;
    
    if (aplicacionesParaExportar.length === 0) {
        showNotification('No hay aplicaciones para exportar', 'error');
        return;
    }
    
    // Crear datos para exportar
    const datosExport = aplicacionesParaExportar.map(aplicacion => ({
        'Nombre Completo': `${aplicacion.nombres} ${aplicacion.apellidos}`,
        'DPI': aplicacion.dpi,
        'Email': aplicacion.email,
        'Teléfono': aplicacion.telefono,
        'Código Vacante': aplicacion.vacante_codigo,
        'Título Vacante': aplicacion.vacante_titulo,
        'Estado': aplicacion.estado,
        'Fecha Aplicación': formatDate(aplicacion.fecha_aplicacion)
    }));
    
    // Enviar solicitud de exportación
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'exportar_aplicaciones_excel',
            nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>',
            datos: JSON.stringify(datosExport)
        })
    })
    .then(response => response.blob())
    .then(blob => {
        // Crear enlace de descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `aplicaciones_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showNotification('Aplicaciones exportadas exitosamente', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al exportar aplicaciones', 'error');
    });
}// 
Funciones auxiliares
function showLoading(show) {
    const spinner = document.getElementById('loading-spinner');
    const card = document.getElementById('aplicaciones-card');
    
    if (show) {
        spinner.style.display = 'block';
        card.style.display = 'none';
    } else {
        spinner.style.display = 'none';
        card.style.display = 'block';
    }
}

function showError(message) {
    const tbody = document.getElementById('aplicaciones-tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <br>${message}
            </td>
        </tr>
    `;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getEstadoBadgeClass(estado) {
    switch (estado) {
        case 'Pendiente': return 'bg-warning text-dark';
        case 'Revisada': return 'bg-info';
        case 'Aceptada': return 'bg-success';
        case 'Rechazada': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'success') {
    // Crear toast de Bootstrap
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remover el elemento después de que se oculte
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    const paginationInfo = document.getElementById('pagination-info-text');
    const paginationControls = document.getElementById('pagination-controls');
    
    if (filteredAplicaciones.length === 0) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    totalPages = Math.ceil(filteredAplicaciones.length / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    // Actualizar información
    const startIndex = currentPage * itemsPerPage + 1;
    const endIndex = Math.min((currentPage + 1) * itemsPerPage, filteredAplicaciones.length);
    paginationInfo.textContent = `Mostrando ${startIndex}-${endIndex} de ${filteredAplicaciones.length} aplicaciones`;
    
    // Generar controles de paginación
    let paginationHTML = '';
    
    // Botón anterior
    paginationHTML += `
        <li class="page-item ${currentPage === 0 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="prevPage(); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Números de página
    const maxVisiblePages = 5;
    let startPage = Math.max(0, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages - 1, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(0, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i}); return false;">
                    ${i + 1}
                </a>
            </li>
        `;
    }
    
    // Botón siguiente
    paginationHTML += `
        <li class="page-item ${currentPage === totalPages - 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="nextPage(); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    paginationControls.innerHTML = paginationHTML;
}

function prevPage() {
    if (currentPage > 0) {
        currentPage--;
        updateTable();
        updatePagination();
    }
}

function nextPage() {
    if (currentPage < totalPages - 1) {
        currentPage++;
        updateTable();
        updatePagination();
    }
}

function goToPage(page) {
    currentPage = page;
    updateTable();
    updatePagination();
}

// Test básico sin dependencias
function testBasico() {
    alert('🧪 Test Básico Iniciado - Revisa la consola (F12)');
    console.log('=== TEST BÁSICO INICIADO ===');
    console.log('jQuery disponible:', typeof jQuery !== 'undefined');
    console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined');
    console.log('URL AJAX:', '<?php echo admin_url('admin-ajax.php'); ?>');
    console.log('Nonce:', '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>');
    
    if (typeof jQuery === 'undefined') {
        alert('❌ ERROR: jQuery no está disponible');
        return;
    }
    
    console.log('Enviando petición AJAX...');
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'cargar_aplicaciones_admin',
            nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>'
        },
        success: function(response) {
            console.log('✅ ÉXITO:', response);
            alert('✅ AJAX funcionó! Revisa consola para detalles');
            
            if (response.success && response.data) {
                allAplicaciones = response.data;
                updateStats();
                applyFilters();
                alert(`📊 Cargadas ${response.data.length} aplicaciones`);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ ERROR AJAX:', status, error);
            console.error('Respuesta completa:', xhr.responseText);
            alert('❌ Error AJAX: ' + error);
        }
    });
}

// Cargar directo sin validaciones
function cargarDirecto() {
    console.log('🚀 Carga directa iniciada...');
    
    // Datos de prueba para verificar que la interfaz funciona
    const datosPrueba = [
        {
            id: 1,
            nombres: 'Juan Carlos',
            apellidos: 'García López',
            email: 'juan@test.com',
            telefono: '12345678',
            estado: 'Pendiente',
            vacante_codigo: 'TEST-001',
            vacante_titulo: 'Desarrollador',
            fecha_aplicacion: '2024-10-23'
        },
        {
            id: 2,
            nombres: 'María Elena',
            apellidos: 'Rodríguez',
            email: 'maria@test.com',
            telefono: '87654321',
            estado: 'Revisada',
            vacante_codigo: 'TEST-002',
            vacante_titulo: 'Analista',
            fecha_aplicacion: '2024-10-22'
        }
    ];
    
    console.log('📊 Cargando datos de prueba:', datosPrueba);
    allAplicaciones = datosPrueba;
    updateStats();
    applyFilters();
    alert('✅ Datos de prueba cargados! Deberías ver 2 aplicaciones');
}

// Mostrar información de debug
function mostrarInfo() {
    const info = `
=== INFORMACIÓN DE DEBUG ===
jQuery: ${typeof jQuery !== 'undefined' ? '✅ Disponible' : '❌ No disponible'}
Bootstrap: ${typeof bootstrap !== 'undefined' ? '✅ Disponible' : '❌ No disponible'}
URL AJAX: <?php echo admin_url('admin-ajax.php'); ?>
Nonce: <?php echo wp_create_nonce('aplicaciones_nonce'); ?>
Aplicaciones cargadas: ${allAplicaciones.length}
Usuario actual: <?php echo wp_get_current_user()->user_login; ?>
Permisos: <?php echo current_user_can('manage_options') ? 'Sí' : 'No'; ?>
    `;
    
    console.log(info);
    alert(info);
}

// Función simplificada para recargar
function recargarAplicaciones() {
    console.log('🔄 Recargando aplicaciones...');
    loadAplicaciones();
}

// Funciones de test simples
function testAjaxSimple() {
    console.log('🧪 Test AJAX Simple iniciado');
    
    if (typeof jQuery === 'undefined') {
        alert('❌ jQuery no disponible');
        return;
    }
    
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'cargar_aplicaciones_admin',
        nonce: '<?php echo wp_create_nonce('aplicaciones_nonce'); ?>'
    })
    .done(function(response) {
        console.log('✅ Respuesta AJAX:', response);
        alert('✅ AJAX OK: ' + (response.success ? response.data.length + ' aplicaciones' : 'Error: ' + response.data));
        
        if (response.success) {
            allAplicaciones = response.data || [];
            updateStats();
            applyFilters();
        }
    })
    .fail(function(xhr, status, error) {
        console.error('❌ Error AJAX:', status, error);
        alert('❌ Error AJAX: ' + error);
    });
}

function cargarDatosPrueba() {
    console.log('📊 Cargando datos de prueba');
    
    allAplicaciones = [
        {
            id: 1,
            nombres: 'Juan Carlos',
            apellidos: 'García López',
            email: 'juan@test.com',
            telefono: '12345678',
            estado: 'Pendiente',
            vacante_codigo: 'TEST-001',
            vacante_titulo: 'Desarrollador',
            fecha_aplicacion: '2024-10-23',
            profesion: 'Ingeniero',
            experiencia_laboral: 3
        },
        {
            id: 2,
            nombres: 'María Elena',
            apellidos: 'Rodríguez',
            email: 'maria@test.com',
            telefono: '87654321',
            estado: 'Revisada',
            vacante_codigo: 'TEST-002',
            vacante_titulo: 'Analista',
            fecha_aplicacion: '2024-10-22',
            profesion: 'Contadora',
            experiencia_laboral: 5
        }
    ];
    
    updateStats();
    applyFilters();
    alert('✅ Datos de prueba cargados: ' + allAplicaciones.length + ' aplicaciones');
}
</script>

<style>
/* Estilos adicionales para mejorar la apariencia */
.loading-spinner {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.text-muted {
    color: #6c757d !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2.d-flex.align-items-end {
        margin-top: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>