<?php
/**
 * API REST para el plugin de Vacantes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_REST_API {

    private $namespace = 'vacantes/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Rutas públicas
        register_rest_route($this->namespace, '/vacantes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_vacantes'),
            'permission_callback' => '__return_true',
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'direccion' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'tipo_contrato' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'activas' => array(
                    'default' => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                )
            )
        ));

        register_rest_route($this->namespace, '/vacantes/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_vacante'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'sanitize_callback' => 'absint',
                )
            )
        ));

        register_rest_route($this->namespace, '/aplicaciones', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_aplicacion'),
            'permission_callback' => '__return_true',
            'args' => array(
                'vacante_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ),
                'nombre_completo' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                ),
                'telefono' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'direccion' => array(
                    'sanitize_callback' => 'sanitize_textarea_field',
                )
            )
        ));

        register_rest_route($this->namespace, '/direcciones', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_direcciones'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route($this->namespace, '/estadisticas', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_estadisticas'),
            'permission_callback' => '__return_true'
        ));

        // Rutas administrativas (requieren permisos)
        register_rest_route($this->namespace, '/admin/vacantes', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'admin_get_vacantes'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'admin_create_vacante'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));

        register_rest_route($this->namespace, '/admin/vacantes/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'admin_get_vacante'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'admin_update_vacante'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'admin_delete_vacante'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));

        register_rest_route($this->namespace, '/admin/aplicaciones', array(
            'methods' => 'GET',
            'callback' => array($this, 'admin_get_aplicaciones'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }

    /**
     * Obtener vacantes públicas
     */
    public function get_vacantes($request) {
        $vacante_model = new Vacante_Model();
        
        $filtros = array(
            'limite' => $request['per_page'],
            'direccion' => $request['direccion'],
            'tipo_contrato' => $request['tipo_contrato'],
            'activas' => $request['activas'] ? 'si' : 'no'
        );

        if (!empty($request['search'])) {
            $vacantes = $vacante_model->buscar_vacantes($request['search'], 
                                                       $request['direccion'], 
                                                       $request['tipo_contrato']);
        } else {
            $vacantes = $vacante_model->get_vacantes_publicas($filtros);
        }

        $data = array();
        foreach ($vacantes as $vacante) {
            $data[] = $this->prepare_vacante_for_response($vacante);
        }

        return rest_ensure_response($data);
    }

    /**
     * Obtener una vacante específica
     */
    public function get_vacante($request) {
        $vacante_model = new Vacante_Model();
        $vacante = $vacante_model->get_by_id($request['id']);

        if (!$vacante) {
            return new WP_Error('vacante_not_found', 'Vacante no encontrada', array('status' => 404));
        }

        return rest_ensure_response($this->prepare_vacante_for_response($vacante));
    }

    /**
     * Crear nueva aplicación
     */
    public function create_aplicacion($request) {
        // Validar que la vacante existe y está activa
        $vacante_model = new Vacante_Model();
        $vacante = $vacante_model->get_by_id($request['vacante_id']);

        if (!$vacante || !$vacante->activa || strtotime($vacante->fecha_fin) < time()) {
            return new WP_Error('vacante_not_available', 'Vacante no disponible', array('status' => 400));
        }

        // Verificar si ya aplicó
        $aplicacion_model = new Aplicacion_Model();
        if ($aplicacion_model->ya_aplico($request['email'], $request['vacante_id'])) {
            return new WP_Error('already_applied', 'Ya has aplicado a esta vacante', array('status' => 400));
        }

        // Crear aplicación
        $aplicacion_data = array(
            'vacante_id' => $request['vacante_id'],
            'nombre_completo' => $request['nombre_completo'],
            'email' => $request['email'],
            'telefono' => $request['telefono'],
            'direccion' => $request['direccion'],
            'estado' => 'pendiente',
            'fecha_aplicacion' => current_time('mysql')
        );

        $aplicacion = $aplicacion_model->create($aplicacion_data);

        if (is_wp_error($aplicacion)) {
            return $aplicacion;
        }

        // Enviar email de confirmación
        $email_service = new Vacantes_Email_Service();
        $email_service->enviar_confirmacion_aplicacion($aplicacion_data, $request['vacante_id']);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Aplicación enviada exitosamente',
            'aplicacion_id' => $aplicacion->id
        ));
    }

    /**
     * Obtener direcciones
     */
    public function get_direcciones($request) {
        $direccion_model = new Direccion_Model();
        $direcciones = $direccion_model->get_all();

        $data = array();
        foreach ($direcciones as $direccion) {
            $data[] = array(
                'id' => $direccion->id,
                'nombre' => $direccion->nombre,
                'correlativo' => $direccion->correlativo,
                'icono_url' => $direccion->icono_url
            );
        }

        return rest_ensure_response($data);
    }

    /**
     * Obtener estadísticas públicas
     */
    public function get_estadisticas($request) {
        $vacante_model = new Vacante_Model();
        $aplicacion_model = new Aplicacion_Model();
        
        $stats_vacantes = $vacante_model->get_stats();
        $stats_aplicaciones = $aplicacion_model->get_stats();

        $data = array(
            'vacantes_total' => $stats_vacantes['total'],
            'vacantes_vigentes' => $stats_vacantes['vigentes'],
            'aplicaciones_total' => $stats_aplicaciones['total'],
            'direcciones' => $stats_vacantes['por_direccion']
        );

        return rest_ensure_response($data);
    }

    /**
     * Preparar vacante para respuesta
     */
    private function prepare_vacante_for_response($vacante) {
        return array(
            'id' => $vacante->id,
            'codigo' => $vacante->codigo,
            'titulo' => $vacante->titulo,
            'descripcion' => $vacante->descripcion,
            'requisitos' => $vacante->requisitos,
            'salario' => $vacante->salario,
            'tipo_contrato' => $vacante->tipo_contrato,
            'fecha_inicio' => $vacante->fecha_inicio,
            'fecha_fin' => $vacante->fecha_fin,
            'direccion' => array(
                'id' => $vacante->direccion_id,
                'nombre' => $vacante->direccion_nombre
            ),
            'activa' => (bool) $vacante->activa,
            'total_aplicaciones' => isset($vacante->total_aplicaciones) ? $vacante->total_aplicaciones : 0,
            'fecha_creacion' => $vacante->fecha_creacion
        );
    }

    /**
     * Verificar permisos de administrador
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Métodos administrativos (simplificados)
     */
    public function admin_get_vacantes($request) {
        if (!$this->check_admin_permissions()) {
            return new WP_Error('forbidden', 'No tienes permisos', array('status' => 403));
        }

        $vacante_model = new Vacante_Model();
        $vacantes = $vacante_model->get_all();

        $data = array();
        foreach ($vacantes as $vacante) {
            $data[] = $this->prepare_vacante_for_response($vacante);
        }

        return rest_ensure_response($data);
    }

    public function admin_create_vacante($request) {
        if (!$this->check_admin_permissions()) {
            return new WP_Error('forbidden', 'No tienes permisos', array('status' => 403));
        }

        // Implementar creación de vacante
        return rest_ensure_response(array('message' => 'Funcionalidad en desarrollo'));
    }

    public function admin_get_aplicaciones($request) {
        if (!$this->check_admin_permissions()) {
            return new WP_Error('forbidden', 'No tienes permisos', array('status' => 403));
        }

        $aplicacion_model = new Aplicacion_Model();
        $aplicaciones = $aplicacion_model->get_all();

        return rest_ensure_response($aplicaciones);
    }
}