<?php
/**
 * Página de Gestión de Vacantes - Diseño Moderno
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

// Debug inicial (comentado para producción)
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     error_log('=== POST REQUEST DETECTED ===');
//     error_log('POST keys: ' . implode(', ', array_keys($_POST)));
// }

// Procesar acciones
$action = $_GET['action'] ?? '';
$vacante_id = intval($_GET['id'] ?? intval($_GET['vacante_id'] ?? 0));
$message = '';
$message_type = '';

// INCLUIR REPARACIÓN DE BASE DE DATOS SI SE SOLICITA
if (isset($_GET['db_repair']) && $_GET['db_repair'] == '1') {
    include_once plugin_dir_path(__FILE__) . 'db-repair.php';
}

// VERIFICAR Y AGREGAR COLUMNA bases_pdf SI NO EXISTE (AUTOMÁTICO)
global $wpdb;
$table_name = $wpdb->prefix . 'vacantes_minfin';
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'bases_pdf'");

if (empty($column_exists)) {
    $sql = "ALTER TABLE `{$table_name}` ADD COLUMN `bases_pdf` VARCHAR(500) NULL AFTER `estado`";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle me-2"></i>✅ Columna bases_pdf agregada automáticamente a la base de datos';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>⚠️ No se pudo agregar la columna automáticamente. ';
        echo '<a href="?page=vacantes-list&db_repair=1" class="alert-link">Haz clic aquí para reparar manualmente</a>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_vacante']) || (isset($_POST['titulo']) && !empty($_POST['titulo'])))) {
    // Procesar archivo PDF de bases si se subió
    $bases_pdf_url = '';
    if (isset($_FILES['bases_pdf']) && $_FILES['bases_pdf']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['bases_pdf'];
        
        // Validar tipo de archivo
        $allowed_types = array('application/pdf');
        $file_type = $file['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Validar tamaño (5MB máximo)
            $max_size = 5 * 1024 * 1024; // 5MB en bytes
            if ($file['size'] <= $max_size) {
                // Crear directorio si no existe
                $upload_dir = wp_upload_dir();
                $vacantes_dir = $upload_dir['basedir'] . '/vacantes-bases/';
                
                if (!file_exists($vacantes_dir)) {
                    wp_mkdir_p($vacantes_dir);
                }
                
                // Generar nombre único para el archivo
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = 'bases_' . time() . '_' . wp_generate_password(8, false) . '.' . $file_extension;
                $file_path = $vacantes_dir . $file_name;
                
                // Mover archivo
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $bases_pdf_url = $upload_dir['baseurl'] . '/vacantes-bases/' . $file_name;
                }
            }
        }
    }
    
    $data = array(
        'codigo' => sanitize_text_field($_POST['codigo']),
        'titulo' => sanitize_text_field($_POST['titulo']),
        'descripcion' => sanitize_textarea_field($_POST['descripcion']),
        'requisitos' => sanitize_textarea_field($_POST['requisitos']),
        'direccion_id' => intval($_POST['direccion_id']),
        'tipo_contrato_id' => intval($_POST['tipo_contrato_id']),
        'salario_min' => floatval($_POST['salario_min']),
        'salario_max' => floatval($_POST['salario_max']),
        'fecha_limite' => sanitize_text_field($_POST['fecha_limite']),
        'estado' => sanitize_text_field($_POST['estado'])
    );
    
    // Agregar URL del PDF si se subió Y la columna existe
    if (!empty($bases_pdf_url)) {
        // Verificar si la columna existe antes de intentar usarla
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'bases_pdf'");
        if (!empty($column_exists)) {
            $data['bases_pdf'] = $bases_pdf_url;
        }
    }
    
    // Verificar si la columna bases_pdf existe
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'bases_pdf'");
    $has_pdf_column = !empty($column_exists);
    
    if ($vacante_id > 0) {
        // Actualizar vacante existente
        $format_array = array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s');
        if ($has_pdf_column && !empty($bases_pdf_url)) {
            $format_array[] = '%s';
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'vacantes_minfin',
            $data,
            array('id' => $vacante_id),
            $format_array,
            array('%d')
        );
        // Mensaje se asigna después
    } else {
        // Crear nueva vacante
        $data['fecha_creacion'] = current_time('mysql');
        
        // Generar código automático si no se proporcionó
        if (empty($data['codigo'])) {
            $data['codigo'] = 'VAC-' . date('Y') . '-' . str_pad(($stats['total'] + 1), 4, '0', STR_PAD_LEFT);
        }
        
        $format_array = array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s');
        if ($has_pdf_column && !empty($bases_pdf_url)) {
            $format_array[] = '%s';
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vacantes_minfin',
            $data,
            $format_array
        );
        // Mensaje se asigna después
    }
    
    if ($result !== false) {
        $message_type = 'success';
        $message = $vacante_id > 0 ? 'Vacante actualizada correctamente' : 'Vacante creada correctamente';
        $action = '';
        $vacante_id = 0;
        
        // Limpiar variables para mostrar la lista
        $_GET['action'] = '';
        $_GET['id'] = '';
    } else {
        $message = 'Error al guardar la vacante: ' . $wpdb->last_error;
        $message_type = 'error';
    }
}

if ($action === 'delete' && $vacante_id > 0) {
    // Verificar si tiene aplicaciones
    $aplicaciones_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE vacante_id = %d",
        $vacante_id
    ));
    
    if ($aplicaciones_count > 0) {
        $message = 'No se puede eliminar la vacante porque tiene aplicaciones asociadas';
        $message_type = 'error';
    } else {
        $result = $wpdb->delete(
            $wpdb->prefix . 'vacantes_minfin',
            array('id' => $vacante_id),
            array('%d')
        );
        
        if ($result !== false) {
            $message = 'Vacante eliminada correctamente';
            $message_type = 'success';
        } else {
            $message = 'Error al eliminar la vacante';
            $message_type = 'error';
        }
    }
    $action = '';
}

// Función para cargar datos
function cargar_datos_vacantes() {
    global $wpdb;
    
    // Obtener vacantes directamente de la base de datos
    $search = sanitize_text_field($_GET['s'] ?? '');
    $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
    $direccion_filtro = intval($_GET['direccion_id'] ?? 0);
    $tipo_contrato_filtro = intval($_GET['tipo_contrato_id'] ?? 0);
    
    // Paginación
    $items_per_page = 10;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $items_per_page;

    $where_conditions = array();
    $query_params = array();

    if (!empty($search)) {
        $where_conditions[] = "(v.titulo LIKE %s OR v.descripcion LIKE %s OR v.codigo LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $query_params[] = $search_term;
        $query_params[] = $search_term;
        $query_params[] = $search_term;
    }

    if (!empty($estado_filtro)) {
        $where_conditions[] = "v.estado = %s";
        $query_params[] = $estado_filtro;
    }

    if ($direccion_filtro > 0) {
        $where_conditions[] = "v.direccion_id = %d";
        $query_params[] = $direccion_filtro;
    }

    if ($tipo_contrato_filtro > 0) {
        $where_conditions[] = "v.tipo_contrato_id = %d";
        $query_params[] = $tipo_contrato_filtro;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Contar total de registros
    $count_sql = "SELECT COUNT(DISTINCT v.id) 
                  FROM {$wpdb->prefix}vacantes_minfin v
                  LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                  LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
                  {$where_clause}";

    if (!empty($query_params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
    } else {
        $total_items = $wpdb->get_var($count_sql);
    }

    // Consulta con paginación
    $sql = "SELECT v.*, 
                   d.nombre as direccion_nombre,
                   tc.nombre as tipo_contrato_nombre,
                   COUNT(a.id) as total_aplicaciones
            FROM {$wpdb->prefix}vacantes_minfin v
            LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
            LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
            LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
            {$where_clause}
            GROUP BY v.id
            ORDER BY v.fecha_creacion DESC
            LIMIT %d OFFSET %d";

    $query_params[] = $items_per_page;
    $query_params[] = $offset;

    if (!empty($query_params)) {
        $vacantes = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    } else {
        $vacantes = $wpdb->get_results($sql);
    }

    // Obtener estadísticas
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'Activa' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'Inactiva' THEN 1 ELSE 0 END) as inactivas,
        SUM(CASE WHEN estado = 'Cerrada' THEN 1 ELSE 0 END) as cerradas,
        (SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin) as total_aplicaciones
    FROM {$wpdb->prefix}vacantes_minfin";

    $stats = $wpdb->get_row($stats_sql, ARRAY_A);

    // Obtener direcciones para filtro
    $direcciones = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}direcciones_minfin WHERE activa = 1 ORDER BY nombre ASC");

    // Obtener tipos de contrato para filtro
    $tipos_contrato = $wpdb->get_results("SELECT id, nombre FROM {$wpdb->prefix}tipos_contrato_minfin WHERE activo = 1 ORDER BY nombre ASC");
    
    return array(
        'vacantes' => $vacantes,
        'total_vacantes' => $total_items,
        'stats' => $stats,
        'direcciones' => $direcciones,
        'tipos_contrato' => $tipos_contrato,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_pages' => ceil($total_items / $items_per_page)
    );
}

// Cargar datos después del procesamiento
$datos = cargar_datos_vacantes();
$vacantes = $datos['vacantes'];
$total_vacantes = $datos['total_vacantes'];
$stats = $datos['stats'];
$direcciones = $datos['direcciones'];
$tipos_contrato = $datos['tipos_contrato'];

// Variables para los filtros
$search = sanitize_text_field($_GET['s'] ?? '');
$estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
$direccion_filtro = intval($_GET['direccion_id'] ?? 0);

// Obtener datos para formulario
$vacante = null;
if ($action === 'edit' && $vacante_id > 0) {
    $vacante = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vacantes_minfin WHERE id = %d",
        $vacante_id
    ));
}
?>

<div class="wrap">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-briefcase me-3"></i>Gestión de Vacantes</h1>
            <p class="subtitle">Administra las vacantes laborales disponibles en el MINFIN</p>
        </div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="?page=vacantes-list&action=add" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Nueva Vacante
        </a>
        <?php endif; ?>
    </div>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'error' ? 'error' : 'success'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
    <div class="notice notice-success is-dismissible">
        <p>✅ Vacante guardada correctamente.</p>
    </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Formulario de Vacante -->
    <div class="form-card">
        <div class="card-header">
            <h3><i class="fas fa-<?php echo $action === 'edit' ? 'edit' : 'plus'; ?> me-2"></i><?php echo $action === 'edit' ? 'Editar Vacante' : 'Nueva Vacante'; ?></h3>
        </div>
        <div class="card-body">
            <form method="post" class="vacante-form" enctype="multipart/form-data">
                <input type="hidden" name="save_vacante" value="1">
                <?php if ($action === 'edit' && $vacante_id > 0): ?>
                <input type="hidden" name="vacante_id" value="<?php echo $vacante_id; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código de la Vacante</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                   value="<?php echo esc_attr($vacante->codigo ?? ''); ?>" 
                                   placeholder="Ej: VAC-2024-001">
                            <div class="form-text">Código único para identificar la vacante</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título de la Vacante *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo esc_attr($vacante->titulo ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción del Puesto</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"
                                      placeholder="Descripción detallada del puesto y responsabilidades..."><?php echo esc_textarea($vacante->descripcion ?? ''); ?></textarea>
                        </div>
                        
                        <?php 
                        // Solo mostrar el campo PDF si la columna existe en la base de datos
                        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'bases_pdf'");
                        if (!empty($column_exists)): 
                        ?>
                        <div class="mb-3">
                            <label for="bases_pdf" class="form-label">Bases de la Convocatoria (PDF)</label>
                            <input type="file" class="form-control" id="bases_pdf" name="bases_pdf" 
                                   accept=".pdf" data-max-size="5242880">
                            <div class="form-text">
                                <i class="fas fa-info-circle text-info me-1"></i>
                                Sube el archivo PDF con las bases de la convocatoria (máximo 5MB)
                            </div>
                            <?php if (isset($vacante->bases_pdf) && $vacante->bases_pdf): ?>
                            <div class="mt-2">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                    <div>
                                        <strong>Archivo actual:</strong> 
                                        <a href="<?php echo esc_url($vacante->bases_pdf); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo basename($vacante->bases_pdf); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Campo PDF no disponible:</strong> La base de datos necesita actualización. 
                                <a href="?page=vacantes-list&db_repair=1" class="alert-link">Reparar ahora</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="requisitos" class="form-label">Requisitos</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="4"
                                      placeholder="Requisitos académicos, experiencia, competencias..."><?php echo esc_textarea($vacante->requisitos ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="direccion_id" class="form-label">Dirección *</label>
                                    <select class="form-select" id="direccion_id" name="direccion_id" required>
                                        <option value="">Seleccionar dirección</option>
                                        <?php foreach ($direcciones as $dir): ?>
                                        <option value="<?php echo $dir->id; ?>" <?php selected($vacante->direccion_id ?? 0, $dir->id); ?>>
                                            <?php echo esc_html($dir->nombre); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_contrato_id" class="form-label">Tipo de Contrato *</label>
                                    <select class="form-select" id="tipo_contrato_id" name="tipo_contrato_id" required>
                                        <option value="">Seleccionar tipo</option>
                                        <?php foreach ($tipos_contrato as $tipo): ?>
                                        <option value="<?php echo $tipo->id; ?>" <?php selected($vacante->tipo_contrato_id ?? 0, $tipo->id); ?>>
                                            <?php echo esc_html($tipo->nombre); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="salario_min" class="form-label">Salario Mínimo (Q)</label>
                                    <input type="number" class="form-control" id="salario_min" name="salario_min" 
                                           value="<?php echo esc_attr($vacante->salario_min ?? ''); ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="salario_max" class="form-label">Salario Máximo (Q)</label>
                                    <input type="number" class="form-control" id="salario_max" name="salario_max" 
                                           value="<?php echo esc_attr($vacante->salario_max ?? ''); ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha_limite" class="form-label">Fecha Límite de Aplicación</label>
                            <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" 
                                   value="<?php echo esc_attr($vacante->fecha_limite ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Estado de la Vacante</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="Activa" <?php selected($vacante->estado ?? 'Activa', 'Activa'); ?>>Activa</option>
                                        <option value="Inactiva" <?php selected($vacante->estado ?? '', 'Inactiva'); ?>>Inactiva</option>
                                        <option value="Cerrada" <?php selected($vacante->estado ?? '', 'Cerrada'); ?>>Cerrada</option>
                                    </select>
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
                                    <span class="small text-muted">Las vacantes activas aparecen en el portal público</span>
                                </div>
                                <div class="info-item mt-2">
                                    <i class="fas fa-calendar-alt text-info me-2"></i>
                                    <span class="small text-muted">La fecha límite es opcional pero recomendada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" name="save_vacante" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="?page=vacantes-list" class="btn btn-secondary">
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
                    <i class="fas fa-briefcase fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['total'] ?? 0); ?></div>
                <div class="label">Total Vacantes</div>
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
                    <i class="fas fa-users fa-lg"></i>
                </div>
                <div class="number"><?php echo intval($stats['total_aplicaciones'] ?? 0); ?></div>
                <div class="label">Aplicaciones</div>
            </div>
        </div>
    </div>

    <!-- Tabla de Vacantes Mejorada -->
    <div class="data-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-list me-2"></i>Lista de Vacantes (<?php echo $total_vacantes; ?>)</h5>
            
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="vacantes-list">
                <div class="input-group">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Buscar vacantes..." class="form-control">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="Activa" <?php selected($estado_filtro, 'Activa'); ?>>Activas</option>
                        <option value="Inactiva" <?php selected($estado_filtro, 'Inactiva'); ?>>Inactivas</option>
                        <option value="Cerrada" <?php selected($estado_filtro, 'Cerrada'); ?>>Cerradas</option>
                    </select>
                    <select name="direccion_id" class="form-select">
                        <option value="">Todas las direcciones</option>
                        <?php foreach ($direcciones as $dir): ?>
                        <option value="<?php echo $dir->id; ?>" <?php selected($direccion_filtro, $dir->id); ?>>
                            <?php echo esc_html($dir->nombre); ?>
                        </option>
                        <?php endforeach; ?>
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
                        <th>Vacante</th>
                        <th>Dirección</th>
                        <th>Tipo Contrato</th>
                        <th>Estado</th>
                        <th class="text-center">Aplicaciones</th>
                        <th>Fecha Límite</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vacantes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay vacantes registradas</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($vacantes as $vacante): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="icon-wrapper bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="fas fa-briefcase text-primary"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?php echo esc_html($vacante->titulo); ?></div>
                                    <div class="small text-muted"><?php echo esc_html($vacante->codigo); ?></div>
                                    <?php if ($vacante->descripcion): ?>
                                    <div class="small text-muted mt-1">
                                        <?php echo esc_html(wp_trim_words($vacante->descripcion, 10)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-building text-muted me-2"></i>
                                <span class="fw-medium"><?php echo esc_html($vacante->direccion_nombre); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-file-contract text-muted me-2"></i>
                                <span><?php echo esc_html($vacante->tipo_contrato_nombre); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $badge_class = array(
                                'Activa' => 'success',
                                'Inactiva' => 'secondary',
                                'Cerrada' => 'warning'
                            );
                            $class = $badge_class[$vacante->estado] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>-subtle text-<?php echo $class; ?> border border-<?php echo $class; ?>-subtle">
                                <i class="fas fa-<?php echo $vacante->estado === 'Activa' ? 'check-circle' : ($vacante->estado === 'Cerrada' ? 'lock' : 'pause-circle'); ?> me-1"></i><?php echo $vacante->estado; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column align-items-center">
                                <span class="badge bg-info-subtle text-info border border-info-subtle fs-6">
                                    <?php echo intval($vacante->total_aplicaciones ?? 0); ?>
                                </span>
                                <small class="text-muted">aplicaciones</small>
                            </div>
                        </td>
                        <td>
                            <?php if ($vacante->fecha_limite): ?>
                            <div class="small">
                                <i class="fas fa-calendar-alt text-muted me-1"></i>
                                <?php echo date('d/m/Y', strtotime($vacante->fecha_limite)); ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted fst-italic">Sin límite</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="?page=vacantes-list&action=edit&id=<?php echo $vacante->id; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar vacante">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?page=aplicaciones-list&vacante_id=<?php echo $vacante->id; ?>" 
                                   class="btn btn-sm btn-outline-info" title="Ver aplicaciones">
                                    <i class="fas fa-users"></i>
                                </a>
                                <?php if (!empty($vacante->bases_pdf)): ?>
                                <a href="<?php echo esc_url($vacante->bases_pdf); ?>" target="_blank"
                                   class="btn btn-sm btn-outline-success" title="Ver PDF de bases">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (intval($vacante->total_aplicaciones) === 0): ?>
                                <a href="?page=vacantes-list&action=delete&id=<?php echo $vacante->id; ?>" 
                                   class="btn btn-sm btn-outline-danger" title="Eliminar vacante"
                                   onclick="return confirm('⚠️ ¿Está seguro de eliminar esta vacante?\n\nEsta acción no se puede deshacer.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-danger" disabled title="No se puede eliminar: tiene aplicaciones asociadas">
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
                    <?php echo min($datos['current_page'] * $datos['items_per_page'], $datos['total_vacantes']); ?> 
                    de <?php echo $datos['total_vacantes']; ?> vacantes
                </div>
                <nav aria-label="Paginación de vacantes">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($datos['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=vacantes-list&paged=<?php echo $datos['current_page'] - 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?><?php echo !empty($_GET['direccion_id']) ? '&direccion_id=' . urlencode($_GET['direccion_id']) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $datos['current_page'] - 2); $i <= min($datos['total_pages'], $datos['current_page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $datos['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=vacantes-list&paged=<?php echo $i; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?><?php echo !empty($_GET['direccion_id']) ? '&direccion_id=' . urlencode($_GET['direccion_id']) : ''; ?>" style="<?php echo $i == $datos['current_page'] ? 'color: white !important;' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($datos['current_page'] < $datos['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=vacantes-list&paged=<?php echo $datos['current_page'] + 1; ?><?php echo !empty($_GET['s']) ? '&s=' . urlencode($_GET['s']) : ''; ?><?php echo !empty($_GET['estado']) ? '&estado=' . urlencode($_GET['estado']) : ''; ?><?php echo !empty($_GET['direccion_id']) ? '&direccion_id=' . urlencode($_GET['direccion_id']) : ''; ?>">
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
    // Auto-submit SOLO en filtros de búsqueda (no en formularios de edición)
    const filtrosBusqueda = document.querySelectorAll('.search-form select[name="estado"], .search-form select[name="direccion_id"]');
    filtrosBusqueda.forEach(filtro => {
        filtro.addEventListener('change', function() {
            const btn = this.form.querySelector('button[type="submit"]');
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                setTimeout(() => {
                    this.form.submit();
                }, 300);
            }
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
    
    // Validación del formulario
    const form = document.querySelector('.vacante-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const direccion = document.getElementById('direccion_id').value;
            const tipoContrato = document.getElementById('tipo_contrato_id').value;
            
            if (!titulo || !direccion || !tipoContrato) {
                e.preventDefault();
                alert('⚠️ Por favor complete todos los campos obligatorios.');
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            }
            
            // Permitir que el formulario se envíe normalmente
            return true;
        });
    }
    
    // Validación del archivo PDF de bases
    const pdfInput = document.getElementById('bases_pdf');
    if (pdfInput) {
        pdfInput.addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file) {
                // Validar tipo de archivo
                if (file.type !== 'application/pdf') {
                    alert('⚠️ Solo se permiten archivos PDF');
                    this.value = '';
                    return;
                }
                
                // Validar tamaño
                if (file.size > maxSize) {
                    alert('⚠️ El archivo PDF no puede ser mayor a 5MB\\n\\nTamaño actual: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                    this.value = '';
                    return;
                }
                
                // Mostrar información del archivo
                const fileInfo = document.createElement('div');
                fileInfo.className = 'mt-2 alert alert-success';
                fileInfo.innerHTML = `
                    <i class="fas fa-file-pdf text-danger me-2"></i>
                    <strong>Archivo seleccionado:</strong> ${file.name} 
                    <span class="text-muted">(${(file.size / 1024 / 1024).toFixed(2)}MB)</span>
                `;
                
                // Remover info anterior si existe
                const existingInfo = this.parentNode.querySelector('.alert-success');
                if (existingInfo) {
                    existingInfo.remove();
                }
                
                this.parentNode.appendChild(fileInfo);
            }
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