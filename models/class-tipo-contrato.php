<?php
/**
 * Modelo de Tipo de Contrato
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tipo_Contrato_Model {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'vs_tipos_contrato';
    }
    
    /**
     * Obtener todos los tipos de contrato
     */
    public function get_all($args = array()) {
        $defaults = array(
            'activo' => null,
            'es_estandar' => null,
            'orderby' => 'es_estandar DESC, codigo',
            'order' => 'ASC',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT tc.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.tipo_contrato = tc.codigo) as total_vacantes
                FROM {$this->table_name} tc";
        
        $where = array();
        $values = array();
        
        if ($args['activo'] !== null) {
            $where[] = 'tc.activo = %d';
            $values[] = $args['activo'] ? 1 : 0;
        }
        
        if ($args['es_estandar'] !== null) {
            $where[] = 'tc.es_estandar = %d';
            $values[] = $args['es_estandar'] ? 1 : 0;
        }
        
        if (!empty($args['search'])) {
            $where[] = '(tc.codigo LIKE %s OR tc.nombre LIKE %s OR tc.descripcion LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener tipo de contrato por ID
     */
    public function get_by_id($id) {
        $sql = "SELECT tc.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.tipo_contrato = tc.codigo) as total_vacantes
                FROM {$this->table_name} tc 
                WHERE tc.id = %d";
        
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $id));
    }
    
    /**
     * Obtener tipo de contrato por código
     */
    public function get_by_codigo($codigo) {
        $sql = "SELECT tc.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.tipo_contrato = tc.codigo) as total_vacantes
                FROM {$this->table_name} tc 
                WHERE tc.codigo = %s";
        
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $codigo));
    }
    
    /**
     * Crear nuevo tipo de contrato
     */
    public function create($data) {
        // Validar que el código sea único
        if ($this->codigo_exists($data['codigo'])) {
            return new WP_Error('codigo_duplicado', 'Ya existe un tipo de contrato con ese código');
        }
        
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'codigo' => strtoupper($data['codigo']),
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'activo' => isset($data['activo']) ? $data['activo'] : 1,
                'es_estandar' => 0 // Los creados por usuario no son estándar
            ),
            array('%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_insercion', 'Error al crear el tipo de contrato');
        }
        
        $tipo_id = $this->wpdb->insert_id;
        
        // Log de auditoría
        $this->log_action('create', $tipo_id, null, $data);
        
        return $this->get_by_id($tipo_id);
    }
    
    /**
     * Actualizar tipo de contrato
     */
    public function update($id, $data) {
        $tipo_actual = $this->get_by_id($id);
        if (!$tipo_actual) {
            return new WP_Error('tipo_no_encontrado', 'Tipo de contrato no encontrado');
        }
        
        // Validar código único si se está cambiando
        if (isset($data['codigo']) && strtoupper($data['codigo']) !== $tipo_actual->codigo) {
            if ($this->codigo_exists($data['codigo'], $id)) {
                return new WP_Error('codigo_duplicado', 'Ya existe un tipo de contrato con ese código');
            }
        }
        
        $update_data = array();
        $update_format = array();
        
        // Para tipos estándar, solo permitir cambiar ciertos campos
        if ($tipo_actual->es_estandar) {
            $allowed_fields = array(
                'nombre' => '%s',
                'descripcion' => '%s',
                'activo' => '%d'
            );
        } else {
            $allowed_fields = array(
                'codigo' => '%s',
                'nombre' => '%s',
                'descripcion' => '%s',
                'activo' => '%d'
            );
        }
        
        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($field === 'codigo') {
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
            return new WP_Error('error_actualizacion', 'Error al actualizar el tipo de contrato');
        }
        
        // Log de auditoría
        $this->log_action('update', $id, $tipo_actual, $update_data);
        
        return $this->get_by_id($id);
    }
    
    /**
     * Eliminar tipo de contrato
     */
    public function delete($id) {
        $tipo = $this->get_by_id($id);
        if (!$tipo) {
            return new WP_Error('tipo_no_encontrado', 'Tipo de contrato no encontrado');
        }
        
        // No permitir eliminar tipos estándar
        if ($tipo->es_estandar) {
            return new WP_Error('tipo_estandar', 'No se pueden eliminar los tipos de contrato estándar');
        }
        
        // Verificar si tiene vacantes asociadas
        if ($tipo->total_vacantes > 0) {
            return new WP_Error('tiene_vacantes', 'No se puede eliminar un tipo de contrato que tiene vacantes asociadas');
        }
        
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_eliminacion', 'Error al eliminar el tipo de contrato');
        }
        
        // Log de auditoría
        $this->log_action('delete', $id, $tipo, null);
        
        return true;
    }
    
    /**
     * Verificar si existe un código
     */
    public function codigo_exists($codigo, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE codigo = %s";
        $values = array(strtoupper($codigo));
        
        if ($exclude_id) {
            $sql .= " AND id != %d";
            $values[] = $exclude_id;
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $values));
        return $count > 0;
    }
    
    /**
     * Obtener tipos activos para select
     */
    public function get_activos_for_select() {
        $sql = "SELECT codigo, nombre FROM {$this->table_name} WHERE activo = 1 ORDER BY es_estandar DESC, codigo ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener tipos estándar
     */
    public function get_estandar() {
        $sql = "SELECT * FROM {$this->table_name} WHERE es_estandar = 1 ORDER BY codigo ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener tipos personalizados
     */
    public function get_personalizados() {
        $sql = "SELECT tc.*, 
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}vacantes v WHERE v.tipo_contrato = tc.codigo) as total_vacantes
                FROM {$this->table_name} tc 
                WHERE tc.es_estandar = 0 
                ORDER BY tc.codigo ASC";
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener estadísticas
     */
    public function get_stats() {
        $stats = array();
        
        // Total de tipos
        $stats['total'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Tipos activos
        $stats['activos'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE activo = 1");
        
        // Tipos inactivos
        $stats['inactivos'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE activo = 0");
        
        // Tipos estándar
        $stats['estandar'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE es_estandar = 1");
        
        // Tipos personalizados
        $stats['personalizados'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE es_estandar = 0");
        
        // Tipos más usados
        $stats['mas_usados'] = $this->wpdb->get_results(
            "SELECT tc.codigo, tc.nombre, COUNT(v.id) as total_vacantes 
             FROM {$this->table_name} tc 
             LEFT JOIN {$this->wpdb->prefix}vacantes v ON tc.codigo = v.tipo_contrato 
             GROUP BY tc.id, tc.codigo, tc.nombre 
             ORDER BY total_vacantes DESC 
             LIMIT 5"
        );
        
        return $stats;
    }
    
    /**
     * Cambiar estado de tipo de contrato
     */
    public function cambiar_estado($id, $activo) {
        return $this->update($id, array('activo' => $activo ? 1 : 0));
    }
    
    /**
     * Validar código de tipo de contrato
     */
    public function validate_codigo($codigo) {
        // Limpiar y convertir a mayúsculas
        $codigo = strtoupper(trim($codigo));
        
        // Validar longitud
        if (strlen($codigo) < 2 || strlen($codigo) > 10) {
            return new WP_Error('codigo_longitud', 'El código debe tener entre 2 y 10 caracteres');
        }
        
        // Validar caracteres permitidos (solo letras y números)
        if (!preg_match('/^[A-Z0-9]+$/', $codigo)) {
            return new WP_Error('codigo_caracteres', 'El código solo puede contener letras y números');
        }
        
        return $codigo;
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
                'tabla_afectada' => 'tipos_contrato',
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