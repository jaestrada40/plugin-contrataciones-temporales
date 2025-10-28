<?php
/**
 * Modelo de Aplicación
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aplicacion_Model {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'vacantes_aplicaciones';
    }
    
    /**
     * Obtener todas las aplicaciones
     */
    public function get_all($args = array()) {
        $defaults = array(
            'vacante_id' => 0,
            'estado' => '',
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'fecha_aplicacion',
            'order' => 'DESC',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT a.*, v.titulo as vacante_titulo, v.codigo as vacante_codigo,
                       d.nombre as direccion_nombre, d.correlativo as direccion_correlativo
                FROM {$this->table_name} a 
                LEFT JOIN {$this->wpdb->prefix}vacantes v ON a.vacante_id = v.id
                LEFT JOIN {$this->wpdb->prefix}vacantes_direcciones d ON v.direccion_id = d.id";
        
        $where = array('1=1');
        $values = array();
        
        if ($args['vacante_id'] > 0) {
            $where[] = 'a.vacante_id = %d';
            $values[] = $args['vacante_id'];
        }
        
        if (!empty($args['estado'])) {
            $where[] = 'a.estado = %s';
            $values[] = $args['estado'];
        }
        
        if (!empty($args['search'])) {
            $where[] = '(a.nombre_completo LIKE %s OR a.email LIKE %s OR a.dpi LIKE %s OR v.titulo LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= $this->wpdb->prepare(" ORDER BY a.{$args['orderby']} {$args['order']} LIMIT %d OFFSET %d", 
                                    $args['limit'], $args['offset']);
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Obtener aplicación por ID
     */
    public function get_by_id($id) {
        $sql = "SELECT a.*, 
                       CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                       v.titulo as vacante_titulo, 
                       v.codigo as vacante_codigo,
                       d.nombre as direccion_nombre
                FROM {$this->wpdb->prefix}aplicaciones_minfin a 
                LEFT JOIN {$this->wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id
                LEFT JOIN {$this->wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                WHERE a.id = %d";
        
        $result = $this->wpdb->get_row($this->wpdb->prepare($sql, $id));
        
        if ($result) {
            // Agregar campo cv_path para compatibilidad
            $result->cv_path = $result->cv_archivo ?? '';
        }
        
        return $result;
    }
    
    /**
     * Crear nueva aplicación
     */
    public function create($data) {
        // Validar que la vacante existe y está activa
        $vacante = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id, activa, fecha_fin FROM {$this->wpdb->prefix}vacantes WHERE id = %d",
                $data['vacante_id']
            )
        );
        
        if (!$vacante) {
            return new WP_Error('vacante_no_encontrada', 'La vacante no existe');
        }
        
        if (!$vacante->activa) {
            return new WP_Error('vacante_inactiva', 'La vacante no está activa');
        }
        
        if ($vacante->fecha_fin < date('Y-m-d')) {
            return new WP_Error('vacante_vencida', 'La vacante ha vencido');
        }
        
        // Validar DPI único por vacante
        $existe_dpi = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE vacante_id = %d AND dpi = %s",
                $data['vacante_id'],
                $data['dpi']
            )
        );
        
        if ($existe_dpi > 0) {
            return new WP_Error('dpi_duplicado', 'Ya existe una aplicación con este DPI para esta vacante');
        }
        
        // Validar email único por vacante (opcional)
        $existe_email = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE vacante_id = %d AND email = %s",
                $data['vacante_id'],
                $data['email']
            )
        );
        
        if ($existe_email > 0) {
            return new WP_Error('email_duplicado', 'Ya existe una aplicación con este email para esta vacante');
        }
        
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'vacante_id' => $data['vacante_id'],
                'nombre_completo' => $data['nombre_completo'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? '',
                'dpi' => $data['dpi'],
                'cv_nombre' => $data['cv_nombre'] ?? '',
                'cv_ruta' => $data['cv_ruta'] ?? '',
                'cv_tamano' => $data['cv_tamano'] ?? 0,
                'estado' => 'Pendiente'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('error_insercion', 'Error al crear la aplicación');
        }
        
        $aplicacion_id = $this->wpdb->insert_id;
        
        // Log de auditoría
        $this->log_action('create', $aplicacion_id, null, $data);
        
        // Enviar notificaciones
        do_action('vacantes_nueva_aplicacion', $aplicacion_id, $data);
        
        return $this->get_by_id($aplicacion_id);
    }
    
    /**
     * Actualizar aplicación
     */
    public function update($id, $data) {
        $aplicacion_actual = $this->get_by_id($id);
        if (!$aplicacion_actual) {
            return new WP_Error('aplicacion_no_encontrada', 'Aplicación no encontrada');
        }
        
        $update_data = array();
        $update_format = array();
        
        $allowed_fields = array(
            'estado' => '%s',
            'comentarios' => '%s',
            'cv_nombre' => '%s',
            'cv_ruta' => '%s',
            'cv_tamano' => '%d'
        );
        
        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
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
            return new WP_Error('error_actualizacion', 'Error al actualizar la aplicación');
        }
        
        // Log de auditoría
        $this->log_action('update', $id, $aplicacion_actual, $update_data);
        
        // Notificar cambio de estado si aplica
        if (isset($data['estado']) && $data['estado'] !== $aplicacion_actual->estado) {
            do_action('vacantes_cambio_estado_aplicacion', $id, $aplicacion_actual->estado, $data['estado']);
        }
        
        return $this->get_by_id($id);
    }
    
    /**
     * Eliminar aplicación
     */
    public function delete($id) {
        $aplicacion = $this->get_by_id($id);
        if (!$aplicacion) {
            return new WP_Error('aplicacion_no_encontrada', 'Aplicación no encontrada');
        }
        
        // Eliminar archivo CV si existe
        if (!empty($aplicacion->cv_ruta)) {
            $this->delete_cv_file($aplicacion->cv_ruta);
        }
        
        $result = $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('error_eliminacion', 'Error al eliminar la aplicación');
        }
        
        // Log de auditoría
        $this->log_action('delete', $id, $aplicacion, null);
        
        return true;
    }
    
    /**
     * Obtener estadísticas de aplicaciones
     */
    public function get_stats($vacante_id = null) {
        $stats = array();
        
        $where_clause = $vacante_id ? $this->wpdb->prepare("WHERE vacante_id = %d", $vacante_id) : "";
        
        // Total de aplicaciones
        $stats['total'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where_clause}");
        
        // Por estado
        $estados = $this->wpdb->get_results(
            "SELECT estado, COUNT(*) as total FROM {$this->table_name} {$where_clause} GROUP BY estado"
        );
        
        $stats['por_estado'] = array();
        foreach ($estados as $estado) {
            $stats['por_estado'][$estado->estado] = $estado->total;
        }
        
        // Aplicaciones recientes (últimos 7 días)
        $stats['recientes'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE fecha_aplicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY) {$where_clause}"
        );
        
        return $stats;
    }
    
    /**
     * Verificar si existe DPI para una vacante
     */
    public function dpi_exists_for_vacante($dpi, $vacante_id) {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE dpi = %s AND vacante_id = %d",
                $dpi,
                $vacante_id
            )
        );
        
        return $count > 0;
    }
    
    /**
     * Obtener aplicaciones por estado
     */
    public function get_by_estado($estado, $limit = 10) {
        $sql = "SELECT a.*, v.titulo as vacante_titulo, v.codigo as vacante_codigo
                FROM {$this->table_name} a 
                LEFT JOIN {$this->wpdb->prefix}vacantes v ON a.vacante_id = v.id
                WHERE a.estado = %s 
                ORDER BY a.fecha_aplicacion DESC 
                LIMIT %d";
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $estado, $limit)
        );
    }
    
    /**
     * Cambiar estado de aplicación
     */
    public function cambiar_estado($id, $nuevo_estado, $comentarios = '') {
        $estados_validos = array('Pendiente', 'Revisado', 'Aceptado', 'Rechazado');
        
        if (!in_array($nuevo_estado, $estados_validos)) {
            return new WP_Error('estado_invalido', 'Estado no válido');
        }
        
        return $this->update($id, array(
            'estado' => $nuevo_estado,
            'comentarios' => $comentarios
        ));
    }
    
    /**
     * Eliminar archivo CV
     */
    private function delete_cv_file($cv_ruta) {
        if (empty($cv_ruta)) {
            return;
        }
        
        // Si es una URL completa, extraer el path
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $cv_ruta);
        
        if (file_exists($file_path)) {
            wp_delete_file($file_path);
        }
    }
    
    /**
     * Verificar si ya aplicó a una vacante
     */
    public function ya_aplico($email, $vacante_id) {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE email = %s AND vacante_id = %d",
                $email, $vacante_id
            )
        );
        
        return $count > 0;
    }

    /**
     * Obtener aplicaciones con paginación (método requerido por la página de admin)
     */
    public function get_all($page = 1, $per_page = 10, $search = '', $filtros = array()) {
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT a.*, 
                       CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                       v.titulo as vacante_titulo, 
                       v.codigo as vacante_codigo,
                       d.nombre as direccion_nombre
                FROM {$this->wpdb->prefix}aplicaciones_minfin a 
                LEFT JOIN {$this->wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id
                LEFT JOIN {$this->wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id";
        
        $where = array('1=1');
        $values = array();
        
        // Filtro por vacante
        if (!empty($filtros['vacante_id']) && $filtros['vacante_id'] > 0) {
            $where[] = 'a.vacante_id = %d';
            $values[] = $filtros['vacante_id'];
        }
        
        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $where[] = 'a.estado = %s';
            $values[] = $filtros['estado'];
        }
        
        // Filtro de búsqueda
        if (!empty($search)) {
            $where[] = '(CONCAT(a.nombres, " ", a.apellidos) LIKE %s OR a.email LIKE %s OR a.telefono LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY a.fecha_aplicacion DESC';
        $sql .= $this->wpdb->prepare(' LIMIT %d OFFSET %d', $per_page, $offset);
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        $results = $this->wpdb->get_results($sql);
        
        // Agregar campo cv_path para compatibilidad
        foreach ($results as $result) {
            $result->cv_path = $result->cv_archivo ?? '';
        }
        
        return $results;
    }
    
    /**
     * Obtener total de aplicaciones (método requerido por la página de admin)
     */
    public function get_total_count($search = '', $filtros = array()) {
        $sql = "SELECT COUNT(*) 
                FROM {$this->wpdb->prefix}aplicaciones_minfin a 
                LEFT JOIN {$this->wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id";
        
        $where = array('1=1');
        $values = array();
        
        // Filtro por vacante
        if (!empty($filtros['vacante_id']) && $filtros['vacante_id'] > 0) {
            $where[] = 'a.vacante_id = %d';
            $values[] = $filtros['vacante_id'];
        }
        
        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $where[] = 'a.estado = %s';
            $values[] = $filtros['estado'];
        }
        
        // Filtro de búsqueda
        if (!empty($search)) {
            $where[] = '(CONCAT(a.nombres, " ", a.apellidos) LIKE %s OR a.email LIKE %s OR a.telefono LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($search) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        $sql .= ' WHERE ' . implode(' AND ', $where);
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }
    
    /**
     * Obtener estadísticas (método requerido por la página de admin)
     */
    public function get_stats($vacante_id = null) {
        $where_clause = '';
        $values = array();
        
        if ($vacante_id) {
            $where_clause = 'WHERE vacante_id = %d';
            $values[] = $vacante_id;
        }
        
        $sql = "SELECT 
                    SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'Revisado' THEN 1 ELSE 0 END) as revisadas,
                    SUM(CASE WHEN estado = 'Aceptado' THEN 1 ELSE 0 END) as aceptadas,
                    SUM(CASE WHEN estado = 'Rechazado' THEN 1 ELSE 0 END) as rechazadas,
                    COUNT(*) as total
                FROM {$this->wpdb->prefix}aplicaciones_minfin 
                {$where_clause}";
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, $values);
        }
        
        $result = $this->wpdb->get_row($sql, ARRAY_A);
        
        return $result ?: array(
            'pendientes' => 0,
            'revisadas' => 0,
            'aceptadas' => 0,
            'rechazadas' => 0,
            'total' => 0
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
                'tabla_afectada' => 'aplicaciones',
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