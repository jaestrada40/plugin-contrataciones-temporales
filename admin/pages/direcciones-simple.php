<?php
/**
 * Página simplificada de Direcciones
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
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activa' => isset($_POST['activa']) ? 1 : 0
            );
            
            // Verificar nombre único
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vs_direcciones WHERE nombre = %s",
                $data['nombre']
            ));
            
            if ($exists > 0) {
                $error = 'Ya existe una dirección con ese nombre.';
            } else {
                $result = $wpdb->insert("{$wpdb->prefix}vs_direcciones", $data);
                if ($result !== false) {
                    $message = 'Dirección creada exitosamente.';
                } else {
                    $error = 'Error al crear la dirección.';
                }
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $data = array(
                'nombre' => sanitize_text_field($_POST['nombre']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'activa' => isset($_POST['activa']) ? 1 : 0
            );
            
            $result = $wpdb->update(
                "{$wpdb->prefix}vs_direcciones",
                $data,
                array('id' => $id)
            );
            
            if ($result !== false) {
                $message = 'Dirección actualizada exitosamente.';
            } else {
                $error = 'Error al actualizar la dirección.';
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si tiene vacantes
            $vacantes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vs_vacantes WHERE direccion_id = %d",
                $id
            ));
            
            if ($vacantes > 0) {
                $error = 'No se puede eliminar una dirección que tiene vacantes asociadas.';
            } else {
                $result = $wpdb->delete(
                    "{$wpdb->prefix}vs_direcciones",
                    array('id' => $id)
                );
                
                if ($result !== false) {
                    $message = 'Dirección eliminada exitosamente.';
                } else {
                    $error = 'Error al eliminar la dirección.';
                }
            }
            break;
            
        case 'toggle_status':
            $id = intval($_POST['id']);
            $activa = intval($_POST['activa']);
            
            $result = $wpdb->update(
                "{$wpdb->prefix}vs_direcciones",
                array('activa' => $activa),
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
$filter_activa = $_GET['filter_activa'] ?? '';

$sql = "SELECT d.*, 
               (SELECT COUNT(*) FROM {$wpdb->prefix}vs_vacantes v WHERE v.direccion_id = d.id) as total_vacantes
        FROM {$wpdb->prefix}vs_direcciones d WHERE 1=1";
$params = array();

if ($search) {
    $sql .= " AND (d.nombre LIKE %s OR d.descripcion LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter_activa !== '') {
    $sql .= " AND d.activa = %d";
    $params[] = intval($filter_activa);
}

$sql .= " ORDER BY d.nombre ASC";

if (!empty($params)) {
    $direcciones = $wpdb->get_results($wpdb->prepare($sql, $params));
} else {
    $direcciones = $wpdb->get_results($sql);
}

// Estadísticas
$stats = array(
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_direcciones"),
    'activas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_direcciones WHERE activa = 1"),
    'inactivas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vs_direcciones WHERE activa = 0"),
    'con_vacantes' => $wpdb->get_var("SELECT COUNT(DISTINCT d.id) FROM {$wpdb->prefix}vs_direcciones d INNER JOIN {$wpdb->prefix}vs_vacantes v ON d.id = v.direccion_id")
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-building"></span>
        Direcciones
    </h1>
    
    <a href="#" class="page-title-action" onclick="openCreateModal()">Agregar Nueva</a>
    
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
                            <div class="stat-label">Total de Direcciones</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['activas']; ?></div>
                            <div class="stat-label">Activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['inactivas']; ?></div>
                            <div class="stat-label">Inactivas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['con_vacantes']; ?></div>
                            <div class="stat-label">Con Vacantes</div>
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
                <input type="hidden" name="page" value="direcciones-list">
                
                <div>
                    <label>Buscar:</label><br>
                    <input type="text" name="search" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Nombre o descripción..." class="regular-text">
                </div>
                
                <div>
                    <label>Estado:</label><br>
                    <select name="filter_activa">
                        <option value="">Todos</option>
                        <option value="1" <?php selected($filter_activa, '1'); ?>>Activas</option>
                        <option value="0" <?php selected($filter_activa, '0'); ?>>Inactivas</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="button">Filtrar</button>
                    <a href="<?php echo admin_url('admin.php?page=direcciones-list'); ?>" class="button">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Vacantes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($direcciones)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <div>
                            <span class="dashicons dashicons-building" style="font-size: 48px; color: #ddd;"></span>
                            <p>No se encontraron direcciones.</p>
                            <a href="#" class="button button-primary" onclick="openCreateModal()">Crear la primera dirección</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($direcciones as $direccion): ?>
                    <tr>
                        <td><strong><?php echo esc_html($direccion->nombre); ?></strong></td>
                        <td><?php echo esc_html(wp_trim_words($direccion->descripcion, 10)); ?></td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" 
                                       <?php checked($direccion->activa, 1); ?>
                                       onchange="toggleStatus(<?php echo $direccion->id; ?>, this.checked)">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <span class="badge"><?php echo $direccion->total_vacantes ?? 0; ?></span>
                        </td>
                        <td>
                            <button type="button" class="button button-small" 
                                    onclick="editDireccion(<?php echo htmlspecialchars(json_encode($direccion)); ?>)">
                                Editar
                            </button>
                            
                            <button type="button" class="button button-small button-link-delete" 
                                    onclick="deleteDireccion(<?php echo $direccion->id; ?>, '<?php echo esc_js($direccion->nombre); ?>')">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="direccionModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nueva Dirección</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <form id="direccionForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="direccionId">
            
            <div class="modal-body">
                <table class="form-table">
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
                        <td><label><input type="checkbox" id="activa" name="activa" checked> Activa</label></td>
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
    background: #0073aa;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
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
        $('#modalTitle').text('Nueva Dirección');
        $('#formAction').val('create');
        $('#direccionId').val('');
        $('#direccionForm')[0].reset();
        $('#activa').prop('checked', true);
        $('#direccionModal').show();
    };
    
    window.editDireccion = function(direccion) {
        $('#modalTitle').text('Editar Dirección');
        $('#formAction').val('update');
        $('#direccionId').val(direccion.id);
        $('#nombre').val(direccion.nombre);
        $('#descripcion').val(direccion.descripcion);
        $('#activa').prop('checked', direccion.activa == 1);
        $('#direccionModal').show();
    };
    
    window.closeModal = function() {
        $('#direccionModal').hide();
    };
    
    window.toggleStatus = function(id, activa) {
        if (confirm('¿Estás seguro de cambiar el estado?')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="toggle_status">')
                .append('<input type="hidden" name="id" value="' + id + '">')
                .append('<input type="hidden" name="activa" value="' + (activa ? 1 : 0) + '">');
            
            $('body').append(form);
            form.submit();
        } else {
            $('input[onchange*="toggleStatus(' + id + ')"]').prop('checked', !activa);
        }
    };
    
    window.deleteDireccion = function(id, nombre) {
        if (confirm('¿Eliminar "' + nombre + '"?')) {
            var form = $('<form method="POST">')
                .append('<input type="hidden" name="action" value="delete">')
                .append('<input type="hidden" name="id" value="' + id + '">');
            
            $('body').append(form);
            form.submit();
        }
    };
    
    $(window).click(function(event) {
        if (event.target.id === 'direccionModal') {
            closeModal();
        }
    });
});
</script>