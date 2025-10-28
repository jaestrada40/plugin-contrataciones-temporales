<?php
/**
 * Activador del plugin Vacantes MINFIN
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Activator {
    
    public static function activate() {
        // Crear tablas de base de datos
        self::create_database_tables();
        
        // Insertar datos iniciales
        self::insert_initial_data();
        
        // Crear roles y capacidades
        self::create_roles_and_capabilities();
        
        // Crear páginas necesarias
        self::create_pages();
        
        // Configurar opciones por defecto
        self::set_default_options();
        
        // Crear directorio de uploads
        self::create_upload_directory();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_database_tables() {
        $db_file = VACANTES_MINFIN_PLUGIN_DIR . 'includes/database/class-database-manager.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            if (class_exists('Vacantes_Database_Manager')) {
                Vacantes_Database_Manager::create_tables();
            }
        }
    }
    
    private static function insert_initial_data() {
        $db_file = VACANTES_MINFIN_PLUGIN_DIR . 'includes/database/class-database-manager.php';
        if (file_exists($db_file)) {
            require_once $db_file;
            if (class_exists('Vacantes_Database_Manager')) {
                Vacantes_Database_Manager::insert_initial_data();
            }
        }
    }
    
    private static function create_roles_and_capabilities() {
        // Agregar capacidades al administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_vacantes');
            $admin_role->add_cap('edit_vacantes');
            $admin_role->add_cap('delete_vacantes');
            $admin_role->add_cap('view_aplicaciones');
            $admin_role->add_cap('manage_aplicaciones');
        }
        
        // Crear rol de gestor de vacantes
        add_role('gestor_vacantes', 'Gestor de Vacantes', array(
            'read' => true,
            'manage_vacantes' => true,
            'edit_vacantes' => true,
            'view_aplicaciones' => true,
            'manage_aplicaciones' => true
        ));
    }
    
    private static function create_pages() {
        // Crear página de vacantes si no existe
        $vacantes_page = get_page_by_path('vacantes');
        if (!$vacantes_page) {
            wp_insert_post(array(
                'post_title' => 'Vacantes Laborales',
                'post_name' => 'vacantes',
                'post_content' => '[vacantes_lista]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1
            ));
        }
        
        // Crear página de aplicación si no existe
        $aplicar_page = get_page_by_path('aplicar-vacante');
        if (!$aplicar_page) {
            wp_insert_post(array(
                'post_title' => 'Aplicar a Vacante',
                'post_name' => 'aplicar-vacante',
                'post_content' => '[vacantes_formulario]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1
            ));
        }
    }
    
    private static function set_default_options() {
        // Configuraciones por defecto
        $default_options = array(
            'vacantes_minfin_items_per_page' => 10,
            'vacantes_minfin_show_salary' => 1,
            'vacantes_minfin_require_cv' => 1,
            'vacantes_minfin_auto_approve_applications' => 0,
            'vacantes_minfin_enable_public_search' => 1,
            'vacantes_minfin_email_notifications' => 1,
            'vacantes_minfin_admin_email' => get_option('admin_email'),
            'vacantes_minfin_email_from_name' => get_bloginfo('name'),
            'vacantes_minfin_max_file_size' => 5,
            'vacantes_minfin_allowed_file_types' => 'pdf,doc,docx',
            'vacantes_minfin_api_timeout' => 30
        );
        
        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }
    
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $vacantes_dir = $upload_dir['basedir'] . '/vacantes-cv';
        
        if (!file_exists($vacantes_dir)) {
            wp_mkdir_p($vacantes_dir);
            
            // Crear archivo .htaccess para seguridad
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($vacantes_dir . '/.htaccess', $htaccess_content);
            
            // Crear archivo index.php vacío
            file_put_contents($vacantes_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    public static function deactivate() {
        // Limpiar tareas programadas
        wp_clear_scheduled_hook('vacantes_cleanup_expired');
        wp_clear_scheduled_hook('vacantes_send_notifications');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // Solo ejecutar si realmente se está desinstalando
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        // Eliminar opciones del plugin
        $options_to_delete = array(
            'vacantes_minfin_items_per_page',
            'vacantes_minfin_show_salary',
            'vacantes_minfin_require_cv',
            'vacantes_minfin_auto_approve_applications',
            'vacantes_minfin_enable_public_search',
            'vacantes_minfin_api_url',
            'vacantes_minfin_api_key',
            'vacantes_minfin_api_timeout',
            'vacantes_minfin_email_notifications',
            'vacantes_minfin_admin_email',
            'vacantes_minfin_email_from_name',
            'vacantes_minfin_max_file_size',
            'vacantes_minfin_allowed_file_types'
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // Eliminar roles personalizados
        remove_role('gestor_vacantes');
        
        // Eliminar capacidades del administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_vacantes');
            $admin_role->remove_cap('edit_vacantes');
            $admin_role->remove_cap('delete_vacantes');
            $admin_role->remove_cap('view_aplicaciones');
            $admin_role->remove_cap('manage_aplicaciones');
        }
        
        // Opcional: Eliminar tablas de base de datos
        // (Comentado por seguridad - descomentar solo si se desea eliminar todos los datos)
        /*
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vacantes_minfin");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}aplicaciones_minfin");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}direcciones_minfin");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tipos_contrato_minfin");
        */
    }
}