<?php

/**
 * Funcionalidad pública del plugin
 */
class Vacantes_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registrar estilos CSS
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/vacantes-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Registrar scripts JS
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/vacantes-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localizar script para AJAX
        wp_localize_script($this->plugin_name, 'vacantes_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vacantes_nonce')
        ));
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('vacantes_lista', array($this, 'shortcode_lista_vacantes'));
        add_shortcode('vacantes_aplicar', array($this, 'shortcode_aplicar_vacante'));
        add_shortcode('vacantes_buscar', array($this, 'shortcode_buscar_vacantes'));
    }

    /**
     * Shortcode para mostrar lista de vacantes
     */
    public function shortcode_lista_vacantes($atts) {
        $atts = shortcode_atts(array(
            'limite' => 10,
            'direccion' => '',
            'tipo_contrato' => '',
            'activas' => 'si'
        ), $atts);

        $vacante_model = new Vacante_Model();
        $vacantes = $vacante_model->get_vacantes_publicas($atts);

        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/lista-vacantes.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para formulario de aplicación
     */
    public function shortcode_aplicar_vacante($atts) {
        $atts = shortcode_atts(array(
            'vacante_id' => 0
        ), $atts);

        if (!$atts['vacante_id']) {
            return '<p>ID de vacante requerido.</p>';
        }

        $vacante_model = new Vacante_Model();
        $vacante = $vacante_model->get_by_id($atts['vacante_id']);

        if (!$vacante || $vacante->estado !== 'activa') {
            return '<p>Vacante no encontrada o no disponible.</p>';
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/formulario-aplicacion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para buscador de vacantes
     */
    public function shortcode_buscar_vacantes($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/buscador-vacantes.php';
        return ob_get_clean();
    }

    /**
     * Manejar envío de aplicación via AJAX
     */
    public function handle_aplicacion_ajax() {
        check_ajax_referer('vacantes_nonce', 'nonce');

        $vacante_id = intval($_POST['vacante_id']);
        $nombre = sanitize_text_field($_POST['nombre']);
        $email = sanitize_email($_POST['email']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $direccion = sanitize_textarea_field($_POST['direccion']);

        // Validaciones
        if (empty($nombre) || empty($email) || empty($telefono)) {
            wp_send_json_error('Todos los campos son obligatorios.');
        }

        if (!is_email($email)) {
            wp_send_json_error('Email inválido.');
        }

        // Verificar si ya aplicó
        $aplicacion_model = new Aplicacion_Model();
        if ($aplicacion_model->ya_aplico($email, $vacante_id)) {
            wp_send_json_error('Ya has aplicado a esta vacante.');
        }

        // Manejar archivo CV
        $cv_filename = '';
        if (!empty($_FILES['cv']['name'])) {
            $file_service = new Vacantes_File_Service();
            $upload_result = $file_service->upload_cv($_FILES['cv']);
            
            if ($upload_result['success']) {
                $cv_filename = $upload_result['filename'];
            } else {
                wp_send_json_error($upload_result['message']);
            }
        }

        // Crear aplicación
        $aplicacion_data = array(
            'vacante_id' => $vacante_id,
            'nombre_completo' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'cv_filename' => $cv_filename,
            'estado' => 'pendiente',
            'fecha_aplicacion' => current_time('mysql')
        );

        $aplicacion_id = $aplicacion_model->create($aplicacion_data);

        if ($aplicacion_id) {
            // Enviar email de confirmación
            $email_service = new Vacantes_Email_Service();
            $email_service->enviar_confirmacion_aplicacion($aplicacion_data, $vacante_id);

            wp_send_json_success('Aplicación enviada exitosamente.');
        } else {
            wp_send_json_error('Error al procesar la aplicación.');
        }
    }

    /**
     * Buscar vacantes via AJAX
     */
    public function handle_buscar_vacantes_ajax() {
        check_ajax_referer('vacantes_nonce', 'nonce');

        $termino = sanitize_text_field($_POST['termino']);
        $direccion_id = intval($_POST['direccion_id']);
        $tipo_contrato_id = intval($_POST['tipo_contrato_id']);

        $vacante_model = new Vacante_Model();
        $vacantes = $vacante_model->buscar_vacantes($termino, $direccion_id, $tipo_contrato);

        ob_start();
        foreach ($vacantes as $vacante) {
            include plugin_dir_path(__FILE__) . 'partials/vacante-item.php';
        }
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Obtener detalles de vacante via AJAX
     */
    public function handle_get_vacante_detalle() {
        check_ajax_referer('vacantes_nonce', 'nonce');

        $vacante_id = intval($_POST['vacante_id']);
        $vacante_model = new Vacante_Model();
        $vacante = $vacante_model->get_by_id($vacante_id);

        if (!$vacante) {
            wp_send_json_error('Vacante no encontrada.');
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/detalle-vacante.php';
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Obtener formulario de aplicación via AJAX
     */
    public function handle_get_formulario_aplicacion() {
        check_ajax_referer('vacantes_nonce', 'nonce');

        $vacante_id = intval($_POST['vacante_id']);
        $vacante_model = new Vacante_Model();
        $vacante = $vacante_model->get_by_id($vacante_id);

        if (!$vacante || $vacante->estado !== 'activa') {
            wp_send_json_error('Vacante no disponible.');
        }

        ob_start();
        include plugin_dir_path(__FILE__) . 'partials/formulario-aplicacion-modal.php';
        $html = ob_get_clean();

        wp_send_json_success($html);
    }
}