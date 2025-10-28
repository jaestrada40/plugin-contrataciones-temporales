<?php
/**
 * Página simplificada de Tipos de Contrato
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

global $wpdb;

$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = array(
                'codigo' => strtoupper(sanitize_text_field($_POST['codigo'])),
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'es_estandar' => 0
            );
            
            // Verificar código único
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vs_tipos_contrato WHERE codigo = %s",
                $data['codigo']
            ));
            
            if ($exists > 0) {
                $error = 'Ya existe un tipo de contrato con ese código.';
            } else {
                $result = $wpdb->insert("{$wpdb->prefix}vs_tipos_contrato", $data);
                if ($result !== false) {
                    $message = 'Tipo de contrato creado exitosamente.';
                } else {
                    $error = 'Error al crear el tipo de contrato: ' . $wpdb->last_error;
                }
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $data = array(
                'codigo' => strtoupper(sanitize_text_field($_POST['codigo'])),
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activo' => isset($_POST['activo']) ? 1 : 0
            );
            
            $result = $wpdb->update(
                "{$wpdb->prefix}vs_tipos_contrato",
                $data,
                array('id' => $id)
            );
            
            if ($result !== false) {
                $message = 'Tipo de contrato actualizado exitosamente.';
            } else {
                $error = 'Error al actualizar el tipo de contrato.';
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si es estándar
            $tipo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vs_tipos_contrato WHERE id = %d",
                $id
            ));
            
            if ($tipo && $tipo->es_estandar) {
                $error = 'No se pueden eliminar los tipos de contrato estándar.';
            } else {
                $result = $wpdb->delete(
                    "{$wpdb->prefix}vs_tipos_contrato",
                    array('id' => $id)
                );
                
                if ($result !== false) {
                    $message = 'Tipo de contrato eliminado exitosamente.';
                } else {
                    $error = 'Error al eliminar el tipo de contrato.';
                }
            }
            break;
            
        case 'toggle_status':
            $id = intval($_POST['id']);
            $activo = intval($_POST['activo']);
            
            $result = $wpdb->update(
                "{$wpdb->prefix}vs_tipos_contrato",
                array('activo' => $activo),
                array('id' => $id)
            );
            
            if ($result !== false) {
                $message = 'Estado actualizado exitosamente.';
            } else {
                $error = 'Error al actualizar el estado.';
            }
            break;
    }
}

// Obtener datos
$search = $_GET['search'] ?? '';
$filter_activo = $_GET['filter_activo'] ?? '';

$sql = "SELECT * FROM {$wpdb->prefix}vs_tipos_contrato WHERE 1=1";
$params = array();

if ($search) {
    $sql .= " AND (codigo LIKE %s OR nombre LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter_activo !== '') {
    $sql .= " AND activo = %d";
    $params[] = intval($filter_activo);
}

$sql .= " ORDER BY es_estandar DESC, codigo ASC";

if (!empty($params)) {
    $tipos_contrato = $wpdb->get_results($wpdb->prepare($sql, $params));
} else {
    $tipos_contrato = $wpdb->get_results($sql);
}

// Estadísticas
$stats = array(
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_tipos_contrato"),
    'activos' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_tipos_contrato WHERE activo = 1"),
    'inactivos' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_tipos_contrato WHERE activo = 0"),
    'estandar' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_tipos_contrato WHERE es_estandar = 1")
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clipboard"></span>
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
    <div class="postbox-container" style="width: 100%;">
        <div class="meta-box-sortables">
            <div class="postbox">
                <h2 class="hndle"><span>Estadísticas</span></h2>
                <div class="inside">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total de Tipos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['activos']; ?></div>
                            <div class="stat-label">Activos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['inactivos']; ?></div>
                            <div class="stat-label">Inactivos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['estandar']; ?></div>
                            <div class="stat-label">Estándar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="postbox">
        <h2 class="hndle"><span>Filtros</span></h2>
        <div class="inside">
            <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                <input type="hidden" name="page" value="vacantes-tipos-contrato">
                
                <div>
                    <label>Buscar:</label><br>
                    <input type="text" name="search" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Código o nombre..." class="regular-text">
                </div>
                
                <div>
                    <label>Estado:</label><br>
                    <select name="filter_activo">
                        <option value="">Todos</option>
                        <option value="1" <?php selected($filter_activo, '1'); ?>>Activos</option>
                        <option value="0" <?php selected($filter_activo, '0'); ?>>Inactivos</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="button">Filtrar</button>
                    <a href="<?php echo admin_url('admin.php?page=vacantes-tipos-contrato'); ?>" class="button">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tipos_contrato)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div>
                            <span class="dashicons dashicons-clipboard" style="font-size: 48px; color: #ddd;"></span>
                            <p>No se encontraron tipos de contrato.</p>
                            <a href="#" class="button button-primary" onclick="openCreateModal()">Crear el primer tipo</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($tipos_contrato as $tipo): ?>
                    <tr>
                        <td><strong><?php echo esc_html($tipo->codigo); ?></strong></td>
                        <td><?php echo esc_html($tipo->nombre); ?></td>
                        <td><?php echo esc_html(wp_trim_words($tipo->descripcion, 10)); ?></td>
                        <td>
                            <span class="badge <?php echo $tipo->es_estandar ? 'estandar' : 'personalizado'; ?>">
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
                            <button type="button" class="button button-small" 
                                    onclick="editTipo(<?php echo htmlspecialchars(json_encode($tipo)); ?>)">
                                Editar
                            </button>
                            
                            <?php if (!$tipo->es_estandar): ?>
                                <button type="button" class="button button-small button-link-delete" 
                                        onclick="deleteTipo(<?php echo $tipo->id; ?>, '<?php echo esc_js($tipo->nombre); ?>')">
                                    Eliminar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="tipoModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Tipo de Contrato</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <form id="tipoForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="tipoId">
            
            <div class="modal-body">
                <table class="form-table">
                    <tr>
                        <th><label for="codigo">Código *</label></th>
                        <td>
                            <input type="text" id="codigo" name="codigo" required maxlength="10" class="regular-text">
                            <p class="description">Código único (máximo 10 caracteres)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nombre">Nombre *</label></th>
                        <td><input type="text" id="nombre" name="nombre" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="descripcion">Descripción</label></th>
                        <td><textarea id="descripcion" name="descripcion" rows="3" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td><label><input type="checkbox" id="activo" name="activo" checked> Activo</label></td>
                    </tr>
                </table>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="button" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="button button-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<style>
.stat-card {
    text-align: center;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    color: #666;
    margin-top: 5px;
}

.badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.badge.estandar {
    background: #d4edda;
    color: #155724;
}

.badge.personalizado {
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

.modal-overlay {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 90%;
    max-width: 600px;
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
</style>

<script>
jQuery(document).ready(function($) {
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
        if (confirm('¿Estás seguro de cambiar el estado?')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="toggle_status">')
                .append('<input type="hidden" name="id" value="' + id + '">')
                .append('<input type="hidden" name="activo" value="' + (activo ? 1 : 0) + '">');
            
            $('body').append(form);
            form.submit();
        } else {
            $('input[onchange*="toggleStatus(' + id + ')"]').prop('checked', !activo);
        }
    };
    
    window.deleteTipo = function(id, nombre) {
        if (confirm('¿Eliminar "' + nombre + '"?')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="delete">')
                .append('<input type="hidden" name="id" value="' + id + '">');
            
            $('body').append(form);
            form.submit();
        }
    };
    
    $(window).click(function(event) {
        if (event.target.id === 'tipoModal') {
            closeModal();
        }
    });
});
</script>