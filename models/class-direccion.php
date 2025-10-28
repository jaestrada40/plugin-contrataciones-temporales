<?php
/**
 * Modelo de Dirección
 */

if (!defined('ABSPATH')) {
    exit;
}

class Direccion_Model {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'vs_direcciones';
    }
    
    /**
     * Obtener todas las direcciones
     */
    public function get_all($args = array()) {
        $defaults = array(
            'activa' => null,
            'orderby' => 'nombre',
            'order' => 'ASC',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT d.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id) as total_vacantes,
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id AND v.activa = 1 AND v.fecha_fin >= CURDATE()) as vacantes_activas
                FROM {$this->table_name} d";
        
        $where = array();
        $values = array();
        
        if ($args['activa'] !== null) {
            $where[] = 'd.activa = %d';
            $values[] = $args['activa'] ? 1 : 0;
        }
        
        if (!empty($args['search'])) {
            $where[] = '(d.nombre LIKE %s OR d.descripcion LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY d.{$args['orderby']} {$args['order']}";
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener dirección por ID
     */
    public function get_by_id($id) {
        $sql = "SELECT d.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id) as total_vacantes,
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id AND v.activa = 1 AND v.fecha_fin >= CURDATE()) as vacantes_activas
                FROM {$this->table_name} d 
                WHERE d.id = %d";
        
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $id));
    }
    
    /**
     * Obtener dirección por correlativo
     */
    public function get_by_correlativo($correlativo) {
        $sql = "SELECT d.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id) as total_vacantes,
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.direccion_id = d.id AND v.activa = 1 AND v.fecha_fin >= CURDATE()) as vacantes_activas
                FROM {$this->table_name} d 
                WHERE d.correlativo = %s";
        
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $correlativo));
    }
    
    /**
     * Crear nueva dirección
     */
    public function create($data) {
        // Validar que el nombre sea único
        if ($this->nombre_exists($data['nombre'])) {
            return new WP_Error('nombre_duplicado', 'Ya existe una dirección con ese nombre');
        }
        
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'activa' => isset($data['activa']) ? $data['activa'] : 1
            ),
            array('%s', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_insercion', 'Error al crear la dirección');
        }
        
        $direccion_id = $this->wpdb->insert_id;
        
        // Log de auditoría
        $this->log_action('create', $direccion_id, null, $data);
        
        return $this->get_by_id($direccion_id);
    }
    
    /**
     * Actualizar dirección
     */
    public function update($id, $data) {
        $direccion_actual = $this->get_by_id($id);
        if (!$direccion_actual) {
            return new WP_Error('direccion_no_encontrada', 'Dirección no encontrada');
        }
        
        // Validar correlativo único si se está cambiando
        if (isset($data['correlativo']) && strtoupper($data['correlativo']) !== $direccion_actual->correlativo) {
            if ($this->correlativo_exists($data['correlativo'], $id)) {
                return new WP_Error('correlativo_duplicado', 'Ya existe una dirección con ese correlativo');
            }
        }
        
        // Validar nombre único si se está cambiando
        if (isset($data['nombre']) && $data['nombre'] !== $direccion_actual->nombre) {
            if ($this->nombre_exists($data['nombre'], $id)) {
                return new WP_Error('nombre_duplicado', 'Ya existe una dirección con ese nombre');
            }
        }
        
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array(
            'correlativo' => '%s',
            'nombre' => '%s',
            'descripcion' => '%s',
            'icono_url' => '%s',
            'formato_codigo' => '%s',
            'activa' => '%d'
        );
        
        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($field === 'correlativo') {
                    $value = strtoupper($value);
                }
                $update_data[$field] = $value;
                $update_format[] = $format;
            }
        }
        
        if (empty($update_data)) {
            return new WP_Error('sin_datos', 'No hay datos para actualizar');
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_actualizacion', 'Error al actualizar la dirección');
        }
        
        // Log de auditoría
        $this->log_action('update', $id, $direccion_actual, $update_data);
        
        return $this->get_by_id($id);
    }
    
    /**
     * Eliminar dirección
     */
    public function delete($id) {
        $direccion = $this->get_by_id($id);
        if (!$direccion) {
            return new WP_Error('direccion_no_encontrada', 'Dirección no encontrada');
        }
        
        // Verificar si tiene vacantes asociadas
        if ($direccion->total_vacantes > 0) {
            return new WP_Error('tiene_vacantes', 'No se puede eliminar una dirección que tiene vacantes asociadas');
        }
        
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_eliminacion', 'Error al eliminar la dirección');
        }
        
        // Log de auditoría
        $this->log_action('delete', $id, $direccion, null);
        
        return true;
    }
    
    /**
     * Verificar si existe un correlativo
     */
    public function correlativo_exists($correlativo, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE correlativo = %s";
        $values = array(strtoupper($correlativo));
        
        if ($exclude_id) {
            $sql .= " AND id != %d";
            $values[] = $exclude_id;
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $values));
        return $count > 0;
    }
    
    /**
     * Verificar si existe un nombre
     */
    public function nombre_exists($nombre, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE nombre = %s";
        $values = array($nombre);
        
        if ($exclude_id) {
            $sql .= " AND id != %d";
            $values[] = $exclude_id;
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $values));
        return $count > 0;
    }
    
    /**
     * Obtener direcciones activas para select
     */
    public function get_for_select() {
        $sql = "SELECT id, correlativo, nombre FROM {$this->table_name} WHERE activa = 1 ORDER BY nombre ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener estadísticas de direcciones
     */
    public function get_stats() {
        $stats = array();
        
        // Total de direcciones
        $stats['total'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Direcciones activas
        $stats['activas'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE activa = 1");
        
        // Direcciones inactivas
        $stats['inactivas'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE activa = 0");
        
        // Direcciones con vacantes
        $stats['con_vacantes'] = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT d.id) FROM {$this->table_name} d 
             INNER JOIN {$this->wpdb->prefix}vs_vacantes v ON d.id = v.direccion_id"
        );
        
        return $stats;
    }
    
    /**
     * Cambiar estado de dirección
     */
    public function cambiar_estado($id, $activa) {
        return $this->update($id, array('activa' => $activa ? 1 : 0));
    }
    
    /**
     * Validar formato de código
     */
    public function validate_formato_codigo($formato) {
        // Placeholders válidos
        $placeholders_validos = array(
            '{CORRELATIVO}',
            '{NUMERO}',
            '{YEAR}',
            '{YYYY}'
        );
        
        // Verificar que contenga al menos un placeholder
        $contiene_placeholder = false;
        foreach ($placeholders_validos as $placeholder) {
            if (strpos($formato, $placeholder) !== false) {
                $contiene_placeholder = true;
                break;
            }
        }
        
        if (!$contiene_placeholder) {
            return new WP_Error('formato_invalido', 'El formato debe contener al menos un placeholder válido');
        }
        
        // Verificar longitud máxima estimada
        $formato_test = str_replace(
            array('{CORRELATIVO}', '{NUMERO}', '{YEAR}', '{YYYY}'),
            array('TESTCORR', '9999', '99', '2024'),
            $formato
        );
        
        if (strlen($formato_test) > 20) {
            return new WP_Error('formato_muy_largo', 'El formato generaría códigos demasiado largos (máximo 20 caracteres)');
        }
        
        return true;
    }
    
    /**
     * Generar código de ejemplo basado en formato
     */
    public function generate_codigo_ejemplo($formato, $correlativo) {
        $year = date('y');
        $year_full = date('Y');
        $numero = rand(1000, 9999);
        
        return str_replace(
            array('{CORRELATIVO}', '{NUMERO}', '{YEAR}', '{YYYY}'),
            array($correlativo, $numero, $year, $year_full),
            $formato
        );
    }
    
    /**
     * Log de auditoría
     */
    private function log_action($action, $record_id, $old_data, $new_data) {
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $this->wpdb->insert(
            $this->wpdb->prefix . 'vacantes_logs',
            array(
                'usuario_id' => $user_id,
                'accion' => $action,
                'tabla_afectada' => 'direcciones',
                'registro_id' => $record_id,
                'datos_anteriores' => $old_data ? json_encode($old_data) : null,
                'datos_nuevos' => $new_data ? json_encode($new_data) : null,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
}