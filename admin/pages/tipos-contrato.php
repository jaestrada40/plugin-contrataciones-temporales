<?php
/**
 * Página de administración de Tipos de Contrato
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

// Incluir modelo
require_once VACANTES_MINFIN_PATH . 'models/class-tipo-contrato.php';

$tipo_contrato_model = new Tipo_Contrato_Model();
$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = array(
                'codigo' => sanitize_text_field($_POST['codigo']),
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activo' => isset($_POST['activo']) ? 1 : 0
            );
            
            $result = $tipo_contrato_model->create($data);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Tipo de contrato creado exitosamente.';
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $data = array(
                'codigo' => sanitize_text_field($_POST['codigo']),
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activo' => isset($_POST['activo']) ? 1 : 0
            );
            
            $result = $tipo_contrato_model->update($id, $data);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Tipo de contrato actualizado exitosamente.';
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            $result = $tipo_contrato_model->delete($id);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Tipo de contrato eliminado exitosamente.';
            }
            break;
            
        case 'toggle_status':
            $id = intval($_POST['id']);
            $activo = intval($_POST['activo']);
            $result = $tipo_contrato_model->cambiar_estado($id, $activo);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Estado actualizado exitosamente.';
            }
            break;
    }
}

// Obtener datos
$search = $_GET['search'] ?? '';
$filter_activo = $_GET['filter_activo'] ?? '';
$filter_tipo = $_GET['filter_tipo'] ?? '';

$args = array();
if ($search) {
    $args['search'] = $search;
}
if ($filter_activo !== '') {
    $args['activo'] = $filter_activo;
}
if ($filter_tipo !== '') {
    $args['es_estandar'] = $filter_tipo;
}

$tipos_contrato = $tipo_contrato_model->get_all($args);
$stats = $tipo_contrato_model->get_stats();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="dashicons dashicons-clipboard"></i>
        Tipos de Contrato
    </h1>
    
    <a href="#" class="page-title-action" onclick="openCreateModal()">Agregar Nuevo</a>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Estadísticas -->
    <div class="vacantes-stats-grid">
        <div class="vacantes-stat-card">
            <div class="stat-icon">
                <i class="dashicons dashicons-clipboard"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Tipos</div>
            </div>
        </div>
        
        <div class="vacantes-stat-card">
            <div class="stat-icon">
                <i class="dashicons dashicons-yes-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['activos']; ?></div>
                <div class="stat-label">Activos</div>
            </div>
        </div>
        
        <div class="vacantes-stat-card">
            <div class="stat-icon">
                <i class="dashicons dashicons-dismiss"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['inactivos']; ?></div>
                <div class="stat-label">Inactivos</div>
            </div>
        </div>
        
        <div class="vacantes-stat-card">
            <div class="stat-icon">
                <i class="dashicons dashicons-admin-settings"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['estandar']; ?></div>
                <div class="stat-label">Estándar</div>
            </div>
        </div>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="vacantes-filters">
        <form method="GET" class="filter-form">
            <input type="hidden" name="page" value="vacantes-tipos-contrato">
            
            <div class="filter-group">
                <input type="text" 
                       name="search" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="Buscar por código o nombre..."
                       class="regular-text">
            </div>
            
            <div class="filter-group">
                <select name="filter_activo">
                    <option value="">Todos los estados</option>
                    <option value="1" <?php selected($filter_activo, '1'); ?>>Activos</option>
                    <option value="0" <?php selected($filter_activo, '0'); ?>>Inactivos</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="filter_tipo">
                    <option value="">Todos los tipos</option>
                    <option value="1" <?php selected($filter_tipo, '1'); ?>>Estándar</option>
                    <option value="0" <?php selected($filter_tipo, '0'); ?>>Personalizado</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button">Filtrar</button>
                <a href="<?php echo admin_url('admin.php?page=vacantes-tipos-contrato'); ?>" class="button">Limpiar</a>
            </div>
        </form>
    </div>
    
    <!-- Tabla de tipos de contrato -->
    <div class="vacantes-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th scope="col" class="manage-column">Código</th>
                    <th scope="col" class="manage-column">Nombre</th>
                    <th scope="col" class="manage-column">Descripción</th>
                    <th scope="col" class="manage-column">Tipo</th>
                    <th scope="col" class="manage-column">Estado</th>
                    <th scope="col" class="manage-column">Fecha Creación</th>
                    <th scope="col" class="manage-column">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tipos_contrato)): ?>
                    <tr>
                        <td colspan="8" class="no-items">
                            <div class="no-data-message">
                                <i class="dashicons dashicons-clipboard"></i>
                                <p>No se encontraron tipos de contrato.</p>
                                <a href="#" class="button button-primary" onclick="openCreateModal()">Crear el primer tipo</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tipos_contrato as $tipo): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="tipo_ids[]" value="<?php echo $tipo->id; ?>">
                            </th>
                            <td>
                                <strong><?php echo esc_html($tipo->codigo); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($tipo->nombre); ?>
                            </td>
                            <td>
                                <?php echo esc_html(wp_trim_words($tipo->descripcion, 10)); ?>
                            </td>
                            <td>
                                <span class="tipo-badge <?php echo $tipo->es_estandar ? 'estandar' : 'personalizado'; ?>">
                                    <?php echo $tipo->es_estandar ? 'Estándar' : 'Personalizado'; ?>
                                </span>
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" 
                                           <?php checked($tipo->activo, 1); ?>
                                           onchange="toggleStatus(<?php echo $tipo->id; ?>, this.checked)">
                                    <span class="slider"></span>
                                </label>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($tipo->fecha_creacion)); ?>
                            </td>
                            <td class="actions">
                                <button type="button" 
                                        class="button button-small" 
                                        onclick="editTipo(<?php echo htmlspecialchars(json_encode($tipo)); ?>)"
                                        title="Editar">
                                    <i class="dashicons dashicons-edit"></i>
                                </button>
                                
                                <button type="button" 
                                        class="button button-small button-link-delete" 
                                        onclick="deleteTipo(<?php echo $tipo->id; ?>, '<?php echo esc_js($tipo->nombre); ?>')"
                                        title="Eliminar">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para crear/editar tipo de contrato -->
<div id="tipoModal" class="vacantes-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Tipo de Contrato</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <form id="tipoForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="tipoId">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo">Código *</label>
                        <input type="text" 
                               id="codigo" 
                               name="codigo" 
                               required 
                               maxlength="10"
                               class="regular-text">
                        <p class="description">Código único del tipo de contrato (máximo 10 caracteres)</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               required 
                               maxlength="100"
                               class="regular-text">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              rows="3" 
                              class="large-text"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="activo" name="activo" checked>
                        Activo
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="button" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="button button-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Funciones para el modal
    window.openCreateModal = function() {
        $('#modalTitle').text('Nuevo Tipo de Contrato');
        $('#formAction').val('create');
        $('#tipoId').val('');
        $('#tipoForm')[0].reset();
        $('#activo').prop('checked', true);
        $('#tipoModal').show();
    };
    
    window.editTipo = function(tipo) {
        $('#modalTitle').text('Editar Tipo de Contrato');
        $('#formAction').val('update');
        $('#tipoId').val(tipo.id);
        $('#codigo').val(tipo.codigo);
        $('#nombre').val(tipo.nombre);
        $('#descripcion').val(tipo.descripcion);
        $('#activo').prop('checked', tipo.activo == 1);
        $('#tipoModal').show();
    };
    
    window.closeModal = function() {
        $('#tipoModal').hide();
    };
    
    window.toggleStatus = function(id, activo) {
        if (confirm('¿Estás seguro de cambiar el estado de este tipo de contrato?')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="toggle_status">')
                .append('<input type="hidden" name="id" value="' + id + '">')
                .append('<input type="hidden" name="activo" value="' + (activo ? 1 : 0) + '">');
            
            $('body').append(form);
            form.submit();
        } else {
            // Revertir el checkbox si se cancela
            $('input[onchange*="toggleStatus(' + id + ')"]').prop('checked', !activo);
        }
    };
    
    window.deleteTipo = function(id, nombre) {
        if (confirm('¿Estás seguro de eliminar el tipo de contrato "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="delete">')
                .append('<input type="hidden" name="id" value="' + id + '">');
            
            $('body').append(form);
            form.submit();
        }
    };
    
    // Cerrar modal al hacer clic fuera
    $(window).click(function(event) {
        if (event.target.id === 'tipoModal') {
            closeModal();
        }
    });
    
    // Validación del formulario
    $('#tipoForm').on('submit', function(e) {
        var codigo = $('#codigo').val().trim();
        var nombre = $('#nombre').val().trim();
        
        if (!codigo || !nombre) {
            alert('Por favor completa todos los campos obligatorios.');
            e.preventDefault();
            return false;
        }
        
        if (codigo.length > 10) {
            alert('El código no puede tener más de 10 caracteres.');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="tipo_ids[]"]').prop('checked', this.checked);
    });
});
</script>

<style>
.vacantes-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.vacantes-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 24px;
    color: #0073aa;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #23282d;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.vacantes-filters {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
}

.filter-form {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.vacantes-table-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.no-data-message {
    text-align: center;
    padding: 40px 20px;
}

.no-data-message i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 15px;
}

.tipo-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.tipo-badge.estandar {
    background: #d4edda;
    color: #155724;
}

.tipo-badge.personalizado {
    background: #fff3cd;
    color: #856404;
}

.switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 20px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #0073aa;
}

input:checked + .slider:before {
    transform: translateX(20px);
}

.actions {
    white-space: nowrap;
}

.actions .button {
    margin-right: 5px;
}

.vacantes-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group .description {
    font-style: italic;
    color: #666;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-actions {
        justify-content: center;
    }
}
</style>