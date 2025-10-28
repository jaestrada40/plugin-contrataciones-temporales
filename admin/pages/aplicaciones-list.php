<?php
/**
 * Página de Gestión de Aplicaciones
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
$aplicacion_id = intval($_GET['aplicacion_id'] ?? 0);
$message = '';
$message_type = '';

if ($_POST && isset($_POST['update_estado'])) {
    $nuevo_estado = sanitize_text_field($_POST['estado']);
    $comentarios = sanitize_textarea_field($_POST['comentarios']);
    
    $result = $wpdb->update(
        $wpdb->prefix . 'aplicaciones_minfin',
        array(
            'estado' => $nuevo_estado,
            'comentarios_admin' => $comentarios
        ),
        array('id' => $aplicacion_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        $message = 'Estado de aplicación actualizado correctamente';
        $message_type = 'success';
    } else {
        $message = 'Error al actualizar el estado';
        $message_type = 'error';
    }
    $action = '';
}

// Función para cargar datos de aplicaciones
function cargar_datos_aplicaciones() {
    global $wpdb;
    
    // Obtener aplicaciones directamente de la base de datos
    $search = sanitize_text_field($_GET['s'] ?? '');
    $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
    
    // Paginación
    $items_per_page = 10;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $items_per_page;

    $where_conditions = array();
    $query_params = array();

    if (!empty($search)) {
        $where_conditions[] = "(CONCAT(a.nombres, ' ', a.apellidos) LIKE %s OR a.email LIKE %s OR a.telefono LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $query_params[] = $search_term;
        $query_params[] = $search_term;
        $query_params[] = $search_term;
    }

    if (!empty($estado_filtro)) {
        $where_conditions[] = "a.estado = %s";
        $query_params[] = $estado_filtro;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Contar total de registros
    $count_sql = "SELECT COUNT(*) 
                  FROM {$wpdb->prefix}aplicaciones_minfin a
                  LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id  
                  LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                  {$where_clause}";

    if (!empty($query_params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
    } else {
        $total_items = $wpdb->get_var($count_sql);
    }

    // Consulta con paginación
    $sql = "SELECT a.*, 
                   CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                   v.titulo as vacante_titulo,
                   v.codigo as vacante_codigo,
                   d.nombre as direccion_nombre
            FROM {$wpdb->prefix}aplicaciones_minfin a
            LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id  
            LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
            {$where_clause}
            ORDER BY a.fecha_aplicacion DESC
            LIMIT %d OFFSET %d";

    $query_params[] = $items_per_page;
    $query_params[] = $offset;

    if (!empty($query_params)) {
        $aplicaciones = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    } else {
        $aplicaciones = $wpdb->get_results($sql);
    }

    // Obtener estadísticas
    $stats_sql = "SELECT 
        SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'Revisado' THEN 1 ELSE 0 END) as revisadas,  
        SUM(CASE WHEN estado = 'Aceptado' THEN 1 ELSE 0 END) as aceptadas,
        SUM(CASE WHEN estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazadas,
        COUNT(*) as total
    FROM {$wpdb->prefix}aplicaciones_minfin";

    $stats = $wpdb->get_row($stats_sql, ARRAY_A);
    
    return array(
        'aplicaciones' => $aplicaciones,
        'total_aplicaciones' => $total_items,
        'stats' => $stats,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_pages' => ceil($total_items / $items_per_page)
    );
}

// Cargar datos después del procesamiento
$datos = cargar_datos_aplicaciones();
$aplicaciones = $datos['aplicaciones'];
$total_aplicaciones = $datos['total_aplicaciones'];
$stats = $datos['stats'];

// Vista detallada
$aplicacion = null;
if ($action === 'view' && $aplicacion_id > 0) {
    $aplicacion_sql = "SELECT a.*, 
                              CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                              v.titulo as vacante_titulo,
                              v.codigo as vacante_codigo,
                              d.nombre as direccion_nombre
                       FROM {$wpdb->prefix}aplicaciones_minfin a
                       LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id  
                       LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                       WHERE a.id = %d";
    
    $aplicacion = $wpdb->get_row($wpdb->prepare($aplicacion_sql, $aplicacion_id));
}
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-users me-3"></i>Gestión de Aplicaciones</h1>
            <p class="subtitle">Administra las aplicaciones recibidas para las vacantes</p>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'error' ? 'error' : 'success'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($action === 'view' && $aplicacion): ?>
    <!-- Vista Detallada de Aplicación -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Detalle de Aplicación #<?php echo $aplicacion->id; ?></h3>
            <a href="?page=aplicaciones-list" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4>Información del Candidato</h4>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td><?php echo esc_html($aplicacion->nombre_completo); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($aplicacion->email); ?>">
                                    <?php echo esc_html($aplicacion->email); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Teléfono:</strong></td>
                            <td><?php echo esc_html($aplicacion->telefono); ?></td>
                        </tr>
                        <tr>
                            <td><strong>DPI:</strong></td>
                            <td><?php echo esc_html($aplicacion->dpi); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de Aplicación:</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($aplicacion->fecha_aplicacion)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vacante:</strong></td>
                            <td><?php echo esc_html($aplicacion->vacante_titulo); ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($aplicacion->carta_presentacion): ?>
                    <h5>Carta de Presentación</h5>
                    <div class="border p-3 bg-light">
                        <?php echo wp_kses_post(nl2br($aplicacion->carta_presentacion)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($aplicacion->comentarios_admin): ?>
                    <h5 class="mt-4">Comentarios Administrativos</h5>
                    <div class="border p-3 bg-warning bg-opacity-10">
                        <?php echo wp_kses_post(nl2br($aplicacion->comentarios_admin)); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Estado Actual</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $badge_class = array(
                                'Pendiente' => 'warning',
                                'Revisado' => 'info',
                                'Aceptado' => 'success',
                                'Rechazado' => 'danger'
                            );
                            $class = $badge_class[$aplicacion->estado] ?? 'secondary';
                            ?>
                            <div class="text-center mb-3">
                                <span class="badge bg-<?php echo $class; ?> fs-6">
                                    <?php echo esc_html($aplicacion->estado); ?>
                                </span>
                            </div>
                            
                            <form method="post">
                                <input type="hidden" name="aplicacion_id" value="<?php echo $aplicacion->id; ?>">
                                
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Cambiar Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="Pendiente" <?php selected($aplicacion->estado, 'Pendiente'); ?>>Pendiente</option>
                                        <option value="Revisado" <?php selected($aplicacion->estado, 'Revisado'); ?>>Revisado</option>
                                        <option value="Aceptado" <?php selected($aplicacion->estado, 'Aceptado'); ?>>Aceptado</option>
                                        <option value="Rechazado" <?php selected($aplicacion->estado, 'Rechazado'); ?>>Rechazado</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="comentarios" class="form-label">Comentarios</label>
                                    <textarea class="form-control" id="comentarios" name="comentarios" rows="4"
                                              placeholder="Agregar comentarios sobre esta aplicación..."><?php echo esc_textarea($aplicacion->comentarios_admin); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_estado" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Actualizar Estado
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($aplicacion->cv_url): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Curriculum Vitae</h5>
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                            <br>
                            <a href="<?php echo esc_url($aplicacion->cv_url); ?>" 
                               target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Descargar CV
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Estadísticas Mejoradas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-warning">
                <div class="icon-wrapper">
                    <i class="fas fa-clock fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['pendientes'] ?? 0); ?></div>
                <div class="label">Pendientes</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-info">
                <div class="icon-wrapper">
                    <i class="fas fa-eye fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['revisadas'] ?? 0); ?></div>
                <div class="label">Revisadas</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-success">
                <div class="icon-wrapper">
                    <i class="fas fa-check fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['aceptadas'] ?? 0); ?></div>
                <div class="label">Aceptadas</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stats-card stats-primary">
                <div class="icon-wrapper">
                    <i class="fas fa-times fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['rechazadas'] ?? 0); ?></div>
                <div class="label">Rechazadas</div>
            </div>
        </div>
    </div>

    <!-- Tabla de Aplicaciones Mejorada -->
    <div class="data-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list me-2"></i>Lista de Aplicaciones (<?php echo $total_aplicaciones; ?>)</h5>
            
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="aplicaciones-list">
                <div class="input-group">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Buscar aplicaciones..." class="form-control">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente" <?php selected($estado_filtro, 'Pendiente'); ?>>Pendiente</option>
                        <option value="Revisado" <?php selected($estado_filtro, 'Revisado'); ?>>Revisado</option>
                        <option value="Aceptado" <?php selected($estado_filtro, 'Aceptado'); ?>>Aceptado</option>
                        <option value="Rechazado" <?php selected($estado_filtro, 'Rechazado'); ?>>Rechazado</option>
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
                        <th>Candidato</th>
                        <th>Vacante</th>
                        <th>Fecha Aplicación</th>
                        <th>Estado</th>
                        <th>CV</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aplicaciones)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay aplicaciones registradas</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($aplicaciones as $app): ?>
                    <tr>
                        <td>
                            <div>
                                <strong><?php echo esc_html($app->nombre_completo); ?></strong>
                                <div class="small text-muted">
                                    <i class="fas fa-envelope"></i> <?php echo esc_html($app->email); ?>
                                </div>
                                <?php if ($app->telefono): ?>
                                <div class="small text-muted">
                                    <i class="fas fa-phone"></i> <?php echo esc_html($app->telefono); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <?php echo esc_html($app->vacante_titulo ?? 'N/A'); ?>
                            </div>
                            <div class="small text-muted">
                                <?php echo esc_html($app->direccion_nombre ?? ''); ?>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <?php echo date('d/m/Y', strtotime($app->fecha_aplicacion)); ?>
                            </div>
                            <div class="small text-muted">
                                <?php echo date('H:i', strtotime($app->fecha_aplicacion)); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $badge_class = array(
                                'Pendiente' => 'warning',
                                'Revisado' => 'info',
                                'Aceptado' => 'success',
                                'Rechazado' => 'danger'
                            );
                            $class = $badge_class[$app->estado] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>"><?php echo esc_html($app->estado); ?></span>
                        </td>
                        <td>
                            <?php if ($app->cv_url): ?>
                            <a href="<?php echo esc_url($app->cv_url); ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank" title="Descargar CV">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">Sin CV</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?page=aplicaciones-list&action=view&aplicacion_id=<?php echo $app->id; ?>" 
                                   class="btn btn-outline-primary" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($app->email): ?>
                                <button type="button" 
                                        class="btn btn-outline-info" 
                                        title="Enviar Email de Respuesta"
                                        onclick="mostrarModalEmail('<?php echo esc_js($app->email); ?>', '<?php echo esc_js($app->nombre_completo); ?>', '<?php echo esc_js($app->vacante_titulo ?? 'Vacante'); ?>')">
                                    <i class="fas fa-envelope"></i>
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
                    <?php echo min($datos['current_page'] * $datos['items_per_page'], $datos['total_aplicaciones']); ?> 
                    de <?php echo $datos['total_aplicaciones']; ?> aplicaciones
                </div>
                <nav aria-label="Paginación de aplicaciones">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($datos['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=aplicaciones-list&paged=<?php echo $datos['current_page'] - 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $datos['current_page'] - 2); $i <= min($datos['total_pages'], $datos['current_page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $datos['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=aplicaciones-list&paged=<?php echo $i; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>" style="<?php echo $i == $datos['current_page'] ? 'color: white !important;' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($datos['current_page'] < $datos['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=aplicaciones-list&paged=<?php echo $datos['current_page'] + 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?>">
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

<!-- Modal para Email -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">
                    <i class="fas fa-envelope me-2"></i>Enviar Email de Respuesta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Completa el formulario y haz clic en "Enviar Email" para enviar la respuesta directamente.
                </div>
                
                <form id="emailForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Para:</label>
                        <input type="email" class="form-control" id="emailPara" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asunto:</label>
                        <input type="text" class="form-control" id="emailAsunto" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mensaje:</label>
                        <textarea class="form-control" id="emailCuerpo" rows="10" required placeholder="Escribe tu mensaje aquí..."></textarea>
                    </div>
                </form>
                
                <div id="emailStatus" class="mt-3" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnEnviarEmail" onclick="enviarEmailDirecto()">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Email
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.empty-state {
    padding: 40px;
    text-align: center;
}

.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
}

.card .table:last-child {
    margin-bottom: 0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let emailData = {};

function mostrarModalEmail(email, nombreCompleto, vacanteTitle) {
    // Guardar datos del email
    emailData = {
        email: email,
        nombreCompleto: nombreCompleto,
        vacanteTitle: vacanteTitle
    };
    
    // Crear mensaje template
    const mensajeTemplate = 'Estimado/a ' + nombreCompleto + ',\n\n' +
                           'Gracias por su interés en la vacante de ' + vacanteTitle + '.\n\n' +
                           'Después de revisar su aplicación, queremos informarle que...\n\n' +
                           '[Escriba su mensaje personalizado aquí]\n\n' +
                           'Si tiene alguna pregunta, no dude en contactarnos.\n\n' +
                           'Saludos cordiales,\n' +
                           'Equipo de Recursos Humanos\n' +
                           'Ministerio de Finanzas Públicas';
    
    // Llenar los campos del modal
    document.getElementById('emailPara').value = email;
    document.getElementById('emailAsunto').value = 'Respuesta a su aplicación - ' + vacanteTitle;
    document.getElementById('emailCuerpo').value = mensajeTemplate;
    
    // Limpiar estado anterior
    document.getElementById('emailStatus').style.display = 'none';
    document.getElementById('btnEnviarEmail').disabled = false;
    document.getElementById('btnEnviarEmail').innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Email';
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('emailModal'));
    modal.show();
}

function enviarEmailDirecto() {
    const btnEnviar = document.getElementById('btnEnviarEmail');
    const statusDiv = document.getElementById('emailStatus');
    
    // Obtener datos del formulario
    const email = document.getElementById('emailPara').value.trim();
    const asunto = document.getElementById('emailAsunto').value.trim();
    const mensaje = document.getElementById('emailCuerpo').value.trim();
    
    console.log('Datos a enviar:', { email, asunto, mensaje: mensaje.substring(0, 50) + '...' });
    
    // Validar campos
    if (!email || !asunto || !mensaje) {
        mostrarEstado('error', 'Por favor completa todos los campos');
        return;
    }
    
    // Validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        mostrarEstado('error', 'El formato del email no es válido');
        return;
    }
    
    // Deshabilitar botón y mostrar loading
    btnEnviar.disabled = true;
    btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    
    // Preparar datos para AJAX
    const formData = new FormData();
    formData.append('action', 'enviar_email_respuesta');
    formData.append('nonce', '<?php echo wp_create_nonce('enviar_email_respuesta_nonce'); ?>');
    formData.append('email', email);
    formData.append('asunto', asunto);
    formData.append('mensaje', mensaje);
    
    console.log('Enviando a:', '<?php echo admin_url('admin-ajax.php'); ?>');
    
    // Enviar AJAX
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta HTTP:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('Respuesta cruda:', text);
        try {
            const data = JSON.parse(text);
            console.log('Datos parseados:', data);
            
            if (data.success) {
                mostrarEstado('success', data.data);
                // Cerrar modal después de 3 segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('emailModal'));
                    if (modal) {
                        modal.hide();
                    }
                }, 3000);
            } else {
                mostrarEstado('error', data.data || 'Error desconocido al enviar el email');
            }
        } catch (parseError) {
            console.error('Error al parsear JSON:', parseError);
            mostrarEstado('error', 'Error en la respuesta del servidor: ' + text.substring(0, 100));
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        mostrarEstado('error', 'Error de conexión: ' + error.message);
    })
    .finally(() => {
        // Rehabilitar botón
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Email';
    });
}

function mostrarEstado(tipo, mensaje) {
    const statusDiv = document.getElementById('emailStatus');
    statusDiv.className = 'alert alert-' + (tipo === 'success' ? 'success' : 'danger');
    statusDiv.innerHTML = '<i class="fas fa-' + (tipo === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + mensaje;
    statusDiv.style.display = 'block';
}

function probarConfiguracionEmail() {
    mostrarEstado('info', 'Probando configuración de email...');
    
    const formData = new FormData();
    formData.append('action', 'test_email_config');
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarEstado('success', 'Configuración OK: ' + data.data);
        } else {
            mostrarEstado('error', 'Problema de configuración: ' + data.data);
        }
    })
    .catch(error => {
        mostrarEstado('error', 'Error al probar configuración: ' + error.message);
    });
}
</script>