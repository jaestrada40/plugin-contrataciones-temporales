<?php
/**
 * Modelo de Vacante
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacante_Model {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'vacantes_minfin';
    }
    
    /**
     * Obtener todas las vacantes
     */
    public function get_all($page = 1, $per_page = 10, $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre,
                       (SELECT COUNT(*) FROM {$this->wpdb->prefix}aplicaciones_minfin a WHERE a.vacante_id = v.id) as total_aplicaciones
                FROM {$this->table_name} v 
                LEFT JOIN {$this->wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                LEFT JOIN {$this->wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id";
        
        $where = array();
        $values = array();
        
        if (!empty($search)) {
            $where[] = '(v.titulo LIKE %s OR v.descripcion LIKE %s OR v.requisitos LIKE %s OR v.codigo LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= ' ORDER BY v.fecha_creacion DESC LIMIT %d OFFSET %d';
        $values[] = $per_page;
        $values[] = $offset;
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener vacante por ID
     */
    public function get_by_id($id) {
        $sql = "SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre
                FROM {$this->table_name} v 
                LEFT JOIN {$this->wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                LEFT JOIN {$this->wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
                WHERE v.id = %d";
        
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $id));
    }
    
    /**
     * Crear nueva vacante
     */
    public function create($data) {
        $defaults = array(
            'titulo' => '',
            'descripcion' => '',
            'requisitos' => '',
            'beneficios' => '',
            'salario_min' => 0,
            'salario_max' => 0,
            'direccion_id' => 0,
            'tipo_contrato_id' => 0,
            'fecha_limite' => '',
            'ubicacion' => '',
            'modalidad' => 'Presencial',
            'experiencia_requerida' => 0,
            'nivel_educativo' => '',
            'estado' => 'Activa',
            'fecha_creacion' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generar código único
        $data['codigo'] = $this->generate_codigo($data['direccion_id']);
        
        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%d', '%s', 
                '%s', '%s', '%d', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Error al crear la vacante: ' . $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Actualizar vacante
     */
    public function update($id, $data) {
        $data['fecha_actualizacion'] = current_time('mysql');
        
        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            array(
                '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%d', '%s', 
                '%s', '%s', '%d', '%s', '%s', '%s'
            ),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Error al actualizar la vacante: ' . $this->wpdb->last_error);
        }
        
        return true;
    }
    
    /**
     * Eliminar vacante
     */
    public function delete($id) {
        // Verificar si tiene aplicaciones
        $aplicaciones = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}aplicaciones_minfin WHERE vacante_id = %d",
            $id
        ));
        
        if ($aplicaciones > 0) {
            return new WP_Error('has_applications', 'No se puede eliminar una vacante que tiene aplicaciones');
        }
        
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Error al eliminar la vacante: ' . $this->wpdb->last_error);
        }
        
        return true;
    }
    
    /**
     * Obtener estadísticas
     */
    public function get_stats() {
        $stats = array();
        
        // Total de vacantes
        $stats['total'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Vacantes activas
        $stats['activas'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE estado = 'Activa'");
        
        // Vacantes vigentes (no vencidas)
        $stats['vigentes'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE fecha_limite >= CURDATE() AND estado = 'Activa'");
        
        return $stats;
    }
    
    /**
     * Generar código único para la vacante
     */
    private function generate_codigo($direccion_id) {
        // Obtener correlativo de la dirección
        $direccion = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT nombre FROM {$this->wpdb->prefix}direcciones_minfin WHERE id = %d",
            $direccion_id
        ));
        
        $correlativo = 'VAC';
        if ($direccion) {
            // Generar correlativo basado en las primeras letras del nombre
            $palabras = explode(' ', $direccion->nombre);
            $correlativo = '';
            foreach ($palabras as $palabra) {
                if (strlen($palabra) > 2) {
                    $correlativo .= strtoupper(substr($palabra, 0, 1));
                }
            }
            if (strlen($correlativo) < 2) {
                $correlativo = 'VAC';
            }
        }
        
        // Obtener el siguiente número secuencial
        $ultimo_numero = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING(codigo, %d) AS UNSIGNED)) 
             FROM {$this->table_name} 
             WHERE codigo LIKE %s",
            strlen($correlativo) + 2,
            $correlativo . '-%'
        ));
        
        $siguiente_numero = ($ultimo_numero ? $ultimo_numero : 0) + 1;
        
        return $correlativo . '-' . str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Obtener total de registros para paginación
     */
    public function get_total_count($search = '') {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} v";
        
        if (!empty($search)) {
            $sql .= " WHERE (v.titulo LIKE %s OR v.descripcion LIKE %s OR v.requisitos LIKE %s OR v.codigo LIKE %s)";
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $search_term, $search_term, $search_term, $search_term));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Buscar vacantes públicas
     */
    public function get_vacantes_publicas($filtros = array()) {
        $limite = isset($filtros['limite']) ? intval($filtros['limite']) : 10;
        $direccion_id = isset($filtros['direccion_id']) ? intval($filtros['direccion_id']) : 0;
        
        $sql = "SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre
                FROM {$this->table_name} v 
                LEFT JOIN {$this->wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                LEFT JOIN {$this->wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
                WHERE v.estado = 'Activa' AND v.fecha_limite >= CURDATE()";
        
        if ($direccion_id > 0) {
            $sql .= $this->wpdb->prepare(" AND v.direccion_id = %d", $direccion_id);
        }
        
        $sql .= " ORDER BY v.fecha_creacion DESC LIMIT %d";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $limite));
    }
}