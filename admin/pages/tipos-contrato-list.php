<?php
/**
 * Página de Gestión de Tipos de Contrato
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
$tipo_id = intval($_GET['id'] ?? intval($_GET['tipo_id'] ?? 0));
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_tipo']) || (isset($_POST['nombre']) && !empty($_POST['nombre'])))) {
    $data = array(
        'codigo' => sanitize_text_field($_POST['codigo']),
        'nombre' => sanitize_text_field($_POST['nombre']),
        'descripcion' => sanitize_textarea_field($_POST['descripcion']),
        'activo' => isset($_POST['activo']) ? 1 : 0
    );
    
    if ($tipo_id > 0) {
        $result = $wpdb->update(
            $wpdb->prefix . 'tipos_contrato_minfin',
            $data,
            array('id' => $tipo_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
        $message = 'Tipo de contrato actualizado correctamente';
    } else {
        $data['fecha_creacion'] = current_time('mysql');
        $result = $wpdb->insert(
            $wpdb->prefix . 'tipos_contrato_minfin',
            $data,
            array('%s', '%s', '%s', '%d', '%s')
        );
        $message = 'Tipo de contrato creado correctamente';
    }
    
    if ($result !== false) {
        $message_type = 'success';
        $action = '';
        $tipo_id = 0;
    } else {
        $message = 'Error al guardar el tipo de contrato';
        $message_type = 'error';
    }
}

if ($action === 'delete' && $tipo_id > 0) {
    // Verificar si tiene vacantes asociadas
    $vacantes_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE tipo_contrato_id = %d",
        $tipo_id
    ));
    
    if ($vacantes_count > 0) {
        $message = 'No se puede eliminar el tipo de contrato porque tiene vacantes asociadas';
        $message_type = 'error';
    } else {
        $result = $wpdb->delete(
            $wpdb->prefix . 'tipos_contrato_minfin',
            array('id' => $tipo_id),
            array('%d')
        );
        
        if ($result !== false) {
            $message = 'Tipo de contrato eliminado correctamente';
            $message_type = 'success';
        } else {
            $message = 'Error al eliminar el tipo de contrato';
            $message_type = 'error';
        }
    }
    $action = '';
}

// Función para cargar datos de tipos de contrato
function cargar_datos_tipos_contrato() {
    global $wpdb;
    
    // Obtener tipos de contrato directamente de la base de datos
    $search = sanitize_text_field($_GET['s'] ?? '');
    $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
    
    // Paginación
    $items_per_page = 10;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $items_per_page;

    $where_conditions = array();
    $query_params = array();

    if (!empty($search)) {
        $where_conditions[] = "(tc.codigo LIKE %s OR tc.nombre LIKE %s OR tc.descripcion LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $query_params[] = $search_term;
        $query_params[] = $search_term;
        $query_params[] = $search_term;
    }

    if (!empty($estado_filtro)) {
        if ($estado_filtro === 'activo') {
            $where_conditions[] = "tc.activo = 1";
        } elseif ($estado_filtro === 'inactivo') {
            $where_conditions[] = "tc.activo = 0";
        }
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Contar total de registros
    $count_sql = "SELECT COUNT(DISTINCT tc.id) 
                  FROM {$wpdb->prefix}tipos_contrato_minfin tc
                  {$where_clause}";

    if (!empty($query_params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
    } else {
        $total_items = $wpdb->get_var($count_sql);
    }

    // Consulta con paginación
    $sql = "SELECT tc.*, 
                   COUNT(v.id) as total_vacantes
            FROM {$wpdb->prefix}tipos_contrato_minfin tc
            LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON tc.id = v.tipo_contrato_id
            {$where_clause}
            GROUP BY tc.id
            ORDER BY tc.nombre ASC
            LIMIT %d OFFSET %d";

    $query_params[] = $items_per_page;
    $query_params[] = $offset;

    if (!empty($query_params)) {
        $tipos_contrato = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    } else {
        $tipos_contrato = $wpdb->get_results($sql);
    }

    // Obtener estadísticas
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos,
        (SELECT COUNT(DISTINCT tipo_contrato_id) FROM {$wpdb->prefix}vacantes_minfin WHERE tipo_contrato_id IS NOT NULL) as con_vacantes
    FROM {$wpdb->prefix}tipos_contrato_minfin";

    $stats = $wpdb->get_row($stats_sql, ARRAY_A);
    
    return array(
        'tipos_contrato' => $tipos_contrato,
        'total_tipos' => $total_items,
        'stats' => $stats,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_pages' => ceil($total_items / $items_per_page)
    );
}

// Cargar datos después del procesamiento
$datos = cargar_datos_tipos_contrato();
$tipos_contrato = $datos['tipos_contrato'];
$total_tipos = $datos['total_tipos'];
$stats = $datos['stats'];

// Variables para el formulario de búsqueda
$search = sanitize_text_field($_GET['s'] ?? '');
$estado_filtro = sanitize_text_field($_GET['estado'] ?? '');

// Obtener datos para formulario
$tipo = null;
if ($action === 'edit' && $tipo_id > 0) {
    $tipo = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tipos_contrato_minfin WHERE id = %d",
        $tipo_id
    ));
}
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-file-contract me-3"></i>Gestión de Tipos de Contrato</h1>
            <p class="subtitle">Administra los tipos de contrato disponibles para las vacantes</p>
        </div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="?page=tipos-contrato-list&action=add" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Nuevo Tipo de Contrato
        </a>
        <?php endif; ?>
    </div>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'error' ? 'error' : 'success'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Formulario de Tipo de Contrato -->
    <div class="form-card">
        <div class="card-header">
            <h3><i class="fas fa-<?php echo $action === 'edit' ? 'edit' : 'plus'; ?> me-2"></i><?php echo $action === 'edit' ? 'Editar Tipo de Contrato' : 'Nuevo Tipo de Contrato'; ?></h3>
        </div>
        <div class="card-body">
            <form method="post" class="tipo-form">
                <input type="hidden" name="save_tipo" value="1">
                <?php if ($action === 'edit' && $tipo_id > 0): ?>
                <input type="hidden" name="tipo_id" value="<?php echo $tipo_id; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código del Tipo de Contrato *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                   value="<?php echo esc_attr($tipo->codigo ?? ''); ?>" required maxlength="20">
                            <div class="form-text">Ejemplo: 021, 031, etc.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tipo de Contrato *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo esc_attr($tipo->nombre ?? ''); ?>" required maxlength="100">
                            <div class="form-text">Ejemplo: Contrato por Servicios Profesionales</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"
                                      placeholder="Descripción detallada del tipo de contrato..."><?php echo esc_textarea($tipo->descripcion ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Campos simplificados - solo los que existen en la tabla -->
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Configuración</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                           <?php checked($tipo->activo ?? 1, 1); ?>>
                                    <label class="form-check-label" for="activo">
                                        Tipo de Contrato Activo
                                    </label>
                                    <div class="form-text">Solo los tipos activos aparecerán en los formularios</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Información</h5>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    <span class="small text-muted">Los tipos activos aparecen en los formularios de vacantes</span>
                                </div>
                                <div class="info-item mt-2">
                                    <i class="fas fa-file-contract text-info me-2"></i>
                                    <span class="small text-muted">Define los diferentes tipos de contratación disponibles</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="save_tipo" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="?page=tipos-contrato-list" class="btn btn-secondary">
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
                    <i class="fas fa-file-contract fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['total'] ?? 0); ?></div>
                <div class="label">Total Tipos</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-success">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['activos'] ?? 0); ?></div>
                <div class="label">Activos</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-warning">
                <div class="icon-wrapper">
                    <i class="fas fa-pause-circle fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['inactivos'] ?? 0); ?></div>
                <div class="label">Inactivos</div>
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

    <!-- Tabla de Tipos de Contrato Mejorada -->
    <div class="data-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list me-2"></i>Lista de Tipos de Contrato (<?php echo $total_tipos; ?>)</h5>
            
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="tipos-contrato-list">
                <div class="input-group">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Buscar tipos de contrato..." class="form-control">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?php selected($estado_filtro, 'activo'); ?>>Activos</option>
                        <option value="inactivo" <?php selected($estado_filtro, 'inactivo'); ?>>Inactivos</option>
                    </select>
                    <button type="submit" class="btn btn-outline-light">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tipo de Contrato</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-center">Vacantes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos_contrato)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay tipos de contrato registrados</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tipos_contrato as $tipo): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-file-contract text-primary"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?php echo esc_html($tipo->nombre); ?></div>
                                    <div class="small text-primary">Código: <?php echo esc_html($tipo->codigo); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($tipo->descripcion): ?>
                            <div class="small text-muted">
                                <?php echo esc_html(wp_trim_words($tipo->descripcion, 15)); ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted fst-italic">Sin descripción</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tipo->activo): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fas fa-check-circle me-1"></i>Activo
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <i class="fas fa-pause-circle me-1"></i>Inactivo
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column align-items-center">
                                <span class="badge bg-info-subtle text-info border border-info-subtle fs-6">
                                    <?php echo intval($tipo->total_vacantes ?? 0); ?>
                                </span>
                                <small class="text-muted">vacantes</small>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="?page=tipos-contrato-list&action=edit&id=<?php echo $tipo->id; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar tipo de contrato">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?page=vacantes-list&tipo_contrato_id=<?php echo $tipo->id; ?>" 
                                   class="btn btn-sm btn-outline-info" title="Ver vacantes">
                                    <i class="fas fa-briefcase"></i>
                                </a>
                                <?php if (intval($tipo->total_vacantes) === 0): ?>
                                <a href="?page=tipos-contrato-list&action=delete&id=<?php echo $tipo->id; ?>" 
                                   class="btn btn-sm btn-outline-danger" title="Eliminar tipo de contrato"
                                   onclick="return confirm('⚠️ ¿Está seguro de eliminar este tipo de contrato?\n\nEsta acción no se puede deshacer.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger" disabled title="No se puede eliminar: tiene vacantes asociadas">
                                    <i class="fas fa-lock"></i>
                                </button>
                                <?php endif; ?>
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
                    <?php echo min($datos['current_page'] * $datos['items_per_page'], $datos['total_tipos']); ?> 
                    de <?php echo $datos['total_tipos']; ?> tipos de contrato
                </div>
                <nav aria-label="Paginación de tipos de contrato">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($datos['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=tipos-contrato-list&paged=<?php echo $datos['current_page'] - 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $datos['current_page'] - 2); $i <= min($datos['total_pages'], $datos['current_page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $datos['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=tipos-contrato-list&paged=<?php echo $i; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>" style="<?php echo $i == $datos['current_page'] ? 'color: white !important;' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($datos['current_page'] < $datos['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=tipos-contrato-list&paged=<?php echo $datos['current_page'] + 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
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
            
            const tipoNombre = this.closest('tr').querySelector('.fw-bold').textContent;
            
            let mensaje = `🗑️ ¿Está seguro de eliminar el tipo de contrato "${tipoNombre}"?`;
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
    
    // Validación del formulario
    const form = document.querySelector('.tipo-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const codigo = document.getElementById('codigo').value.trim();
            const nombre = document.getElementById('nombre').value.trim();
            
            if (!codigo) {
                e.preventDefault();
                alert('⚠️ El código del tipo de contrato es obligatorio.');
                document.getElementById('codigo').focus();
                return false;
            }
            
            if (!nombre) {
                e.preventDefault();
                alert('⚠️ El nombre del tipo de contrato es obligatorio.');
                document.getElementById('nombre').focus();
                return false;
            }
            
            // Mostrar indicador de carga en el botón
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            submitBtn.disabled = true;
        });
    }
    
    // Tooltip para botones deshabilitados
    const disabledButtons = document.querySelectorAll('.btn:disabled');
    disabledButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            // Crear tooltip simple
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.title;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            
            this.addEventListener('mouseleave', function() {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, { once: true });
        });
    });
});
</script>