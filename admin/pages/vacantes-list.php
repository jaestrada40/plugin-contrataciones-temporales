<?php
/**
 * Página de Gestión de Vacantes
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

// Obtener instancias de modelos
$vacante_model = new Vacante_Model();
$direccion_model = new Direccion_Model();
$tipo_contrato_model = new Tipo_Contrato_Model();

// Procesar acciones
$action = $_GET['action'] ?? '';
$vacante_id = intval($_GET['vacante_id'] ?? 0);
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['save_vacante'])) {
        $data = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descripcion' => wp_kses_post($_POST['descripcion']),
            'requisitos' => wp_kses_post($_POST['requisitos']),
            'beneficios' => wp_kses_post($_POST['beneficios']),
            'salario_min' => floatval($_POST['salario_min']),
            'salario_max' => floatval($_POST['salario_max']),
            'direccion_id' => intval($_POST['direccion_id']),
            'tipo_contrato_id' => intval($_POST['tipo_contrato_id']),
            'fecha_limite' => sanitize_text_field($_POST['fecha_limite']),
            'estado' => sanitize_text_field($_POST['estado']),
            'ubicacion' => sanitize_text_field($_POST['ubicacion']),
            'modalidad' => sanitize_text_field($_POST['modalidad']),
            'experiencia_requerida' => intval($_POST['experiencia_requerida']),
            'nivel_educativo' => sanitize_text_field($_POST['nivel_educativo'])
        );
        
        if ($vacante_id > 0) {
            $result = $vacante_model->update($vacante_id, $data);
            $message = 'Vacante actualizada correctamente';
        } else {
            $result = $vacante_model->create($data);
            $message = 'Vacante creada correctamente';
        }
        
        if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $message_type = 'error';
        } else {
            $message_type = 'success';
            $action = '';
            $vacante_id = 0;
        }
    }
}

if ($action === 'delete' && $vacante_id > 0) {
    $result = $vacante_model->delete($vacante_id);
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $message_type = 'error';
    } else {
        $message = 'Vacante eliminada correctamente';
        $message_type = 'success';
    }
    $action = '';
}

// Obtener datos para formulario
$vacante = null;
if ($action === 'edit' && $vacante_id > 0) {
    $vacante = $vacante_model->get_by_id($vacante_id);
}

$direcciones = $direccion_model->get_all();
$tipos_contrato = $tipo_contrato_model->get_all();

// Obtener lista de vacantes
$page = intval($_GET['paged'] ?? 1);
$per_page = 10;
$search = sanitize_text_field($_GET['s'] ?? '');
$vacantes = $vacante_model->get_all($page, $per_page, $search);
$total_vacantes = $vacante_model->get_total_count($search);
$total_pages = ceil($total_vacantes / $per_page);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-briefcase"></i> Gestión de Vacantes
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="?page=vacantes-list&action=add" class="page-title-action">
            <i class="fas fa-plus"></i> Agregar Nueva
        </a>
        <?php endif; ?>
    </h1>
    
    <?php if ($message): ?>
    <div class="notice notice-<?php echo $message_type === 'error' ? 'error' : 'success'; ?> is-dismissible">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Formulario de Vacante -->
    <div class="card mt-4">
        <div class="card-header">
            <h3><?php echo $action === 'edit' ? 'Editar Vacante' : 'Nueva Vacante'; ?></h3>
        </div>
        <div class="card-body">
            <form method="post" class="vacante-form">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título de la Vacante *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo esc_attr($vacante->titulo ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción *</label>
                            <?php 
                            wp_editor(
                                $vacante->descripcion ?? '', 
                                'descripcion',
                                array(
                                    'textarea_name' => 'descripcion',
                                    'media_buttons' => false,
                                    'textarea_rows' => 6,
                                    'teeny' => true
                                )
                            );
                            ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="requisitos" class="form-label">Requisitos *</label>
                            <?php 
                            wp_editor(
                                $vacante->requisitos ?? '', 
                                'requisitos',
                                array(
                                    'textarea_name' => 'requisitos',
                                    'media_buttons' => false,
                                    'textarea_rows' => 6,
                                    'teeny' => true
                                )
                            );
                            ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="beneficios" class="form-label">Beneficios</label>
                            <?php 
                            wp_editor(
                                $vacante->beneficios ?? '', 
                                'beneficios',
                                array(
                                    'textarea_name' => 'beneficios',
                                    'media_buttons' => false,
                                    'textarea_rows' => 4,
                                    'teeny' => true
                                )
                            );
                            ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="direccion_id" class="form-label">Dirección *</label>
                            <select class="form-select" id="direccion_id" name="direccion_id" required>
                                <option value="">Seleccionar dirección</option>
                                <?php foreach ($direcciones as $direccion): ?>
                                <option value="<?php echo $direccion->id; ?>" 
                                        <?php selected($vacante->direccion_id ?? '', $direccion->id); ?>>
                                    <?php echo esc_html($direccion->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_contrato_id" class="form-label">Tipo de Contrato *</label>
                            <select class="form-select" id="tipo_contrato_id" name="tipo_contrato_id" required>
                                <option value="">Seleccionar tipo</option>
                                <?php foreach ($tipos_contrato as $tipo): ?>
                                <option value="<?php echo $tipo->id; ?>" 
                                        <?php selected($vacante->tipo_contrato_id ?? '', $tipo->id); ?>>
                                    <?php echo esc_html($tipo->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="salario_min" class="form-label">Salario Mín.</label>
                                    <input type="number" class="form-control" id="salario_min" name="salario_min" 
                                           value="<?php echo esc_attr($vacante->salario_min ?? ''); ?>" step="0.01">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="salario_max" class="form-label">Salario Máx.</label>
                                    <input type="number" class="form-control" id="salario_max" name="salario_max" 
                                           value="<?php echo esc_attr($vacante->salario_max ?? ''); ?>" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha_limite" class="form-label">Fecha Límite *</label>
                            <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" 
                                   value="<?php echo esc_attr($vacante->fecha_limite ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                   value="<?php echo esc_attr($vacante->ubicacion ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="modalidad" class="form-label">Modalidad</label>
                            <select class="form-select" id="modalidad" name="modalidad">
                                <option value="Presencial" <?php selected($vacante->modalidad ?? '', 'Presencial'); ?>>Presencial</option>
                                <option value="Remoto" <?php selected($vacante->modalidad ?? '', 'Remoto'); ?>>Remoto</option>
                                <option value="Híbrido" <?php selected($vacante->modalidad ?? '', 'Híbrido'); ?>>Híbrido</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="experiencia_requerida" class="form-label">Experiencia (años)</label>
                            <input type="number" class="form-control" id="experiencia_requerida" name="experiencia_requerida" 
                                   value="<?php echo esc_attr($vacante->experiencia_requerida ?? 0); ?>" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nivel_educativo" class="form-label">Nivel Educativo</label>
                            <select class="form-select" id="nivel_educativo" name="nivel_educativo">
                                <option value="Secundaria" <?php selected($vacante->nivel_educativo ?? '', 'Secundaria'); ?>>Secundaria</option>
                                <option value="Técnico" <?php selected($vacante->nivel_educativo ?? '', 'Técnico'); ?>>Técnico</option>
                                <option value="Universitario" <?php selected($vacante->nivel_educativo ?? '', 'Universitario'); ?>>Universitario</option>
                                <option value="Postgrado" <?php selected($vacante->nivel_educativo ?? '', 'Postgrado'); ?>>Postgrado</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="Activa" <?php selected($vacante->estado ?? 'Activa', 'Activa'); ?>>Activa</option>
                                <option value="Pausada" <?php selected($vacante->estado ?? '', 'Pausada'); ?>>Pausada</option>
                                <option value="Cerrada" <?php selected($vacante->estado ?? '', 'Cerrada'); ?>>Cerrada</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
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
    <!-- Lista de Vacantes -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="vacantes-list">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                       placeholder="Buscar vacantes..." class="form-control d-inline-block w-auto">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php if ($total_pages > 1): ?>
            <span class="displaying-num"><?php echo $total_vacantes; ?> elementos</span>
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            ));
            ?>
            <?php endif; ?>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Título</th>
                <th>Dirección</th>
                <th>Tipo Contrato</th>
                <th>Fecha Límite</th>
                <th>Estado</th>
                <th>Aplicaciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vacantes)): ?>
            <tr>
                <td colspan="7" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <p>No hay vacantes registradas</p>
                        <a href="?page=vacantes-list&action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Primera Vacante
                        </a>
                    </div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($vacantes as $vacante): ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($vacante->titulo); ?></strong>
                    <div class="small text-muted">
                        Código: <?php echo esc_html($vacante->codigo); ?>
                    </div>
                </td>
                <td><?php echo esc_html($vacante->direccion_nombre ?? 'N/A'); ?></td>
                <td><?php echo esc_html($vacante->tipo_contrato_nombre ?? 'N/A'); ?></td>
                <td>
                    <?php 
                    $fecha_limite = new DateTime($vacante->fecha_limite);
                    $hoy = new DateTime();
                    $class = $fecha_limite < $hoy ? 'text-danger' : '';
                    ?>
                    <span class="<?php echo $class; ?>">
                        <?php echo $fecha_limite->format('d/m/Y'); ?>
                    </span>
                </td>
                <td>
                    <?php
                    $badge_class = array(
                        'Activa' => 'success',
                        'Pausada' => 'warning',
                        'Cerrada' => 'danger'
                    );
                    $class = $badge_class[$vacante->estado] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $class; ?>"><?php echo esc_html($vacante->estado); ?></span>
                </td>
                <td>
                    <span class="badge bg-info"><?php echo intval($vacante->total_aplicaciones ?? 0); ?></span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="?page=vacantes-list&action=edit&vacante_id=<?php echo $vacante->id; ?>" 
                           class="btn btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?page=aplicaciones-list&vacante_id=<?php echo $vacante->id; ?>" 
                           class="btn btn-outline-info" title="Ver Aplicaciones">
                            <i class="fas fa-users"></i>
                        </a>
                        <a href="?page=vacantes-list&action=delete&vacante_id=<?php echo $vacante->id; ?>" 
                           class="btn btn-outline-danger" title="Eliminar"
                           onclick="return confirm('¿Está seguro de eliminar esta vacante?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>
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

.vacante-form .wp-editor-container {
    border: 1px solid #ddd;
}
</style>