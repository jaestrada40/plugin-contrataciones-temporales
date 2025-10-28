<?php
/**
 * Clase principal del plugin Vacantes MINFIN
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Core {
    
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->plugin_name = 'vacantes-minfin';
        $this->version = VACANTES_MINFIN_VERSION;
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }
    
    private function load_dependencies() {
        // Cargar activador y desactivador
        $this->require_file('includes/class-vacantes-activator.php');
        $this->require_file('includes/class-vacantes-deactivator.php');
        
        // Cargar gestor de base de datos
        $this->require_file('includes/database/class-database-manager.php');
        
        // Cargar modelos
        $this->require_file('models/class-vacante.php');
        $this->require_file('models/class-aplicacion.php');
        $this->require_file('models/class-direccion.php');
        $this->require_file('models/class-tipo-contrato.php');
        
        // Cargar servicios
        $this->require_file('services/class-email-service.php');
        $this->require_file('services/class-file-service.php');
        $this->require_file('services/class-report-service.php');
        $this->require_file('services/class-notification-service.php');
        
        // Cargar admin
        $this->require_file('admin/class-vacantes-admin.php');
        
        // Cargar público
        $this->require_file('public/class-vacantes-public.php');
        
        // Cargar API
        $this->require_file('api/class-rest-api.php');
        
        // Cargar widgets
        $this->require_file('widgets/class-vacantes-widget.php');
        $this->require_file('widgets/class-stats-widget.php');
    }
    
    /**
     * Cargar archivo de forma segura
     */
    private function require_file($file_path) {
        $full_path = VACANTES_MINFIN_PLUGIN_DIR . $file_path;
        
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            error_log("Vacantes Plugin: No se pudo cargar el archivo: " . $file_path);
        }
    }
    
    private function set_locale() {
        // Configurar localización si es necesario
    }
    
    private function define_admin_hooks() {
        if (class_exists('Vacantes_Admin')) {
            $plugin_admin = new Vacantes_Admin($this->get_plugin_name(), $this->get_version());
            
            // Hooks del admin
            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
            add_action('admin_menu', array($plugin_admin, 'add_admin_menu'));
            add_action('admin_init', array($plugin_admin, 'admin_init'));
            
            // AJAX hooks para admin
            add_action('wp_ajax_vacantes_get_dashboard_data', array($plugin_admin, 'ajax_get_dashboard_data'));
            add_action('wp_ajax_vacantes_save_vacante', array($plugin_admin, 'ajax_save_vacante'));
            add_action('wp_ajax_vacantes_delete_vacante', array($plugin_admin, 'ajax_delete_vacante'));
            add_action('wp_ajax_vacantes_update_aplicacion_estado', array($plugin_admin, 'ajax_update_aplicacion_estado'));
        }
    }
    
    private function define_public_hooks() {
        if (class_exists('Vacantes_Public')) {
            $plugin_public = new Vacantes_Public($this->get_plugin_name(), $this->get_version());
            
            // Hooks públicos
            add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
            add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
            add_action('init', array($plugin_public, 'register_shortcodes'));
            
            // AJAX hooks para público (no requieren login)
            add_action('wp_ajax_nopriv_aplicar_vacante', array($plugin_public, 'handle_aplicacion_ajax'));
            add_action('wp_ajax_aplicar_vacante', array($plugin_public, 'handle_aplicacion_ajax'));
            add_action('wp_ajax_nopriv_buscar_vacantes', array($plugin_public, 'handle_buscar_vacantes_ajax'));
            add_action('wp_ajax_buscar_vacantes', array($plugin_public, 'handle_buscar_vacantes_ajax'));
            add_action('wp_ajax_nopriv_get_vacante_detalle', array($plugin_public, 'handle_get_vacante_detalle'));
            add_action('wp_ajax_get_vacante_detalle', array($plugin_public, 'handle_get_vacante_detalle'));
            add_action('wp_ajax_nopriv_get_formulario_aplicacion', array($plugin_public, 'handle_get_formulario_aplicacion'));
            add_action('wp_ajax_get_formulario_aplicacion', array($plugin_public, 'handle_get_formulario_aplicacion'));
        }
    }
    
    private function define_api_hooks() {
        if (class_exists('Vacantes_REST_API')) {
            $plugin_api = new Vacantes_REST_API();
            // Los hooks se registran en el constructor de la clase API
        }
    }
    
    public function run() {
        // El plugin está listo para ejecutarse
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_version() {
        return $this->version;
    }
}