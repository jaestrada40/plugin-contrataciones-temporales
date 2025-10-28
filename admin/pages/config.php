<?php
/**
 * Página de Configuración del Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

$message = '';
$message_type = '';

// Procesar formulario
if ($_POST && isset($_POST['save_config'])) {
    // Verificar nonce
    if (!wp_verify_nonce($_POST['config_nonce'], 'vacantes_config_nonce')) {
        wp_die('Error de seguridad');
    }
    
    // Configuraciones generales
    update_option('vacantes_minfin_items_per_page', intval($_POST['items_per_page']));
    update_option('vacantes_minfin_show_salary', isset($_POST['show_salary']) ? 1 : 0);
    update_option('vacantes_minfin_require_cv', isset($_POST['require_cv']) ? 1 : 0);
    update_option('vacantes_minfin_auto_approve_applications', isset($_POST['auto_approve_applications']) ? 1 : 0);
    update_option('vacantes_minfin_enable_public_search', isset($_POST['enable_public_search']) ? 1 : 0);
    
    // Configuraciones de API
    update_option('vacantes_minfin_api_url', sanitize_text_field($_POST['api_url']));
    update_option('vacantes_minfin_api_key', sanitize_text_field($_POST['api_key']));
    update_option('vacantes_minfin_api_timeout', intval($_POST['api_timeout']));
    
    // Configuraciones de email
    update_option('vacantes_minfin_email_notifications', isset($_POST['email_notifications']) ? 1 : 0);
    update_option('vacantes_minfin_admin_email', sanitize_email($_POST['admin_email']));
    update_option('vacantes_minfin_email_from_name', sanitize_text_field($_POST['email_from_name']));
    
    // Configuraciones de archivos
    update_option('vacantes_minfin_max_file_size', intval($_POST['max_file_size']));
    update_option('vacantes_minfin_allowed_file_types', sanitize_text_field($_POST['allowed_file_types']));
    
    $message = 'Configuración guardada correctamente.';
    $message_type = 'success';
}

// Obtener configuraciones actuales
$items_per_page = get_option('vacantes_minfin_items_per_page', 10);
$show_salary = get_option('vacantes_minfin_show_salary', 1);
$require_cv = get_option('vacantes_minfin_require_cv', 1);
$auto_approve_applications = get_option('vacantes_minfin_auto_approve_applications', 0);
$enable_public_search = get_option('vacantes_minfin_enable_public_search', 1);

$api_url = get_option('vacantes_minfin_api_url', '');
$api_key = get_option('vacantes_minfin_api_key', '');
$api_timeout = get_option('vacantes_minfin_api_timeout', 30);

$email_notifications = get_option('vacantes_minfin_email_notifications', 1);
$admin_email = get_option('vacantes_minfin_admin_email', get_option('admin_email'));
$email_from_name = get_option('vacantes_minfin_email_from_name', get_bloginfo('name'));

$max_file_size = get_option('vacantes_minfin_max_file_size', 5);
$allowed_file_types = get_option('vacantes_minfin_allowed_file_types', 'pdf,doc,docx');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('vacantes_config_nonce', 'config_nonce'); ?>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Configuraciones Generales -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Configuraciones Generales</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="items_per_page">Elementos por página:</label>
                            <input type="number" 
                                   id="items_per_page" 
                                   name="items_per_page" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($items_per_page); ?>" 
                                   min="1" 
                                   max="100">
                            <small class="form-text text-muted">Número de vacantes a mostrar por página en el listado público.</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   id="show_salary" 
                                   name="show_salary" 
                                   class="form-check-input" 
                                   value="1" 
                                   <?php checked($show_salary, 1); ?>>
                            <label for="show_salary" class="form-check-label">Mostrar salario en las vacantes</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   id="require_cv" 
                                   name="require_cv" 
                                   class="form-check-input" 
                                   value="1" 
                                   <?php checked($require_cv, 1); ?>>
                            <label for="require_cv" class="form-check-label">Requerir CV obligatorio</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   id="auto_approve_applications" 
                                   name="auto_approve_applications" 
                                   class="form-check-input" 
                                   value="1" 
                                   <?php checked($auto_approve_applications, 1); ?>>
                            <label for="auto_approve_applications" class="form-check-label">Auto-aprobar aplicaciones</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   id="enable_public_search" 
                                   name="enable_public_search" 
                                   class="form-check-input" 
                                   value="1" 
                                   <?php checked($enable_public_search, 1); ?>>
                            <label for="enable_public_search" class="form-check-label">Habilitar búsqueda pública</label>
                        </div>
                    </div>
                </div>
                
                <!-- Configuraciones de API -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Configuración de API</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="api_url">URL de la API:</label>
                            <input type="url" 
                                   id="api_url" 
                                   name="api_url" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($api_url); ?>" 
                                   placeholder="https://api.ejemplo.com">
                            <small class="form-text text-muted">URL base de la API de vacantes.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="api_key">Clave de API:</label>
                            <input type="password" 
                                   id="api_key" 
                                   name="api_key" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   placeholder="Ingrese la clave de API">
                            <small class="form-text text-muted">Clave de autenticación para la API.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="api_timeout">Timeout de API (segundos):</label>
                            <input type="number" 
                                   id="api_timeout" 
                                   name="api_timeout" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($api_timeout); ?>" 
                                   min="5" 
                                   max="120">
                            <small class="form-text text-muted">Tiempo máximo de espera para las peticiones a la API.</small>
                        </div>
                    </div>
                </div>  
              
                <!-- Configuraciones de Email -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Configuración de Email</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   id="email_notifications" 
                                   name="email_notifications" 
                                   class="form-check-input" 
                                   value="1" 
                                   <?php checked($email_notifications, 1); ?>>
                            <label for="email_notifications" class="form-check-label">Habilitar notificaciones por email</label>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="admin_email">Email del administrador:</label>
                            <input type="email" 
                                   id="admin_email" 
                                   name="admin_email" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($admin_email); ?>" 
                                   required>
                            <small class="form-text text-muted">Email donde se enviarán las notificaciones de nuevas aplicaciones.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="email_from_name">Nombre del remitente:</label>
                            <input type="text" 
                                   id="email_from_name" 
                                   name="email_from_name" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($email_from_name); ?>" 
                                   required>
                            <small class="form-text text-muted">Nombre que aparecerá como remitente en los emails.</small>
                        </div>
                    </div>
                </div>
                
                <!-- Configuraciones de Archivos -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Configuración de Archivos</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="max_file_size">Tamaño máximo de archivo (MB):</label>
                            <input type="number" 
                                   id="max_file_size" 
                                   name="max_file_size" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($max_file_size); ?>" 
                                   min="1" 
                                   max="50">
                            <small class="form-text text-muted">Tamaño máximo permitido para archivos CV.</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="allowed_file_types">Tipos de archivo permitidos:</label>
                            <input type="text" 
                                   id="allowed_file_types" 
                                   name="allowed_file_types" 
                                   class="form-control" 
                                   value="<?php echo esc_attr($allowed_file_types); ?>" 
                                   placeholder="pdf,doc,docx">
                            <small class="form-text text-muted">Extensiones de archivo permitidas, separadas por comas.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Panel de Información -->
                <div class="card">
                    <div class="card-header">
                        <h3>Información del Sistema</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Versión del Plugin:</strong> <?php echo defined('VACANTES_MINFIN_VERSION') ? VACANTES_MINFIN_VERSION : '1.0.0'; ?></p>
                        <p><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></p>
                        <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
                        
                        <hr>
                        
                        <h5>Estado de la API</h5>
                        <div id="api-status">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="testApiConnection()">
                                Probar Conexión
                            </button>
                        </div>
                        
                        <hr>
                        
                        <h5>Shortcodes Disponibles</h5>
                        <ul class="list-unstyled small">
                            <li><code>[vacantes_lista]</code> - Lista de vacantes</li>
                            <li><code>[vacante_detalle id="X"]</code> - Detalle de vacante</li>
                            <li><code>[vacantes_formulario]</code> - Formulario de aplicación</li>
                            <li><code>[vacantes_buscar]</code> - Buscador de vacantes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <button type="submit" name="save_config" class="btn btn-primary">
                    Guardar Configuración
                </button>
                <a href="<?php echo admin_url('admin.php?page=vacantes-minfin'); ?>" class="btn btn-secondary">
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function testApiConnection() {
    const statusDiv = document.getElementById('api-status');
    const apiUrl = document.getElementById('api_url').value;
    const apiKey = document.getElementById('api_key').value;
    
    if (!apiUrl) {
        statusDiv.innerHTML = '<div class="alert alert-warning">Por favor, configure la URL de la API primero.</div>';
        return;
    }
    
    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Probando conexión...';
    
    // Realizar petición AJAX para probar la conexión
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'test_api_connection',
            api_url: apiUrl,
            api_key: apiKey,
            nonce: '<?php echo wp_create_nonce('test_api_connection'); ?>'
        },
        success: function(response) {
            if (response.success) {
                statusDiv.innerHTML = '<div class="alert alert-success">✓ Conexión exitosa</div>';
            } else {
                statusDiv.innerHTML = '<div class="alert alert-danger">✗ Error: ' + response.data + '</div>';
            }
        },
        error: function() {
            statusDiv.innerHTML = '<div class="alert alert-danger">✗ Error de conexión</div>';
        }
    });
}
</script>