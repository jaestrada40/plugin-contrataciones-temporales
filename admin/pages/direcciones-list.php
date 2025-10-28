<?php
/**
 * Página de Gestión de Direcciones
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

// Procesar acciones
$action = $_GET['action'] ?? '';
$direccion_id = intval($_GET['id'] ?? intval($_GET['direccion_id'] ?? 0));
$message = '';
$message_type = '';

if ($_POST && isset($_POST['save_direccion'])) {
    $data = array(
        'nombre' => sanitize_text_field($_POST['nombre']),
        'descripcion' => sanitize_textarea_field($_POST['descripcion']),
        'responsable' => sanitize_text_field($_POST['responsable']),
        'email_contacto' => sanitize_email($_POST['email_contacto']),
        'telefono' => sanitize_text_field($_POST['telefono']),
        'direccion_fisica' => sanitize_textarea_field($_POST['direccion_fisica']),
        'icono_url' => sanitize_text_field($_POST['icono_url']),
        'activa' => isset($_POST['activa']) ? 1 : 0
    );
    
    if ($direccion_id > 0) {
        $result = $wpdb->update(
            $wpdb->prefix . 'direcciones_minfin',
            $data,
            array('id' => $direccion_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
            array('%d')
        );
        $message = 'Dirección actualizada correctamente';
    } else {
        $data['fecha_creacion'] = current_time('mysql');
        $result = $wpdb->insert(
            $wpdb->prefix . 'direcciones_minfin',
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        $message = 'Dirección creada correctamente';
    }
    
    if ($result !== false) {
        $message_type = 'success';
        $action = '';
        $direccion_id = 0;
    } else {
        $message = 'Error al guardar la dirección';
        $message_type = 'error';
    }
}

if ($action === 'delete' && $direccion_id > 0) {
    $result = $wpdb->delete(
        $wpdb->prefix . 'direcciones_minfin',
        array('id' => $direccion_id),
        array('%d')
    );
    
    if ($result !== false) {
        $message = 'Dirección eliminada correctamente';
        $message_type = 'success';
    } else {
        $message = 'Error al eliminar la dirección';
        $message_type = 'error';
    }
    $action = '';
}

// Función para cargar datos de direcciones
function cargar_datos_direcciones() {
    global $wpdb;
    
    // Obtener direcciones directamente de la base de datos
    $search = sanitize_text_field($_GET['s'] ?? '');
    $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
    
    // Paginación
    $items_per_page = 10;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $items_per_page;

    $where_conditions = array();
    $query_params = array();

    if (!empty($search)) {
        $where_conditions[] = "(d.nombre LIKE %s OR d.descripcion LIKE %s OR d.responsable LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $query_params[] = $search_term;
        $query_params[] = $search_term;
        $query_params[] = $search_term;
    }

    if (!empty($estado_filtro)) {
        if ($estado_filtro === 'activa') {
            $where_conditions[] = "d.activa = 1";
        } elseif ($estado_filtro === 'inactiva') {
            $where_conditions[] = "d.activa = 0";
        }
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Contar total de registros
    $count_sql = "SELECT COUNT(DISTINCT d.id) 
                  FROM {$wpdb->prefix}direcciones_minfin d
                  {$where_clause}";

    if (!empty($query_params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
    } else {
        $total_items = $wpdb->get_var($count_sql);
    }

    // Consulta con paginación
    $sql = "SELECT d.*, 
                   COUNT(v.id) as total_vacantes
            FROM {$wpdb->prefix}direcciones_minfin d
            LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id
            {$where_clause}
            GROUP BY d.id
            ORDER BY d.nombre ASC
            LIMIT %d OFFSET %d";

    $query_params[] = $items_per_page;
    $query_params[] = $offset;

    if (!empty($query_params)) {
        $direcciones = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    } else {
        $direcciones = $wpdb->get_results($sql);
    }

    // Obtener estadísticas
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN activa = 1 THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN activa = 0 THEN 1 ELSE 0 END) as inactivas,
        (SELECT COUNT(DISTINCT direccion_id) FROM {$wpdb->prefix}vacantes_minfin WHERE direccion_id IS NOT NULL) as con_vacantes
    FROM {$wpdb->prefix}direcciones_minfin";

    $stats = $wpdb->get_row($stats_sql, ARRAY_A);
    
    return array(
        'direcciones' => $direcciones,
        'total_direcciones' => $total_items,
        'stats' => $stats,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_pages' => ceil($total_items / $items_per_page)
    );
}

// Cargar datos después del procesamiento
$datos = cargar_datos_direcciones();
$direcciones = $datos['direcciones'];
$total_direcciones = $datos['total_direcciones'];
$stats = $datos['stats'];

// Obtener datos para formulario
$direccion = null;
if ($action === 'edit' && $direccion_id > 0) {
    $direccion = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}direcciones_minfin WHERE id = %d",
        $direccion_id
    ));
}
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-building me-3"></i>Gestión de Direcciones</h1>
            <p class="subtitle">Administra las direcciones organizacionales del MINFIN</p>
        </div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="?page=direcciones-list&action=add" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Nueva Dirección
        </a>
        <?php endif; ?>
    </div>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'error' ? 'error' : 'success'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Formulario de Dirección -->
    <div class="form-card">
        <div class="card-header">
            <h3><i class="fas fa-<?php echo $action === 'edit' ? 'edit' : 'plus'; ?> me-2"></i><?php echo $action === 'edit' ? 'Editar Dirección' : 'Nueva Dirección'; ?></h3>
        </div>
        <div class="card-body">
            <form method="post" class="direccion-form">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Dirección *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo esc_attr($direccion->nombre ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo esc_textarea($direccion->descripcion ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="responsable" class="form-label">Responsable</label>
                                    <input type="text" class="form-control" id="responsable" name="responsable" 
                                           value="<?php echo esc_attr($direccion->responsable ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email_contacto" class="form-label">Email de Contacto</label>
                                    <input type="email" class="form-control" id="email_contacto" name="email_contacto" 
                                           value="<?php echo esc_attr($direccion->email_contacto ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" 
                                           value="<?php echo esc_attr($direccion->telefono ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icono_url" class="form-label">Icono (Clase CSS)</label>
                                    <input type="text" class="form-control" id="icono_url" name="icono_url" 
                                           value="<?php echo esc_attr($direccion->icono_url ?? 'fas fa-building'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion_fisica" class="form-label">Dirección Física</label>
                            <textarea class="form-control" id="direccion_fisica" name="direccion_fisica" rows="3"><?php echo esc_textarea($direccion->direccion_fisica ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Configuración</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activa" name="activa" 
                                           <?php checked($direccion->activa ?? 1, 1); ?>>
                                    <label class="form-check-label" for="activa">
                                        Dirección Activa
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="save_direccion" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="?page=direcciones-list" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Estadísticas Mejoradas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-primary">
                <div class="icon-wrapper">
                    <i class="fas fa-building fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['total'] ?? 0); ?></div>
                <div class="label">Total Direcciones</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-success">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['activas'] ?? 0); ?></div>
                <div class="label">Activas</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-warning">
                <div class="icon-wrapper">
                    <i class="fas fa-pause-circle fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['inactivas'] ?? 0); ?></div>
                <div class="label">Inactivas</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-info">
                <div class="icon-wrapper">
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['con_vacantes'] ?? 0); ?></div>
                <div class="label">Con Vacantes</div>
            </div>
        </div>
    </div>
    <!-- Tabla de Direcciones Mejorada -->
    <div class="data-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list me-2"></i>Lista de Direcciones (<?php echo $total_direcciones; ?>)</h5>
            
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="direcciones-list">
                <div class="input-group">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Buscar direcciones..." class="form-control">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="activa" <?php selected($estado_filtro, 'activa'); ?>>Activas</option>
                        <option value="inactiva" <?php selected($estado_filtro, 'inactiva'); ?>>Inactivas</option>
                    </select>
                    <button type="submit" class="btn btn-outline-light">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dirección</th>
                        <th>Responsable</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Vacantes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($direcciones)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay direcciones registradas</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($direcciones as $dir): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="<?php echo esc_attr($dir->icono_url ?: 'fas fa-building'); ?> text-primary"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?php echo esc_html($dir->nombre); ?></div>
                                    <?php if ($dir->descripcion): ?>
                                    <div class="small text-muted mt-1">
                                        <?php echo esc_html(wp_trim_words($dir->descripcion, 12)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($dir->responsable): ?>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user text-muted me-2"></i>
                                <span class="fw-medium"><?php echo esc_html($dir->responsable); ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-muted fst-italic">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <?php if ($dir->email_contacto): ?>
                                <div class="mb-1">
                                    <i class="fas fa-envelope text-muted me-2"></i>
                                    <a href="mailto:<?php echo esc_attr($dir->email_contacto); ?>" class="text-decoration-none">
                                        <?php echo esc_html($dir->email_contacto); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($dir->telefono): ?>
                                <div class="small text-muted">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <?php echo esc_html($dir->telefono); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!$dir->email_contacto && !$dir->telefono): ?>
                                <span class="text-muted fst-italic">Sin contacto</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($dir->activa): ?>
                            <span class="badge bg-success rounded-pill">
                                <i class="fas fa-check-circle me-1"></i>Activa
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary rounded-pill">
                                <i class="fas fa-pause-circle me-1"></i>Inactiva
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info rounded-pill fs-6">
                                <?php echo intval($dir->total_vacantes ?? 0); ?>
                            </span>
                            <div class="small text-muted">vacantes</div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="?page=direcciones-list&action=edit&id=<?php echo $dir->id; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar dirección">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?page=vacantes-list&direccion_id=<?php echo $dir->id; ?>" 
                                   class="btn btn-sm btn-outline-info" title="Ver vacantes">
                                    <i class="fas fa-briefcase"></i>
                                </a>
                                <a href="?page=direcciones-list&action=delete&id=<?php echo $dir->id; ?>" 
                                   class="btn btn-sm btn-outline-danger" title="Eliminar dirección"
                                   onclick="return confirm('⚠️ ¿Está seguro de eliminar esta dirección?\n\nEsta acción eliminará también todas las vacantes asociadas y no se puede deshacer.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($datos['total_pages'] > 1): ?>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Mostrando <?php echo (($datos['current_page'] - 1) * $datos['items_per_page']) + 1; ?> - 
                    <?php echo min($datos['current_page'] * $datos['items_per_page'], $datos['total_direcciones']); ?> 
                    de <?php echo $datos['total_direcciones']; ?> direcciones
                </div>
                <nav aria-label="Paginación de direcciones">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($datos['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=direcciones-list&paged=<?php echo $datos['current_page'] - 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $datos['current_page'] - 2); $i <= min($datos['total_pages'], $datos['current_page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $datos['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=direcciones-list&paged=<?php echo $i; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>" style="<?php echo $i == $datos['current_page'] ? 'color: white !important;' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($datos['current_page'] < $datos['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=direcciones-list&paged=<?php echo $datos['current_page'] + 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Los estilos se cargan desde modern-admin.css -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit en filtros con animación
    const filtros = document.querySelectorAll('select[name="estado"]');
    filtros.forEach(filtro => {
        filtro.addEventListener('change', function() {
            // Mostrar indicador de carga
            const btn = this.form.querySelector('button[type="submit"]');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            // Submit después de un pequeño delay para mostrar la animación
            setTimeout(() => {
                this.form.submit();
            }, 300);
        });
    });
    
    // Animación de hover en las tarjetas de estadísticas
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Confirmación mejorada para eliminación
    const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const direccionNombre = this.closest('tr').querySelector('.fw-bold').textContent;
            const totalVacantes = this.closest('tr').querySelector('.badge.bg-info').textContent;
            
            let mensaje = `🗑️ ¿Está seguro de eliminar la dirección "${direccionNombre}"?`;
            
            if (parseInt(totalVacantes) > 0) {
                mensaje += `\n\n⚠️ Esta dirección tiene ${totalVacantes} vacante(s) asociada(s) que también serán eliminadas.`;
            }
            
            mensaje += '\n\n❌ Esta acción no se puede deshacer.';
            
            if (confirm(mensaje)) {
                // Mostrar indicador de carga
                const icon = this.querySelector('i');
                icon.className = 'fas fa-spinner fa-spin';
                this.style.pointerEvents = 'none';
                
                window.location.href = this.href;
            }
        });
    });
    
    // Búsqueda en tiempo real (opcional)
    const searchInput = document.querySelector('input[name="s"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 1000);
        });
    }
    
    // Animación de aparición para las filas de la tabla
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>