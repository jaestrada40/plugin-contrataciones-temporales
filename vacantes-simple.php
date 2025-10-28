<?php
/**
 * Plugin Name: Vacantes MINFIN Simple
 * Description: Sistema simple de gestión de vacantes laborales - Réplica del sistema Angular/.NET
 * Version: 1.0.0
 * Author: Ministerio de Finanzas Públicas
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('VACANTES_SIMPLE_VERSION', '1.0.0');
define('VACANTES_SIMPLE_DIR', plugin_dir_path(__FILE__));
define('VACANTES_SIMPLE_URL', plugin_dir_url(__FILE__));
define('VACANTES_MINFIN_PATH', plugin_dir_path(__FILE__));
define('VACANTES_MINFIN_VERSION', '1.0.0');

/**
 * Clase principal del plugin
 */
class Vacantes_Simple {
    
    public function __construct() {
        error_log('Vacantes_Simple plugin inicializándose');
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'public_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_aplicar_vacante', array($this, 'ajax_save_aplicacion'));
        add_action('wp_ajax_nopriv_aplicar_vacante', array($this, 'ajax_save_aplicacion'));
        add_action('wp_ajax_test_ajax', array($this, 'ajax_test'));
        add_action('wp_ajax_nopriv_test_ajax', array($this, 'ajax_test'));
        
        // Hook adicional para debugging
        add_action('wp_ajax_debug_test', array($this, 'debug_test'));
        add_action('wp_ajax_nopriv_debug_test', array($this, 'debug_test'));
        
        // Hook específico para el formulario
        add_action('wp_ajax_enviar_aplicacion', array($this, 'ajax_enviar_aplicacion'));
        add_action('wp_ajax_nopriv_enviar_aplicacion', array($this, 'ajax_enviar_aplicacion'));
        
        // Test adicional con nombre diferente
        add_action('wp_ajax_form_test', array($this, 'ajax_form_test'));
        add_action('wp_ajax_nopriv_form_test', array($this, 'ajax_form_test'));
        
        // Hook para eliminar índice único manualmente
        add_action('wp_ajax_remove_unique_constraint', array($this, 'ajax_remove_unique_constraint'));
        
        // Shortcodes
        add_shortcode('vacantes_laborales', array($this, 'shortcode_vacantes_laborales'));
        add_shortcode('vacantes_direccion', array($this, 'shortcode_vacantes_direccion'));
        add_shortcode('vacante_detalle', array($this, 'shortcode_vacante_detalle'));
        add_shortcode('aplicar_vacante', array($this, 'shortcode_aplicar_vacante'));
    }
    
    public function activate() {
        // Crear tablas de base de datos
        $this->create_database_tables();
        
        // Eliminar índice único si existe (para permitir aplicaciones múltiples)
        $this->remove_unique_constraint();
        
        flush_rewrite_rules();
    }
    
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de Direcciones
        $table_direcciones = $wpdb->prefix . 'direcciones_minfin';
        $sql_direcciones = "CREATE TABLE $table_direcciones (
            id int(11) NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabla de Tipos de Contrato
        $table_tipos = $wpdb->prefix . 'tipos_contrato_minfin';
        $sql_tipos = "CREATE TABLE $table_tipos (
            id int(11) NOT NULL AUTO_INCREMENT,
            codigo varchar(20) NOT NULL,
            nombre varchar(100) NOT NULL,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabla de Vacantes
        $table_vacantes = $wpdb->prefix . 'vacantes_minfin';
        $sql_vacantes = "CREATE TABLE $table_vacantes (
            id int(11) NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            requisitos text,
            salario_min decimal(10,2) DEFAULT 0,
            salario_max decimal(10,2) DEFAULT 0,
            direccion_id int(11) NOT NULL,
            tipo_contrato_id int(11) NOT NULL,
            fecha_limite date NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(50) DEFAULT 'Activa',
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabla de Aplicaciones
        $table_aplicaciones = $wpdb->prefix . 'aplicaciones_minfin';
        $sql_aplicaciones = "CREATE TABLE $table_aplicaciones (
            id int(11) NOT NULL AUTO_INCREMENT,
            vacante_id int(11) NOT NULL,
            nombres varchar(100) NOT NULL,
            apellidos varchar(100) NOT NULL,
            dpi varchar(20) NOT NULL,
            email varchar(255) NOT NULL,
            telefono varchar(50),
            direccion text,
            nivel_educativo varchar(100),
            profesion varchar(100),
            carta_presentacion text,
            cv_url varchar(500),
            cv_ruta_archivo varchar(500),
            fecha_aplicacion datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(50) DEFAULT 'Pendiente',
            ip_address varchar(45),
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_direcciones);
        dbDelta($sql_tipos);
        dbDelta($sql_vacantes);
        dbDelta($sql_aplicaciones);
        
        // Insertar datos iniciales
        $this->insert_initial_data();
    }
    
    private function insert_initial_data() {
        global $wpdb;
        
        // Verificar si ya existen datos
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin");
        if ($count > 0) {
            return; // Ya hay datos
        }
        
        // Insertar direcciones iniciales
        $wpdb->insert(
            $wpdb->prefix . 'direcciones_minfin',
            array(
                'nombre' => 'Dirección de Recursos Humanos',
                'descripcion' => 'Gestión del talento humano del ministerio',
                'activa' => 1
            )
        );
        $direccion_id = $wpdb->insert_id;
        
        // Insertar tipo de contrato
        $wpdb->insert(
            $wpdb->prefix . 'tipos_contrato_minfin',
            array(
                'codigo' => 'CTI',
                'nombre' => 'Contrato por Tiempo Indefinido',
                'activo' => 1
            )
        );
        $tipo_id = $wpdb->insert_id;
        
        // Insertar vacante de prueba
        $wpdb->insert(
            $wpdb->prefix . 'vacantes_minfin',
            array(
                'codigo' => 'VAC-001',
                'titulo' => 'Analista de Recursos Humanos',
                'descripcion' => 'Posición para analista con experiencia en gestión de personal.',
                'requisitos' => 'Licenciatura en Administración, 2 años de experiencia.',
                'direccion_id' => $direccion_id,
                'tipo_contrato_id' => $tipo_id,
                'fecha_limite' => date('Y-m-d', strtotime('+30 days')),
                'estado' => 'Activa'
            )
        );
    }
    
    /**
     * Eliminar restricción única para permitir aplicaciones múltiples
     */
    private function remove_unique_constraint() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aplicaciones_minfin';
        
        // Verificar si el índice existe antes de intentar eliminarlo
        $index_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE table_schema = DATABASE() 
             AND table_name = %s 
             AND index_name = 'unique_aplicacion'",
            $table_name
        ));
        
        if ($index_exists > 0) {
            // Eliminar el índice único
            $wpdb->query("ALTER TABLE {$table_name} DROP INDEX unique_aplicacion");
            error_log('Vacantes Plugin: Índice único "unique_aplicacion" eliminado para permitir aplicaciones múltiples');
        }
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function init() {
        // Procesar formularios del admin
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_vacante':
                    $this->process_save_vacante();
                    break;
                case 'save_direccion':
                    $this->process_save_direccion();
                    break;
                case 'save_tipo_contrato':
                    $this->process_save_tipo_contrato();
                    break;
            }
        }
        
        // Procesar acciones GET (como eliminar)
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'delete':
                    if (isset($_GET['page']) && $_GET['page'] === 'direcciones-list') {
                        $this->process_delete_direccion();
                    }
                    break;
            }
        }
        
        // Registrar configuraciones
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('vacantes_config', 'vacantes_email_admin');
        register_setting('vacantes_config', 'vacantes_max_file_size');
        register_setting('vacantes_config', 'vacantes_allowed_files');
        register_setting('vacantes_config', 'vacantes_minfin_enable_notifications');
    }
    
    private function process_save_vacante() {
        if (!wp_verify_nonce($_POST['vacante_nonce'], 'save_vacante')) {
            wp_die('Error de seguridad');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        global $wpdb;
        
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
        
        if (isset($_POST['vacante_id']) && $_POST['vacante_id']) {
            // Actualizar vacante existente
            $result = $wpdb->update(
                $wpdb->prefix . 'vacantes_minfin',
                $data,
                array('id' => intval($_POST['vacante_id'])),
                array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s'),
                array('%d')
            );
            
            $message = 'Vacante actualizada exitosamente.';
        } else {
            // Crear nueva vacante
            $result = $wpdb->insert(
                $wpdb->prefix . 'vacantes_minfin',
                $data,
                array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s')
            );
            
            $message = 'Vacante creada exitosamente.';
        }
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=vacantes-list&message=success'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=vacantes-list&message=error'));
            exit;
        }
    }
    
    private function process_save_direccion() {
        if (!wp_verify_nonce($_POST['direccion_nonce'], 'save_direccion')) {
            wp_die('Error de seguridad');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        global $wpdb;
        
        $data = array(
            'nombre' => sanitize_text_field($_POST['nombre']),
            'descripcion' => sanitize_textarea_field($_POST['descripcion']),
            'activa' => intval($_POST['activa'])
        );
        
        if (isset($_POST['direccion_id']) && $_POST['direccion_id']) {
            // Actualizar dirección existente
            $result = $wpdb->update(
                $wpdb->prefix . 'direcciones_minfin',
                $data,
                array('id' => intval($_POST['direccion_id'])),
                array('%s', '%s', '%d'),
                array('%d')
            );
            
            $message = 'Dirección actualizada exitosamente.';
        } else {
            // Crear nueva dirección
            $result = $wpdb->insert(
                $wpdb->prefix . 'direcciones_minfin',
                $data,
                array('%s', '%s', '%d')
            );
            
            $message = 'Dirección creada exitosamente.';
        }
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=success'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=error'));
            exit;
        }
    }
    
    private function process_delete_direccion() {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        $direccion_id = intval($_GET['id'] ?? 0);
        if (!$direccion_id) {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=error'));
            exit;
        }
        
        global $wpdb;
        
        // Verificar que no tenga vacantes asociadas
        $vacantes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE direccion_id = %d", $direccion_id
        ));
        
        if ($vacantes_count > 0) {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=error_has_vacantes'));
            exit;
        }
        
        // Eliminar la dirección
        $result = $wpdb->delete(
            $wpdb->prefix . 'direcciones_minfin',
            array('id' => $direccion_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=direcciones-list&message=error'));
            exit;
        }
    }
    
    private function process_save_tipo_contrato() {
        if (!wp_verify_nonce($_POST['tipo_contrato_nonce'], 'save_tipo_contrato')) {
            wp_die('Error de seguridad');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        global $wpdb;
        
        $data = array(
            'codigo' => sanitize_text_field($_POST['codigo']),
            'nombre' => sanitize_text_field($_POST['nombre']),
            'activo' => intval($_POST['activo'])
        );
        
        if (isset($_POST['tipo_contrato_id']) && $_POST['tipo_contrato_id']) {
            // Actualizar tipo existente
            $result = $wpdb->update(
                $wpdb->prefix . 'tipos_contrato_minfin',
                $data,
                array('id' => intval($_POST['tipo_contrato_id'])),
                array('%s', '%s', '%d'),
                array('%d')
            );
            
            $message = 'Tipo de contrato actualizado exitosamente.';
        } else {
            // Crear nuevo tipo
            $result = $wpdb->insert(
                $wpdb->prefix . 'tipos_contrato_minfin',
                $data,
                array('%s', '%s', '%d')
            );
            
            $message = 'Tipo de contrato creado exitosamente.';
        }
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=tipos-contrato-list&message=success'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=tipos-contrato-list&message=error'));
            exit;
        }
    }
    
    public function admin_menu() {
        // Menú principal
        add_menu_page(
            'Vacantes MINFIN',
            'Vacantes',
            'manage_options',
            'vacantes-minfin',
            array($this, 'admin_dashboard'),
            'dashicons-groups',
            30
        );
        
        // Submenús
        add_submenu_page(
            'vacantes-minfin',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vacantes-minfin',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Gestionar Vacantes',
            'Vacantes',
            'manage_options',
            'vacantes-list',
            array($this, 'admin_vacantes')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Aplicaciones',
            'Aplicaciones',
            'manage_options',
            'aplicaciones-list',
            array($this, 'admin_aplicaciones')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Direcciones',
            'Direcciones',
            'manage_options',
            'direcciones-list',
            array($this, 'admin_direcciones')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Tipos de Contrato',
            'Tipos de Contrato',
            'manage_options',
            'tipos-contrato-list',
            array($this, 'admin_tipos_contrato')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Reportes',
            'Reportes',
            'manage_options',
            'reportes',
            array($this, 'admin_reportes')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Configuración',
            'Configuración',
            'manage_options',
            'vacantes-config',
            array($this, 'admin_config')
        );
        
        add_submenu_page(
            'vacantes-minfin',
            'Utilidades',
            'Utilidades',
            'manage_options',
            'vacantes-utilidades',
            array($this, 'admin_utilidades')
        );
        

    }
    
    public function admin_dashboard() {
        // Incluir la página moderna del dashboard
        include_once plugin_dir_path(__FILE__) . 'admin/pages/dashboard-modern.php';
    }
    
    public function admin_vacantes() {
        // Incluir la página moderna de vacantes
        include_once plugin_dir_path(__FILE__) . 'admin/pages/vacantes-list-modern.php';
    }
    
    private function admin_vacantes_list() {
        global $wpdb;
        
        $vacantes = $wpdb->get_results("
            SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre
            FROM {$wpdb->prefix}vacantes_minfin v
            LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
            LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
            ORDER BY v.fecha_creacion DESC
        ");
        
        ?>
        <div class="wrap">
            <h1>Gestión de Vacantes 
                <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=add'); ?>" class="page-title-action">Añadir Nueva</a>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th>Fecha Límite</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vacantes)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                No hay vacantes registradas. <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=add'); ?>">Crear la primera vacante</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vacantes as $vacante): ?>
                        <tr>
                            <td><strong><?php echo esc_html($vacante->codigo); ?></strong></td>
                            <td><?php echo esc_html($vacante->titulo); ?></td>
                            <td><?php echo esc_html($vacante->direccion_nombre); ?></td>
                            <td>
                                <span style="background: <?php echo $vacante->estado === 'Activa' ? '#d4edda' : '#f8d7da'; ?>; 
                                             color: <?php echo $vacante->estado === 'Activa' ? '#155724' : '#721c24'; ?>; 
                                             padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                    <?php echo esc_html($vacante->estado); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($vacante->fecha_limite)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=edit&id=' . $vacante->id); ?>" class="button button-small">Editar</a>
                                <a href="<?php echo home_url('/vacante-detalle/?id=' . $vacante->id); ?>" class="button button-small" target="_blank">Ver</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function admin_vacante_form($vacante_id = 0) {
        global $wpdb;
        
        $vacante = null;
        if ($vacante_id > 0) {
            $vacante = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vacantes_minfin WHERE id = %d", $vacante_id
            ));
        }
        
        $direcciones = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}direcciones_minfin WHERE activa = 1");
        $tipos_contrato = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tipos_contrato_minfin WHERE activo = 1");
        
        ?>
        <div class="wrap">
            <h1><?php echo $vacante ? 'Editar Vacante' : 'Nueva Vacante'; ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_vacante', 'vacante_nonce'); ?>
                <input type="hidden" name="action" value="save_vacante">
                <?php if ($vacante): ?>
                    <input type="hidden" name="vacante_id" value="<?php echo $vacante->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="codigo">Código</label></th>
                        <td><input type="text" id="codigo" name="codigo" value="<?php echo $vacante ? esc_attr($vacante->codigo) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="titulo">Título</label></th>
                        <td><input type="text" id="titulo" name="titulo" value="<?php echo $vacante ? esc_attr($vacante->titulo) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="descripcion">Descripción</label></th>
                        <td><textarea id="descripcion" name="descripcion" rows="5" class="large-text"><?php echo $vacante ? esc_textarea($vacante->descripcion) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="requisitos">Requisitos</label></th>
                        <td><textarea id="requisitos" name="requisitos" rows="5" class="large-text"><?php echo $vacante ? esc_textarea($vacante->requisitos) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="direccion_id">Dirección</label></th>
                        <td>
                            <select id="direccion_id" name="direccion_id" required>
                                <option value="">Seleccionar dirección</option>
                                <?php foreach ($direcciones as $dir): ?>
                                    <option value="<?php echo $dir->id; ?>" <?php echo ($vacante && $vacante->direccion_id == $dir->id) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($dir->nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tipo_contrato_id">Tipo de Contrato</label></th>
                        <td>
                            <select id="tipo_contrato_id" name="tipo_contrato_id" required>
                                <option value="">Seleccionar tipo</option>
                                <?php foreach ($tipos_contrato as $tipo): ?>
                                    <option value="<?php echo $tipo->id; ?>" <?php echo ($vacante && $vacante->tipo_contrato_id == $tipo->id) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($tipo->nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="salario_min">Salario Mínimo</label></th>
                        <td><input type="number" id="salario_min" name="salario_min" value="<?php echo $vacante ? $vacante->salario_min : ''; ?>" class="regular-text" step="0.01"></td>
                    </tr>
                    <tr>
                        <th><label for="salario_max">Salario Máximo</label></th>
                        <td><input type="number" id="salario_max" name="salario_max" value="<?php echo $vacante ? $vacante->salario_max : ''; ?>" class="regular-text" step="0.01"></td>
                    </tr>
                    <tr>
                        <th><label for="fecha_limite">Fecha Límite</label></th>
                        <td><input type="date" id="fecha_limite" name="fecha_limite" value="<?php echo $vacante ? $vacante->fecha_limite : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="estado">Estado</label></th>
                        <td>
                            <select id="estado" name="estado">
                                <option value="Activa" <?php echo ($vacante && $vacante->estado === 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo ($vacante && $vacante->estado === 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                                <option value="Cerrada" <?php echo ($vacante && $vacante->estado === 'Cerrada') ? 'selected' : ''; ?>>Cerrada</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $vacante ? 'Actualizar Vacante' : 'Crear Vacante'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=vacantes-list'); ?>" class="button">Cancelar</a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function admin_aplicaciones() {
        include_once plugin_dir_path(__FILE__) . 'admin/pages/aplicaciones-list.php';
    }
    

    
    public function admin_direcciones() {
        // Incluir el archivo mejorado de direcciones
        include_once plugin_dir_path(__FILE__) . 'admin/pages/direcciones-list.php';
    }
    
    private function admin_direcciones_list() {
        global $wpdb;
        
        // Mostrar mensajes de notificación
        if (isset($_GET['message'])) {
            $message = $_GET['message'];
            $class = 'notice-success';
            $text = '';
            
            switch ($message) {
                case 'success':
                    $text = 'Operación realizada exitosamente.';
                    break;
                case 'deleted':
                    $text = 'Dirección eliminada exitosamente.';
                    break;
                case 'error':
                    $text = 'Error al realizar la operación.';
                    $class = 'notice-error';
                    break;
                case 'error_has_vacantes':
                    $text = 'No se puede eliminar la dirección porque tiene vacantes asociadas.';
                    $class = 'notice-error';
                    break;
            }
            
            if ($text) {
                echo '<div class="notice ' . $class . ' is-dismissible"><p>' . $text . '</p></div>';
            }
        }
        
        // Obtener filtros
        $search = sanitize_text_field($_GET['s'] ?? '');
        $estado_filtro = sanitize_text_field($_GET['estado'] ?? '');
        
        // Construir consulta con filtros
        $where_conditions = array();
        $query_params = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }
        
        if (!empty($estado_filtro)) {
            if ($estado_filtro === 'activa') {
                $where_conditions[] = "activa = 1";
            } elseif ($estado_filtro === 'inactiva') {
                $where_conditions[] = "activa = 0";
            }
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT * FROM {$wpdb->prefix}direcciones_minfin {$where_clause} ORDER BY nombre ASC";
        
        if (!empty($query_params)) {
            $direcciones = $wpdb->get_results($wpdb->prepare($sql, $query_params));
        } else {
            $direcciones = $wpdb->get_results($sql);
        }
        
        // Obtener estadísticas
        $total_direcciones = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin");
        $direcciones_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin WHERE activa = 1");
        $direcciones_inactivas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin WHERE activa = 0");
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <i class="fas fa-building"></i> Gestión de Direcciones
                <a href="<?php echo admin_url('admin.php?page=direcciones-list&action=add'); ?>" class="page-title-action">
                    <i class="fas fa-plus"></i> Agregar Nueva
                </a>
            </h1>
            
            <!-- Estadísticas -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary bg-opacity-10">
                        <div class="card-body text-center">
                            <i class="fas fa-building fa-2x text-primary mb-2"></i>
                            <h4><?php echo $total_direcciones; ?></h4>
                            <small>Total Direcciones</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success bg-opacity-10">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <h4><?php echo $direcciones_activas; ?></h4>
                            <small>Activas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning bg-opacity-10">
                        <div class="card-body text-center">
                            <i class="fas fa-pause-circle fa-2x text-warning mb-2"></i>
                            <h4><?php echo $direcciones_inactivas; ?></h4>
                            <small>Inactivas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info bg-opacity-10">
                        <div class="card-body text-center">
                            <i class="fas fa-briefcase fa-2x text-info mb-2"></i>
                            <h4><?php echo $wpdb->get_var("SELECT COUNT(DISTINCT direccion_id) FROM {$wpdb->prefix}vacantes_minfin WHERE direccion_id IS NOT NULL"); ?></h4>
                            <small>Con Vacantes</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Direcciones -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Lista de Direcciones (<?php echo count($direcciones); ?>)</h5>
                    
                    <form method="get" class="search-form">
                        <input type="hidden" name="page" value="direcciones-list">
                        <div class="input-group input-group-sm">
                            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                                   placeholder="Buscar direcciones..." class="form-control">
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="activa" <?php selected($estado_filtro, 'activa'); ?>>Activas</option>
                                <option value="inactiva" <?php selected($estado_filtro, 'inactiva'); ?>>Inactivas</option>
                            </select>
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">Icono</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Vacantes</th>
                                <th width="150">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($direcciones)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay direcciones registradas</p>
                                        <?php if (empty($search) && empty($estado_filtro)): ?>
                                        <a href="<?php echo admin_url('admin.php?page=direcciones-list&action=add'); ?>" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Crear Primera Dirección
                                        </a>
                                        <?php else: ?>
                                        <p class="small">Intenta cambiar los filtros de búsqueda</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($direcciones as $dir): 
                                $vacantes_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE direccion_id = %d", $dir->id
                                ));
                            ?>
                            <tr>
                                <td class="text-center">
                                    <i class="fas fa-building fa-lg text-primary"></i>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($dir->nombre); ?></strong>
                                </td>
                                <td>
                                    <?php if ($dir->descripcion): ?>
                                    <span class="text-muted"><?php echo esc_html(wp_trim_words($dir->descripcion, 10)); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dir->activa): ?>
                                    <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $vacantes_count; ?></span>
                                    <div class="small text-muted">vacantes</div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo admin_url('admin.php?page=direcciones-list&action=edit&id=' . $dir->id); ?>" 
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=vacantes-list&direccion_id=' . $dir->id); ?>" 
                                           class="btn btn-outline-info" title="Ver Vacantes">
                                            <i class="fas fa-briefcase"></i>
                                        </a>
                                        <?php if ($vacantes_count == 0): ?>
                                        <a href="<?php echo admin_url('admin.php?page=direcciones-list&action=delete&id=' . $dir->id); ?>" 
                                           class="btn btn-outline-danger" title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar esta dirección?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        <?php
    }
    
    private function admin_direccion_form($direccion_id = 0) {
        global $wpdb;
        
        $direccion = null;
        if ($direccion_id > 0) {
            $direccion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}direcciones_minfin WHERE id = %d", $direccion_id
            ));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $direccion ? 'Editar Dirección' : 'Nueva Dirección'; ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_direccion', 'direccion_nonce'); ?>
                <input type="hidden" name="action" value="save_direccion">
                <?php if ($direccion): ?>
                    <input type="hidden" name="direccion_id" value="<?php echo $direccion->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="nombre">Nombre de la Dirección</label></th>
                        <td>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo $direccion ? esc_attr($direccion->nombre) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description">Ejemplo: Dirección de Recursos Humanos</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="descripcion">Descripción</label></th>
                        <td>
                            <textarea id="descripcion" name="descripcion" rows="4" class="large-text"><?php echo $direccion ? esc_textarea($direccion->descripcion) : ''; ?></textarea>
                            <p class="description">Breve descripción de las funciones de esta dirección.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="activa">Estado</label></th>
                        <td>
                            <select id="activa" name="activa">
                                <option value="1" <?php echo (!$direccion || $direccion->activa == 1) ? 'selected' : ''; ?>>Activa</option>
                                <option value="0" <?php echo ($direccion && $direccion->activa == 0) ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                            <p class="description">Solo las direcciones activas aparecerán en el sitio público.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $direccion ? 'Actualizar Dirección' : 'Crear Dirección'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=direcciones-list'); ?>" class="button">Cancelar</a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function admin_tipos_contrato() {
        // Incluir el archivo mejorado de tipos de contrato
        include_once plugin_dir_path(__FILE__) . 'admin/pages/tipos-contrato-list.php';
    }
    
    private function admin_tipos_contrato_list() {
        global $wpdb;
        
        $tipos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tipos_contrato_minfin ORDER BY nombre ASC");
        
        ?>
        <div class="wrap">
            <h1>Tipos de Contrato 
                <a href="<?php echo admin_url('admin.php?page=tipos-contrato-list&action=add'); ?>" class="page-title-action">Añadir Nuevo</a>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Vacantes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                No hay tipos de contrato registrados. <a href="<?php echo admin_url('admin.php?page=tipos-contrato-list&action=add'); ?>">Crear el primer tipo</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tipos as $tipo): 
                            $vacantes_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_minfin WHERE tipo_contrato_id = %d", $tipo->id
                            ));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($tipo->codigo); ?></strong></td>
                            <td><?php echo esc_html($tipo->nombre); ?></td>
                            <td>
                                <span style="background: <?php echo $tipo->activo ? '#d4edda' : '#f8d7da'; ?>; 
                                             color: <?php echo $tipo->activo ? '#155724' : '#721c24'; ?>; 
                                             padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                    <?php echo $tipo->activo ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td><?php echo $vacantes_count; ?> vacantes</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=tipos-contrato-list&action=edit&id=' . $tipo->id); ?>" class="button button-small">Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function admin_tipo_contrato_form($tipo_id = 0) {
        global $wpdb;
        
        $tipo = null;
        if ($tipo_id > 0) {
            $tipo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tipos_contrato_minfin WHERE id = %d", $tipo_id
            ));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $tipo ? 'Editar Tipo de Contrato' : 'Nuevo Tipo de Contrato'; ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_tipo_contrato', 'tipo_contrato_nonce'); ?>
                <input type="hidden" name="action" value="save_tipo_contrato">
                <?php if ($tipo): ?>
                    <input type="hidden" name="tipo_contrato_id" value="<?php echo $tipo->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="codigo">Código</label></th>
                        <td>
                            <input type="text" id="codigo" name="codigo" 
                                   value="<?php echo $tipo ? esc_attr($tipo->codigo) : ''; ?>" 
                                   class="regular-text" required maxlength="20">
                            <p class="description">Código único para identificar el tipo de contrato (ej: CTI, CTD, CONS)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nombre">Nombre</label></th>
                        <td>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo $tipo ? esc_attr($tipo->nombre) : ''; ?>" 
                                   class="regular-text" required>
                            <p class="description">Nombre descriptivo del tipo de contrato</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="activo">Estado</label></th>
                        <td>
                            <select id="activo" name="activo">
                                <option value="1" <?php echo (!$tipo || $tipo->activo == 1) ? 'selected' : ''; ?>>Activo</option>
                                <option value="0" <?php echo ($tipo && $tipo->activo == 0) ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                            <p class="description">Solo los tipos activos aparecerán al crear vacantes.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $tipo ? 'Actualizar Tipo' : 'Crear Tipo'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=tipos-contrato-list'); ?>" class="button">Cancelar</a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function admin_reportes() {
        // Incluir la página moderna de reportes
        include_once plugin_dir_path(__FILE__) . 'admin/pages/reportes-list-modern.php';
    }
    
    public function admin_config() {
        ?>
        <div class="wrap">
            <h1>Configuración del Sistema</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('vacantes_config'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Email de Notificaciones</th>
                        <td>
                            <input type="email" name="vacantes_email_admin" value="<?php echo esc_attr(get_option('vacantes_email_admin', get_option('admin_email'))); ?>" class="regular-text">
                            <p class="description">Email donde se enviarán las notificaciones de nuevas aplicaciones.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tamaño máximo de archivos</th>
                        <td>
                            <input type="number" name="vacantes_max_file_size" value="<?php echo esc_attr(get_option('vacantes_max_file_size', 5)); ?>" class="small-text"> MB
                            <p class="description">Tamaño máximo permitido para archivos CV y cartas de presentación.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tipos de archivo permitidos</th>
                        <td>
                            <input type="text" name="vacantes_allowed_files" value="<?php echo esc_attr(get_option('vacantes_allowed_files', 'pdf,doc,docx')); ?>" class="regular-text">
                            <p class="description">Extensiones de archivo permitidas, separadas por comas.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Notificaciones por Email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vacantes_minfin_enable_notifications" value="1" <?php checked(get_option('vacantes_minfin_enable_notifications', 1), 1); ?>>
                                Enviar emails de confirmación y notificaciones
                            </label>
                            <p class="description">Habilita el envío de emails de confirmación a aplicantes y notificaciones a administradores.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function admin_utilidades() {
        ?>
        <div class="wrap">
            <h1>Utilidades del Sistema</h1>
            
            <div class="card" style="max-width: 800px; padding: 20px; margin: 20px 0; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2>🔧 Solución de Problemas</h2>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107;">
                    <h3 style="margin-top: 0;">Eliminar Restricción de Aplicaciones Duplicadas</h3>
                    <p><strong>Problema:</strong> Error "Duplicate entry for key 'unique_aplicacion'" al enviar aplicaciones.</p>
                    <p><strong>Solución:</strong> Eliminar el índice único de la base de datos para permitir múltiples aplicaciones por persona.</p>
                    
                    <button type="button" id="remove-unique-constraint" class="button button-primary" style="background: #dc3545; border-color: #dc3545;">
                        🗑️ Eliminar Índice Único
                    </button>
                    <div id="constraint-result" style="margin-top: 10px;"></div>
                </div>
                
                <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #17a2b8;">
                    <h3 style="margin-top: 0;">ℹ️ Información</h3>
                    <p>Esta utilidad elimina la restricción que impide que una persona aplique múltiples veces a las vacantes.</p>
                    <p><strong>Nota:</strong> Esta acción es segura y no afecta los datos existentes, solo permite aplicaciones múltiples.</p>
                </div>
            </div>
        </div>
        
        <script>
        document.getElementById('remove-unique-constraint').addEventListener('click', function() {
            const button = this;
            const resultDiv = document.getElementById('constraint-result');
            
            button.disabled = true;
            button.textContent = 'Procesando...';
            resultDiv.innerHTML = '<p style="color: #856404;">Eliminando índice único...</p>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove_unique_constraint&_wpnonce=' + '<?php echo wp_create_nonce("remove_constraint"); ?>'
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('INDICE_ELIMINADO_OK')) {
                    resultDiv.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-top: 10px;"><strong>✅ Éxito:</strong> Índice único eliminado correctamente. Ahora se pueden enviar aplicaciones múltiples.</div>';
                    button.style.display = 'none';
                } else {
                    resultDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px;"><strong>❌ Error:</strong> ' + data + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px;"><strong>❌ Error:</strong> ' + error + '</div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '🗑️ Eliminar Índice Único';
            });
        });
        </script>
        <?php
    }
    
    public function admin_scripts($hook) {
        // Solo cargar en páginas del plugin
        if (strpos($hook, 'vacantes-minfin') === false && 
            strpos($hook, 'aplicaciones-list') === false && 
            strpos($hook, 'vacantes_page_aplicaciones-list') === false) {
            return;
        }
        
        // Cargar Bootstrap 5 CSS
        wp_enqueue_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );
        
        // Cargar Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Cargar Bootstrap 5 JS
        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array(),
            '5.3.0',
            true
        );
        
        // Cargar estilos personalizados del admin
        wp_enqueue_style(
            'vacantes-admin-styles',
            plugin_dir_url(__FILE__) . 'admin/css/admin-styles.css',
            array('bootstrap-css'),
            '1.0.0'
        );
        
        // Cargar estilos específicos para aplicaciones Bootstrap
        wp_enqueue_style(
            'aplicaciones-bootstrap-styles',
            plugin_dir_url(__FILE__) . 'admin/css/aplicaciones-bootstrap.css',
            array('bootstrap-css', 'font-awesome'),
            '1.0.0'
        );
        
        // Cargar scripts personalizados del admin
        wp_enqueue_script(
            'vacantes-admin-scripts',
            plugin_dir_url(__FILE__) . 'admin/js/admin-scripts.js',
            array('jquery', 'bootstrap-js'),
            '1.0.0',
            true
        );
    }
    
    public function public_scripts() {
        // Cargar scripts públicos
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'vacantes-public-js',
            plugin_dir_url(__FILE__) . 'public/js/vacantes-public.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localizar script con datos AJAX
        wp_localize_script('vacantes-public-js', 'vacantes_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vacantes_ajax_nonce')
        ));
        
        wp_enqueue_style(
            'vacantes-public-css',
            plugin_dir_url(__FILE__) . 'public/css/vacantes-public.css',
            array(),
            '1.0.0'
        );
    }
    
    // Shortcodes
    public function shortcode_vacantes_laborales($atts) {
        $atts = shortcode_atts(array(), $atts);
        
        ob_start();
        include VACANTES_SIMPLE_DIR . 'templates/public-vacantes-laborales.php';
        return ob_get_clean();
    }
    
    public function shortcode_vacantes_direccion($atts) {
        $atts = shortcode_atts(array(
            'direccion_id' => 0
        ), $atts);
        
        ob_start();
        include VACANTES_SIMPLE_DIR . 'templates/public-lista-vacantes-direccion-simple.php';
        return ob_get_clean();
    }
    
    public function shortcode_vacante_detalle($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        ob_start();
        include VACANTES_SIMPLE_DIR . 'templates/public-vacante-detalle-simple.php';
        return ob_get_clean();
    }
    
    public function shortcode_aplicar_vacante($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        ob_start();
        include VACANTES_SIMPLE_DIR . 'templates/public-aplicar-vacante-simple.php';
        return ob_get_clean();
    }
    
    // Método de prueba AJAX
    public function ajax_test() {
        error_log('Test AJAX ejecutándose');
        echo 'TEST_AJAX_OK';
        wp_die();
    }
    
    // Método de debug más básico
    public function debug_test() {
        die('DEBUG_OK');
    }
    
    // Test de formulario más básico
    public function ajax_form_test() {
        // Configurar headers para respuesta limpia
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        global $wpdb;
        
        // Mapear los campos del formulario a los campos de la tabla
        $vacante_id = intval($_POST['vacante_id'] ?? 0);
        $nombres = sanitize_text_field($_POST['nombre'] ?? '');
        $apellidos = sanitize_text_field($_POST['apellidos'] ?? '');
        $dpi = sanitize_text_field($_POST['dpi'] ?? '');
        $email = sanitize_email($_POST['email'] ?? ''); // Ahora sí capturamos el email
        $telefono = sanitize_text_field($_POST['telefono1'] ?? '');
        $direccion = ''; // No viene como texto simple
        $nivel_educativo = sanitize_text_field($_POST['nivel_academico'] ?? '');
        $profesion = sanitize_text_field($_POST['profesion'] ?? '');
        
        // Validación básica
        if (empty($nombres) || empty($apellidos) || empty($dpi)) {
            echo 'ERROR: Faltan campos obligatorios';
            wp_die();
        }
        
        // Obtener datos de la vacante para emails
        $vacante_data = $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, d.nombre as direccion_nombre 
             FROM {$wpdb->prefix}vacantes_minfin v
             LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
             WHERE v.id = %d", 
            $vacante_id
        ));
        
        if (!$vacante_data) {
            echo 'ERROR: Vacante no encontrada';
            wp_die();
        }
        
        // Manejar archivo CV
        $cv_url = '';
        if (!empty($_FILES['cv_file']['name'])) {
            $cv_upload = $this->handle_file_upload($_FILES['cv_file'], 'cv');
            if ($cv_upload['success']) {
                $cv_url = $cv_upload['file_path'];
            } else {
                echo 'ERROR_CV: ' . $cv_upload['error'];
                wp_die();
            }
        }
        
        // Insertar en la base de datos
        $result = $wpdb->insert(
            $wpdb->prefix . 'aplicaciones_minfin',
            array(
                'vacante_id' => $vacante_id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dpi' => $dpi,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'nivel_educativo' => $nivel_educativo,
                'profesion' => $profesion,
                'cv_url' => $cv_url,
                'estado' => 'Pendiente',
                'fecha_aplicacion' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            // ENVIAR EMAILS DE NOTIFICACIÓN
            $this->send_application_notifications($vacante_data, array(
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'nombre_completo' => $nombres . ' ' . $apellidos,
                'dpi' => $dpi,
                'email' => $email,
                'telefono' => $telefono,
                'nivel_educativo' => $nivel_educativo,
                'profesion' => $profesion
            ));
            
            // Limpiar cualquier output previo y enviar respuesta limpia
            if (ob_get_level()) {
                ob_clean();
            }
            echo 'APLICACION_GUARDADA_OK';
        } else {
            if (ob_get_level()) {
                ob_clean();
            }
            echo 'ERROR_BD: ' . $wpdb->last_error;
        }
        
        wp_die();
    }
    
    /**
     * AJAX para eliminar índice único manualmente
     */
    public function ajax_remove_unique_constraint() {
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            echo 'ERROR: Sin permisos de administrador';
            wp_die();
        }
        
        $this->remove_unique_constraint();
        echo 'INDICE_ELIMINADO_OK: Índice único eliminado correctamente';
        wp_die();
    }
    
    /**
     * Manejar subida de archivos CV
     */
    private function handle_file_upload($file, $type = 'cv') {
        // Verificar que se subió un archivo
        if (empty($file['name'])) {
            return array('success' => false, 'error' => 'No se seleccionó ningún archivo');
        }
        
        // Verificar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'error' => 'Error al subir el archivo');
        }
        
        // Configuración
        $max_size = get_option('vacantes_max_file_size', 5) * 1024 * 1024; // MB a bytes
        $allowed_types = explode(',', get_option('vacantes_allowed_files', 'pdf,doc,docx'));
        $allowed_types = array_map('trim', $allowed_types);
        
        // Validar tamaño
        if ($file['size'] > $max_size) {
            return array('success' => false, 'error' => 'El archivo es demasiado grande. Máximo ' . get_option('vacantes_max_file_size', 5) . 'MB');
        }
        
        // Validar tipo de archivo
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            return array('success' => false, 'error' => 'Tipo de archivo no permitido. Solo: ' . implode(', ', $allowed_types));
        }
        
        // Crear directorio si no existe
        $upload_dir = wp_upload_dir();
        $vacantes_dir = $upload_dir['basedir'] . '/vacantes-cv/';
        
        if (!file_exists($vacantes_dir)) {
            wp_mkdir_p($vacantes_dir);
        }
        
        // Generar nombre único
        $filename = sanitize_file_name($file['name']);
        $filename = time() . '_' . $filename;
        $file_path = $vacantes_dir . $filename;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'success' => true, 
                'file_path' => $upload_dir['baseurl'] . '/vacantes-cv/' . $filename,
                'file_name' => $filename
            );
        } else {
            return array('success' => false, 'error' => 'Error al guardar el archivo');
        }
    }
    
    /**
     * Enviar notificaciones de email para nueva aplicación
     */
    private function send_application_notifications($vacante_data, $aplicacion_data) {
        // Incluir el servicio de email si no está cargado
        if (!class_exists('Vacantes_Email_Service')) {
            require_once plugin_dir_path(__FILE__) . 'services/class-email-service.php';
        }
        
        $email_service = new Vacantes_Email_Service();
        
        // Enviar confirmación al aplicante (solo si tiene email)
        if (!empty($aplicacion_data['email']) && is_email($aplicacion_data['email'])) {
            try {
                $email_service->send_aplicacion_confirmacion($aplicacion_data, $vacante_data);
            } catch (Exception $e) {
                error_log('Error enviando confirmación al aplicante: ' . $e->getMessage());
            }
        }
        
        // Enviar notificación a administradores
        try {
            $email_service->send_nueva_aplicacion_admin($aplicacion_data, $vacante_data);
        } catch (Exception $e) {
            error_log('Error enviando notificación a administradores: ' . $e->getMessage());
        }
    }
    
    // Método específico para el formulario
    public function ajax_enviar_aplicacion() {
        // Configurar headers para respuesta limpia
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        error_log('FORMULARIO RECIBIDO - Método ajax_enviar_aplicacion ejecutándose');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
        
        global $wpdb;
        
        // Mapear los campos del formulario a los campos de la tabla
        $vacante_id = intval($_POST['vacante_id'] ?? 0);
        $nombres = sanitize_text_field($_POST['nombre'] ?? '');
        $apellidos = sanitize_text_field($_POST['apellidos'] ?? '');
        $dpi = sanitize_text_field($_POST['dpi'] ?? '');
        $email = sanitize_email($_POST['email'] ?? ''); // Ahora sí capturamos el email
        $telefono = sanitize_text_field($_POST['telefono1'] ?? ''); // El formulario usa telefono1
        $direccion = ''; // No viene como texto, usar vacío
        $nivel_educativo = sanitize_text_field($_POST['nivel_academico'] ?? '');
        $profesion = sanitize_text_field($_POST['profesion'] ?? '');
        
        // Validación básica
        if (empty($nombres) || empty($apellidos) || empty($dpi)) {
            echo 'ERROR: Faltan campos obligatorios (nombre, apellidos, dpi)';
            wp_die();
        }
        
        // Obtener datos de la vacante para emails
        $vacante_data = $wpdb->get_row($wpdb->prepare(
            "SELECT v.*, d.nombre as direccion_nombre 
             FROM {$wpdb->prefix}vacantes_minfin v
             LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
             WHERE v.id = %d", 
            $vacante_id
        ));
        
        if (!$vacante_data) {
            echo 'ERROR: Vacante no encontrada';
            wp_die();
        }
        
        // Insertar con los campos que existen en la tabla
        $result = $wpdb->insert(
            $wpdb->prefix . 'aplicaciones_minfin',
            array(
                'vacante_id' => $vacante_id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dpi' => $dpi,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'nivel_educativo' => $nivel_educativo,
                'profesion' => $profesion,
                'estado' => 'Pendiente',
                'fecha_aplicacion' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            // EMAILS DESACTIVADOS EN ESTE MÉTODO - Se envían desde ajax_form_test
            // $this->send_application_notifications($vacante_data, array(...));
            
            // Limpiar cualquier output previo y enviar respuesta limpia
            if (ob_get_level()) {
                ob_clean();
            }
            echo 'APLICACION_GUARDADA_OK';
        } else {
            error_log('Error al insertar aplicación: ' . $wpdb->last_error);
            if (ob_get_level()) {
                ob_clean();
            }
            echo 'ERROR_BD: ' . $wpdb->last_error;
        }
        
        wp_die();
    }
    
    // AJAX handler para guardar aplicaciones
    public function ajax_save_aplicacion() {
        // Log para debugging
        error_log('AJAX aplicar_vacante iniciado - Método ejecutándose');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
        
        // Respuesta simple para testing
        echo 'APLICACION_RECIBIDA_OK';
        wp_die();
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vacantes_ajax_nonce')) {
            error_log('Error de nonce');
            echo 'ERROR_NONCE';
            wp_die();
        }
        
        global $wpdb;
        
        // Obtener datos del formulario básico
        $vacante_id = intval($_POST['vacante_id']);
        $nombre_completo = sanitize_text_field($_POST['nombre']);
        $email = sanitize_email($_POST['email']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $direccion = sanitize_textarea_field($_POST['direccion']);
        
        // Separar nombre y apellidos
        $nombre_parts = explode(' ', $nombre_completo, 2);
        $nombres = $nombre_parts[0];
        $apellidos = isset($nombre_parts[1]) ? $nombre_parts[1] : '';
        
        // Validaciones básicas
        if (empty($nombres) || empty($email) || empty($telefono)) {
            wp_send_json_error('Por favor completa todos los campos obligatorios');
            return;
        }
        
        // Validar email
        if (!is_email($email)) {
            wp_send_json_error('Por favor ingresa un email válido');
            return;
        }
        
        // Verificar que la vacante existe
        $vacante = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vacantes_minfin WHERE id = %d AND estado = 'Activa'",
            $vacante_id
        ));
        
        if (!$vacante) {
            wp_send_json_error('La vacante no existe o ya no está activa.');
            return;
        }
        
        // Verificar si ya aplicó antes (por email)
        $aplicacion_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}aplicaciones_minfin WHERE vacante_id = %d AND email = %s",
            $vacante_id, $email
        ));
        
        if ($aplicacion_existente) {
            wp_send_json_error('Ya has aplicado a esta vacante anteriormente.');
            return;
        }
        
        // Manejar archivo CV
        $cv_file = '';
        
        if (!empty($_FILES['cv']['name'])) {
            $cv_upload = $this->handle_file_upload($_FILES['cv'], 'cv');
            if ($cv_upload['success']) {
                $cv_file = $cv_upload['file_path'];
            } else {
                wp_send_json_error('Error al subir el CV: ' . $cv_upload['error']);
                return;
            }
        }
        
        // Insertar aplicación en la base de datos
        $result = $wpdb->insert(
            $wpdb->prefix . 'aplicaciones_minfin',
            array(
                'vacante_id' => $vacante_id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dpi' => '', // Campo requerido pero vacío por ahora
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'cv_url' => $cv_file,
                'estado' => 'Pendiente',
                'fecha_aplicacion' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            // Log del error para debugging
            error_log('Error al insertar aplicación: ' . $wpdb->last_error);
            wp_send_json_error('Error al guardar la aplicación: ' . $wpdb->last_error);
            return;
        }
        
        // Respuesta exitosa
        wp_send_json_success('¡Aplicación enviada exitosamente!');
    }
    


}

// Inicializar el plugin
new Vacantes_Simple();

// Incluir nuevos handlers AJAX para reportes
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers-nuevo.php';