<?php
/**
 * Página de Gestión de Vacantes - Versión Nueva
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

// Obtener direcciones y tipos de contrato para filtros
try {
    $direcciones = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}direcciones_minfin ORDER BY nombre");
    $tipos_contrato = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tipos_contrato_minfin ORDER BY nombre");
} catch (Exception $e) {
    $direcciones = array();
    $tipos_contrato = array();
}
?>

<div class="wrap vacantes-moderno">
    <!-- Header -->
    <div class="vacantes-header">
        <div class="header-content">
            <div>
                <h1>Gestión de Vacantes</h1>
                <p class="vacantes-subtitle">Administra las vacantes laborales</p>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <i class="dashicons dashicons-plus-alt2"></i>
                Nueva Vacante
            </button>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="filters-card">
        <div class="filters-content">
            <div class="filter-group">
                <label class="filter-label">Buscar vacantes</label>
                <input type="text" class="filter-input" id="search-input" 
                       placeholder="Buscar por título, código, descripción...">
            </div>
            <div class="filter-group">
                <label class="filter-label">Filtrar por dirección</label>
                <select class="filter-select" id="filter-direccion">
                    <option value="">Todas las direcciones</option>
                    <?php foreach ($direcciones as $direccion): ?>
                    <option value="<?php echo $direccion->id; ?>">
                        <?php echo esc_html($direccion->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Estado</label>
                <select class="filter-select" id="filter-estado">
                    <option value="">Todos los estados</option>
                    <option value="Activa">Activas</option>
                    <option value="Pausada">Pausadas</option>
                    <option value="Cerrada">Cerradas</option>
                </select>
            </div>
            <div class="filter-group filter-actions">
                <button class="btn-clear" onclick="clearFilters()">
                    <i class="dashicons dashicons-no"></i>
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading-spinner" class="loading-container" style="display: none;">
        <div class="loading-spinner"></div>
        <span>Cargando vacantes...</span>
    </div>

    <!-- Tabla de Vacantes -->
    <div class="vacantes-table-card">
        <div class="table-container">
            <table class="vacantes-table" id="vacantes-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Dirección</th>
                        <th>Tipo</th>
                        <th>Fecha Límite</th>
                        <th>Aplicaciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="vacantes-tbody">
                    <tr>
                        <td colspan="8" class="loading-cell">
                            <div class="loading-spinner"></div>
                            <span>Cargando datos...</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="dashicons dashicons-portfolio"></i>
            <h3 id="empty-message">No se encontraron vacantes</h3>
            <button class="btn-primary" onclick="openModal()" id="create-first-btn" style="display: none;">
                <i class="dashicons dashicons-plus-alt2"></i>
                Crear Primera Vacante
            </button>
            <button class="btn-secondary" onclick="clearFilters()" id="clear-filters-btn" style="display: none;">
                <i class="dashicons dashicons-no"></i>
                Limpiar Filtros
            </button>
        </div>

        <!-- Paginación -->
        <div class="pagination-container" id="pagination-container" style="display: none;">
            <div class="pagination-info">
                <span id="pagination-info-text">Mostrando 0 vacantes</span>
            </div>
            <div class="pagination-controls" id="pagination-controls">
                <!-- Se genera dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Vacante -->
<div class="modal-overlay" id="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h2 id="modal-title">Nueva Vacante</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="dashicons dashicons-no-alt"></i>
            </button>
        </div>
        
        <form id="vacante-form" class="modal-body">
            <div class="form-grid">
                <!-- Columna Izquierda -->
                <div class="form-column">
                    <div class="form-group">
                        <label class="form-label">Título *</label>
                        <input type="text" id="titulo" name="titulo" class="form-input" required>
                        <div class="form-error" id="titulo-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descripción *</label>
                        <textarea id="descripcion" name="descripcion" class="form-textarea" rows="4" required 
                                  placeholder="Describe las responsabilidades y funciones del puesto..."></textarea>
                        <div class="form-error" id="descripcion-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Requisitos *</label>
                        <textarea id="requisitos" name="requisitos" class="form-textarea" rows="4" required 
                                  placeholder="Lista los requisitos necesarios para el puesto..."></textarea>
                        <div class="form-error" id="requisitos-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Beneficios</label>
                        <textarea id="beneficios" name="beneficios" class="form-textarea" rows="3" 
                                  placeholder="Describe los beneficios del puesto..."></textarea>
                    </div>
                </div>
                
                <!-- Columna Derecha -->
                <div class="form-column">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Dirección *</label>
                            <select id="direccion_id" name="direccion_id" class="form-select" required>
                                <option value="">Seleccionar dirección...</option>
                                <?php foreach ($direcciones as $direccion): ?>
                                <option value="<?php echo $direccion->id; ?>">
                                    <?php echo esc_html($direccion->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-error" id="direccion_id-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tipo de Contrato *</label>
                            <select id="tipo_contrato_id" name="tipo_contrato_id" class="form-select" required>
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($tipos_contrato as $tipo): ?>
                                <option value="<?php echo $tipo->id; ?>">
                                    <?php echo esc_html($tipo->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-error" id="tipo_contrato_id-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Salario Mínimo</label>
                            <input type="number" id="salario_min" name="salario_min" class="form-input" 
                                   step="0.01" placeholder="8000.00">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Salario Máximo</label>
                            <input type="number" id="salario_max" name="salario_max" class="form-input" 
                                   step="0.01" placeholder="12000.00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fecha Límite *</label>
                        <input type="date" id="fecha_limite" name="fecha_limite" class="form-input" required>
                        <div class="form-error" id="fecha_limite-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-input" 
                               placeholder="Ciudad de Guatemala">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Modalidad</label>
                            <select id="modalidad" name="modalidad" class="form-select">
                                <option value="Presencial">Presencial</option>
                                <option value="Remoto">Remoto</option>
                                <option value="Híbrido">Híbrido</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Experiencia (años)</label>
                            <input type="number" id="experiencia_requerida" name="experiencia_requerida" 
                                   class="form-input" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nivel Educativo</label>
                            <select id="nivel_educativo" name="nivel_educativo" class="form-select">
                                <option value="Secundaria">Secundaria</option>
                                <option value="Técnico">Técnico</option>
                                <option value="Universitario">Universitario</option>
                                <option value="Postgrado">Postgrado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select id="estado" name="estado" class="form-select">
                                <option value="Activa">Activa</option>
                                <option value="Pausada">Pausada</option>
                                <option value="Cerrada">Cerrada</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Error general -->
            <div class="form-error" id="form-error" style="display: none;"></div>
        </form>
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal()">
                Cancelar
            </button>
            <button type="button" class="btn-primary" onclick="saveVacante()" id="save-btn">
                <span id="save-text">Crear Vacante</span>
                <div class="btn-spinner" id="save-spinner" style="display: none;"></div>
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos principales para Gestión de Vacantes */
.vacantes-moderno {
    background: #f8f9fa;
    padding: 20px;
    margin: 0 -20px;
    min-height: 100vh;
}

/* Header */
.vacantes-header {
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vacantes-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #495057;
    margin: 0 0 5px 0;
}

.vacantes-subtitle {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

/* Botones */
.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s ease;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
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

.btn-secondary:hover {
    background: #545b62;
}

.btn-clear {
    background: #6c757d;
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

.btn-clear:hover {
    background: #545b62;
}

/* Filtros */
.filters-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    padding: 24px;
    border: none !important;
}

.filters-content {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 200px;
    flex: 1;
}

.filter-group:last-child {
    flex: 0 0 auto;
    min-width: auto;
}

.filter-label {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 4px;
}

.filter-input, .filter-select {
    padding: 10px 12px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    background: #f8f9fa;
    color: #495057;
    height: 42px;
    transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-input::placeholder {
    color: #6c757d;
    opacity: 0.8;
}

/* Tabla */
.vacantes-table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}

.vacantes-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.vacantes-table th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    padding: 16px 12px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    font-size: 13px;
    white-space: nowrap;
}

.vacantes-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    color: #495057;
    vertical-align: middle;
}

.vacantes-table tbody tr:hover {
    background: #f8f9fa;
}

.vacantes-table tbody tr:last-child td {
    border-bottom: none;
}

/* Loading */
.loading-container {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.loading-cell {
    text-align: center;
    padding: 40px 16px !important;
    color: #6c757d;
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e9ecef;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    display: inline-block;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 500;
    margin: 0 0 20px 0;
    color: #6c757d;
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

.badge.bg-secondary { background: #6c757d !important; color: white; }
.badge.bg-info { background: #17a2b8 !important; color: white; }
.badge.bg-primary { background: #007bff !important; color: white; }
.badge.bg-success { background: #28a745 !important; color: white; }
.badge.bg-warning { background: #ffc107 !important; color: #212529; }
.badge.bg-danger { background: #dc3545 !important; color: white; }

/* Botones de acción */
.action-buttons {
    display: flex;
    gap: 4px;
}

.btn-action {
    padding: 6px 8px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
}

.btn-action:hover {
    background: #f8f9fa;
}

.btn-action.btn-edit { border-color: #007bff; color: #007bff; }
.btn-action.btn-edit:hover { background: #007bff; color: white; }

.btn-action.btn-view { border-color: #17a2b8; color: #17a2b8; }
.btn-action.btn-view:hover { background: #17a2b8; color: white; }

.btn-action.btn-delete { border-color: #dc3545; color: #dc3545; }
.btn-action.btn-delete:hover { background: #dc3545; color: white; }

/* Paginación */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-top: 1px solid #e9ecef;
}

.pagination-info {
    color: #6c757d;
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    gap: 4px;
}

.page-btn {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
}

.page-btn:hover {
    background: #f8f9fa;
}

.page-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Estados de texto */
.text-danger { color: #dc3545 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-muted { color: #6c757d !important; }
.fw-bold { font-weight: 600 !important; }

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #495057;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6c757d;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.modal-close:hover {
    background: #e9ecef;
    color: #495057;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
}

/* Formulario */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
}

.form-input, .form-select, .form-textarea {
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    color: #495057;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-error {
    color: #dc3545;
    font-size: 12px;
    margin-top: 4px;
    display: none;
}

.form-error.show {
    display: block;
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

/* Spinner del botón */
.btn-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .vacantes-moderno {
        padding: 15px;
        margin: 0 -15px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .filters-content {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    .vacantes-table {
        font-size: 13px;
    }
    
    .vacantes-table th,
    .vacantes-table td {
        padding: 8px 6px;
    }
    
    /* Modal responsive */
    .modal-overlay {
        padding: 10px;
    }
    
    .modal-container {
        max-width: 100%;
        max-height: 95vh;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .modal-header {
        padding: 16px 20px;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-footer {
        padding: 12px 20px;
        flex-direction: column-reverse;
    }
    
    .modal-footer button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Variables globales
let allVacantes = [];
let filteredVacantes = [];
let currentPage = 0;
let itemsPerPage = 10;
let totalPages = 0;

// Filtros
let searchTerm = '';
let filterDireccion = '';
let filterEstado = '';

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos iniciales
    loadVacantes();
    
    // Event listeners para filtros
    document.getElementById('search-input').addEventListener('input', debounce(onSearchChange, 300));
    document.getElementById('filter-direccion').addEventListener('change', onFilterChange);
    document.getElementById('filter-estado').addEventListener('change', onFilterChange);
});

// Función para cargar vacantes
function loadVacantes() {
    showLoading(true);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'cargar_vacantes',
            nonce: '<?php echo wp_create_nonce('vacantes_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allVacantes = data.data;
            applyFilters();
        } else {
            console.error('Error:', data.data);
            showError('Error al cargar vacantes');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error de conexión');
    })
    .finally(() => {
        showLoading(false);
    });
}

// Función para aplicar filtros
function applyFilters() {
    filteredVacantes = allVacantes.filter(vacante => {
        // Filtro de búsqueda
        if (searchTerm) {
            const searchLower = searchTerm.toLowerCase();
            const matchesSearch = 
                vacante.titulo.toLowerCase().includes(searchLower) ||
                vacante.codigo.toLowerCase().includes(searchLower) ||
                (vacante.descripcion && vacante.descripcion.toLowerCase().includes(searchLower)) ||
                (vacante.direccion_nombre && vacante.direccion_nombre.toLowerCase().includes(searchLower));
            
            if (!matchesSearch) return false;
        }
        
        // Filtro de dirección
        if (filterDireccion && vacante.direccion_id != filterDireccion) {
            return false;
        }
        
        // Filtro de estado
        if (filterEstado && vacante.estado !== filterEstado) {
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
    const tbody = document.getElementById('vacantes-tbody');
    const emptyState = document.getElementById('empty-state');
    const tableCard = document.querySelector('.vacantes-table-card .table-container');
    
    if (filteredVacantes.length === 0) {
        // Mostrar estado vacío
        tableCard.style.display = 'none';
        emptyState.style.display = 'block';
        
        // Actualizar mensaje
        const emptyMessage = document.getElementById('empty-message');
        const createFirstBtn = document.getElementById('create-first-btn');
        const clearFiltersBtn = document.getElementById('clear-filters-btn');
        
        if (allVacantes.length === 0) {
            emptyMessage.textContent = 'No hay vacantes registradas';
            createFirstBtn.style.display = 'flex';
            clearFiltersBtn.style.display = 'none';
        } else {
            emptyMessage.textContent = 'No se encontraron vacantes con los filtros aplicados';
            createFirstBtn.style.display = 'none';
            clearFiltersBtn.style.display = 'flex';
        }
        
        return;
    }
    
    // Mostrar tabla
    tableCard.style.display = 'block';
    emptyState.style.display = 'none';
    
    // Calcular elementos de la página actual
    const startIndex = currentPage * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredVacantes.length);
    const pageVacantes = filteredVacantes.slice(startIndex, endIndex);
    
    // Generar HTML de la tabla
    tbody.innerHTML = pageVacantes.map(vacante => `
        <tr>
            <td>
                <span class="badge bg-secondary">${vacante.codigo}</span>
            </td>
            <td>
                <strong>${vacante.titulo}</strong>
                ${vacante.salario ? `<div class="text-muted" style="font-size: 12px;">${vacante.salario}</div>` : ''}
            </td>
            <td>${vacante.direccion_nombre || 'N/A'}</td>
            <td>
                <span class="badge bg-info">${vacante.tipo_contrato_nombre || 'N/A'}</span>
            </td>
            <td>
                <span class="${isExpiringSoon(vacante.fecha_limite) ? 'text-danger fw-bold' : ''}">
                    ${formatDate(vacante.fecha_limite)}
                </span>
            </td>
            <td>
                <span class="badge bg-primary">${vacante.total_aplicaciones || 0}</span>
            </td>
            <td>
                <span class="badge ${getEstadoBadgeClass(vacante.estado)}">${vacante.estado}</span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-edit" onclick="editVacante(${vacante.id})" title="Editar">
                        <i class="dashicons dashicons-edit"></i>
                    </button>
                    <button class="btn-action btn-view" onclick="viewApplications(${vacante.id})" title="Ver Aplicaciones">
                        <i class="dashicons dashicons-groups"></i>
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteVacante(${vacante.id})" title="Eliminar">
                        <i class="dashicons dashicons-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Función para actualizar paginación
function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    const paginationInfo = document.getElementById('pagination-info-text');
    const paginationControls = document.getElementById('pagination-controls');
    
    if (filteredVacantes.length === 0) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    totalPages = Math.ceil(filteredVacantes.length / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    
    // Actualizar información
    const startIndex = currentPage * itemsPerPage + 1;
    const endIndex = Math.min((currentPage + 1) * itemsPerPage, filteredVacantes.length);
    paginationInfo.textContent = `Mostrando ${startIndex}-${endIndex} de ${filteredVacantes.length} vacantes`;
    
    // Generar controles de paginación
    let paginationHTML = '';
    
    // Botón anterior
    paginationHTML += `
        <button class="page-btn" onclick="prevPage()" ${currentPage === 0 ? 'disabled' : ''}>
            <i class="dashicons dashicons-arrow-left-alt2"></i>
        </button>
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
            <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                ${i + 1}
            </button>
        `;
    }
    
    // Botón siguiente
    paginationHTML += `
        <button class="page-btn" onclick="nextPage()" ${currentPage === totalPages - 1 ? 'disabled' : ''}>
            <i class="dashicons dashicons-arrow-right-alt2"></i>
        </button>
    `;
    
    paginationControls.innerHTML = paginationHTML;
}

// Event handlers
function onSearchChange() {
    searchTerm = document.getElementById('search-input').value;
    applyFilters();
}

function onFilterChange() {
    filterDireccion = document.getElementById('filter-direccion').value;
    filterEstado = document.getElementById('filter-estado').value;
    applyFilters();
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-direccion').value = '';
    document.getElementById('filter-estado').value = '';
    
    searchTerm = '';
    filterDireccion = '';
    filterEstado = '';
    
    applyFilters();
}

// Funciones de paginación
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

// Variables del modal
let editingVacante = null;
let isSubmitting = false;

// Funciones del modal
function openModal(vacanteId = null) {
    editingVacante = vacanteId ? allVacantes.find(v => v.id == vacanteId) : null;
    
    // Actualizar título del modal
    const modalTitle = document.getElementById('modal-title');
    const saveText = document.getElementById('save-text');
    
    if (editingVacante) {
        modalTitle.textContent = 'Editar Vacante';
        saveText.textContent = 'Actualizar Vacante';
        fillForm(editingVacante);
    } else {
        modalTitle.textContent = 'Nueva Vacante';
        saveText.textContent = 'Crear Vacante';
        clearForm();
    }
    
    // Mostrar modal
    document.getElementById('modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.body.style.overflow = 'auto';
    clearForm();
    clearErrors();
    editingVacante = null;
}

function fillForm(vacante) {
    document.getElementById('titulo').value = vacante.titulo || '';
    document.getElementById('descripcion').value = vacante.descripcion || '';
    document.getElementById('requisitos').value = vacante.requisitos || '';
    document.getElementById('beneficios').value = vacante.beneficios || '';
    document.getElementById('direccion_id').value = vacante.direccion_id || '';
    document.getElementById('tipo_contrato_id').value = vacante.tipo_contrato_id || '';
    document.getElementById('salario_min').value = vacante.salario_min || '';
    document.getElementById('salario_max').value = vacante.salario_max || '';
    document.getElementById('fecha_limite').value = vacante.fecha_limite ? vacante.fecha_limite.split(' ')[0] : '';
    document.getElementById('ubicacion').value = vacante.ubicacion || '';
    document.getElementById('modalidad').value = vacante.modalidad || 'Presencial';
    document.getElementById('experiencia_requerida').value = vacante.experiencia_requerida || '0';
    document.getElementById('nivel_educativo').value = vacante.nivel_educativo || 'Universitario';
    document.getElementById('estado').value = vacante.estado || 'Activa';
}

function clearForm() {
    const form = document.getElementById('vacante-form');
    form.reset();
    
    // Valores por defecto
    document.getElementById('modalidad').value = 'Presencial';
    document.getElementById('experiencia_requerida').value = '0';
    document.getElementById('nivel_educativo').value = 'Universitario';
    document.getElementById('estado').value = 'Activa';
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(error => {
        error.style.display = 'none';
        error.textContent = '';
    });
    
    document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(input => {
        input.classList.remove('error');
    });
}

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const error = document.getElementById(fieldId + '-error');
    
    if (field) field.classList.add('error');
    if (error) {
        error.textContent = message;
        error.style.display = 'block';
    }
}

function validateForm() {
    clearErrors();
    let isValid = true;
    
    // Validaciones requeridas
    const requiredFields = [
        { id: 'titulo', message: 'El título es requerido' },
        { id: 'descripcion', message: 'La descripción es requerida' },
        { id: 'requisitos', message: 'Los requisitos son requeridos' },
        { id: 'direccion_id', message: 'La dirección es requerida' },
        { id: 'tipo_contrato_id', message: 'El tipo de contrato es requerido' },
        { id: 'fecha_limite', message: 'La fecha límite es requerida' }
    ];
    
    requiredFields.forEach(field => {
        const value = document.getElementById(field.id).value.trim();
        if (!value) {
            showError(field.id, field.message);
            isValid = false;
        }
    });
    
    // Validar que la fecha límite sea futura
    const fechaLimite = document.getElementById('fecha_limite').value;
    const hoy = new Date().toISOString().split('T')[0];
    
    if (fechaLimite && fechaLimite <= hoy) {
        showError('fecha_limite', 'La fecha límite debe ser posterior a hoy');
        isValid = false;
    }
    
    // Validar salarios
    const salarioMin = parseFloat(document.getElementById('salario_min').value) || 0;
    const salarioMax = parseFloat(document.getElementById('salario_max').value) || 0;
    
    if (salarioMin > 0 && salarioMax > 0 && salarioMin >= salarioMax) {
        showError('salario_max', 'El salario máximo debe ser mayor al mínimo');
        isValid = false;
    }
    
    return isValid;
}

function saveVacante() {
    if (isSubmitting) return;
    
    if (!validateForm()) {
        return;
    }
    
    isSubmitting = true;
    
    // Mostrar spinner
    document.getElementById('save-spinner').style.display = 'inline-block';
    document.getElementById('save-text').style.opacity = '0.7';
    document.getElementById('save-btn').disabled = true;
    
    // Recopilar datos del formulario
    const formData = new FormData();
    formData.append('action', editingVacante ? 'actualizar_vacante' : 'crear_vacante');
    formData.append('nonce', '<?php echo wp_create_nonce('vacantes_nonce'); ?>');
    
    if (editingVacante) {
        formData.append('id', editingVacante.id);
    }
    
    // Agregar todos los campos (excluyendo fecha_inicio que no existe en la tabla)
    const fields = [
        'titulo', 'descripcion', 'requisitos', 'beneficios', 'direccion_id', 
        'tipo_contrato_id', 'salario_min', 'salario_max', 
        'fecha_limite', 'ubicacion', 'modalidad', 'experiencia_requerida', 
        'nivel_educativo', 'estado'
    ];
    
    fields.forEach(field => {
        formData.append(field, document.getElementById(field).value);
    });
    
    // Enviar datos
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadVacantes(); // Recargar la lista
            showNotification(editingVacante ? 'Vacante actualizada correctamente' : 'Vacante creada correctamente', 'success');
        } else {
            const errorElement = document.getElementById('form-error');
            errorElement.textContent = data.data || 'Error al guardar la vacante';
            errorElement.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorElement = document.getElementById('form-error');
        errorElement.textContent = 'Error de conexión. Intenta nuevamente.';
        errorElement.style.display = 'block';
    })
    .finally(() => {
        isSubmitting = false;
        document.getElementById('save-spinner').style.display = 'none';
        document.getElementById('save-text').style.opacity = '1';
        document.getElementById('save-btn').disabled = false;
    });
}

function editVacante(id) {
    openModal(id);
}

function viewApplications(id) {
    // Redirigir a la página de aplicaciones
    window.location.href = `?page=aplicaciones-list&vacante_id=${id}`;
}

function deleteVacante(id) {
    if (confirm('¿Está seguro de eliminar esta vacante?')) {
        // TODO: Implementar eliminación
        alert('Eliminar vacante ID: ' + id);
    }
}

// Funciones auxiliares
function showLoading(show) {
    const spinner = document.getElementById('loading-spinner');
    const tableCard = document.querySelector('.vacantes-table-card');
    
    if (show) {
        spinner.style.display = 'block';
        tableCard.style.display = 'none';
    } else {
        spinner.style.display = 'none';
        tableCard.style.display = 'block';
    }
}

function showError(message) {
    const tbody = document.getElementById('vacantes-tbody');
    tbody.innerHTML = `<tr><td colspan="8" class="loading-cell text-danger">${message}</td></tr>`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-GT', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function isExpiringSoon(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const diffTime = date - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays <= 7 && diffDays >= 0;
}

function getEstadoBadgeClass(estado) {
    switch (estado) {
        case 'Activa': return 'bg-success';
        case 'Pausada': return 'bg-warning';
        case 'Cerrada': return 'bg-danger';
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
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="dashicons dashicons-${type === 'success' ? 'yes-alt' : 'warning'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 16px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            }
            .notification-success { background: #28a745; }
            .notification-error { background: #dc3545; }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Mostrar notificación
    document.body.appendChild(notification);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Event listeners adicionales
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-overlay');
        if (modal.style.display === 'flex') {
            closeModal();
        }
    }
});

// Cerrar modal al hacer clic en el overlay
document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>