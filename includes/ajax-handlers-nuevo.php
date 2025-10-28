<?php
/**
 * Nuevos Manejadores AJAX para Reportes
 * Replica exactamente la funcionalidad de .NET
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Ajax_Reportes_Nuevo {
    
    /**
     * Cargar reporte de efectividad por dirección
     */
    public static function cargar_reporte_efectividad() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $datos = $wpdb->get_results("
                SELECT 
                    d.nombre as direccion,
                    COUNT(DISTINCT v.id) as total_vacantes,
                    COUNT(DISTINCT CASE WHEN v.estado = 'Activa' AND v.fecha_limite > NOW() THEN v.id END) as vacantes_activas,
                    COUNT(a.id) as total_aplicaciones,
                    COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas,
                    ROUND(
                        CASE 
                            WHEN COUNT(a.id) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) / COUNT(a.id)) * 100
                            ELSE 0 
                        END, 2
                    ) as porcentaje_efectividad,
                    ROUND(
                        CASE 
                            WHEN COUNT(DISTINCT v.id) > 0 THEN COUNT(a.id) / COUNT(DISTINCT v.id)
                            ELSE 0 
                        END, 2
                    ) as promedio_aplicaciones
                FROM {$wpdb->prefix}direcciones_minfin d
                LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id
                LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
                GROUP BY d.id, d.nombre
                HAVING total_vacantes > 0
                ORDER BY porcentaje_efectividad DESC
            ");
            
            wp_send_json_success($datos);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar tendencias mensuales
     */
    public static function cargar_reporte_tendencias() {
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $datos = $wpdb->get_results("
                SELECT 
                    YEAR(a.fecha_aplicacion) as anio,
                    MONTH(a.fecha_aplicacion) as mes,
                    MONTHNAME(a.fecha_aplicacion) as mes_nombre,
                    COUNT(*) as total_aplicaciones,
                    COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) as aceptadas,
                    COUNT(CASE WHEN a.estado = 'Rechazada' THEN 1 END) as rechazadas,
                    COUNT(CASE WHEN a.estado = 'Pendiente' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN a.estado = 'Revisada' THEN 1 END) as revisadas,
                    ROUND(
                        CASE 
                            WHEN COUNT(*) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) / COUNT(*)) * 100
                            ELSE 0 
                        END, 2
                    ) as porcentaje_aceptacion
                FROM {$wpdb->prefix}aplicaciones_minfin a
                WHERE a.fecha_aplicacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY YEAR(a.fecha_aplicacion), MONTH(a.fecha_aplicacion)
                ORDER BY anio DESC, mes DESC
            ");
            
            wp_send_json_success($datos);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar tendencias: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar perfiles de candidatos
     */
    public static function cargar_reporte_perfiles() {
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $datos = $wpdb->get_results("
                SELECT 
                    COALESCE(a.nivel_educativo, 'No especificado') as nivel_educativo,
                    CASE 
                        WHEN a.experiencia_laboral <= 1 THEN '0-1 años'
                        WHEN a.experiencia_laboral <= 3 THEN '2-3 años'
                        WHEN a.experiencia_laboral <= 5 THEN '4-5 años'
                        WHEN a.experiencia_laboral <= 10 THEN '6-10 años'
                        ELSE '10+ años'
                    END as rango_experiencia,
                    COUNT(*) as total_candidatos,
                    COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) as aceptados,
                    ROUND(
                        CASE 
                            WHEN COUNT(*) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) / COUNT(*)) * 100
                            ELSE 0 
                        END, 2
                    ) as porcentaje_aceptacion,
                    ROUND(AVG(a.experiencia_laboral), 1) as promedio_experiencia
                FROM {$wpdb->prefix}aplicaciones_minfin a
                WHERE a.nivel_educativo IS NOT NULL AND a.nivel_educativo != ''
                GROUP BY nivel_educativo, rango_experiencia
                ORDER BY total_candidatos DESC
            ");
            
            wp_send_json_success($datos);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar perfiles: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar reporte de vacantes
     */
    public static function cargar_reporte_vacantes() {
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $datos = $wpdb->get_results("
                SELECT 
                    v.codigo,
                    v.titulo,
                    d.nombre as direccion,
                    tc.nombre as tipo_contrato,
                    v.fecha_limite,
                    v.estado,
                    v.fecha_creacion,
                    COUNT(a.id) as total_aplicaciones,
                    COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas,
                    COUNT(CASE WHEN a.estado = 'Pendiente' THEN a.id END) as aplicaciones_pendientes,
                    COUNT(CASE WHEN a.estado = 'Rechazada' THEN a.id END) as aplicaciones_rechazadas,
                    DATEDIFF(v.fecha_limite, CURDATE()) as dias_por_vencer,
                    CASE 
                        WHEN v.estado = 'Activa' AND v.fecha_limite > NOW() THEN 'Activa'
                        WHEN v.fecha_limite <= NOW() THEN 'Vencida'
                        ELSE 'Inactiva'
                    END as estado_calculado
                FROM {$wpdb->prefix}vacantes_minfin v
                LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
                LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
                GROUP BY v.id
                ORDER BY v.fecha_creacion DESC
            ");
            
            wp_send_json_success($datos);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar vacantes: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar reporte de aplicaciones
     */
    public static function cargar_reporte_aplicaciones() {
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $direccion_id = isset($_POST['direccion_id']) ? intval($_POST['direccion_id']) : 0;
        
        $where_conditions = array();
        $where_params = array();
        
        if (!empty($estado)) {
            $where_conditions[] = "a.estado = %s";
            $where_params[] = $estado;
        }
        
        if ($direccion_id > 0) {
            $where_conditions[] = "v.direccion_id = %d";
            $where_params[] = $direccion_id;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        try {
            $query = "
                SELECT 
                    CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                    a.email,
                    a.telefono,
                    v.codigo as vacante_codigo,
                    v.titulo as vacante_titulo,
                    d.nombre as direccion,
                    a.estado,
                    a.fecha_aplicacion,
                    a.nivel_educativo,
                    a.profesion,
                    a.experiencia_laboral,
                    DATEDIFF(CURDATE(), a.fecha_aplicacion) as dias_en_estado
                FROM {$wpdb->prefix}aplicaciones_minfin a
                LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id
                LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                {$where_clause}
                ORDER BY a.fecha_aplicacion DESC
            ";
            
            if (!empty($where_params)) {
                $datos = $wpdb->get_results($wpdb->prepare($query, $where_params));
            } else {
                $datos = $wpdb->get_results($query);
            }
            
            wp_send_json_success($datos);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar aplicaciones: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar direcciones para filtros
     */
    public static function cargar_direcciones() {
        if (!wp_verify_nonce($_POST['nonce'], 'reportes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $direcciones = $wpdb->get_results("
                SELECT id, nombre 
                FROM {$wpdb->prefix}direcciones_minfin 
                ORDER BY nombre
            ");
            
            wp_send_json_success($direcciones);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar direcciones: ' . $e->getMessage());
        }
    }
    
    /**
     * Cargar vacantes con filtros
     */
    public static function cargar_vacantes() {
        if (!wp_verify_nonce($_POST['nonce'], 'vacantes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $vacantes = $wpdb->get_results("
                SELECT 
                    v.id,
                    v.codigo,
                    v.titulo,
                    v.descripcion,
                    v.requisitos,
                    v.salario_min,
                    v.salario_max,
                    v.fecha_limite,
                    v.estado,
                    v.fecha_creacion,
                    v.direccion_id,
                    v.tipo_contrato_id,
                    d.nombre as direccion_nombre,
                    tc.nombre as tipo_contrato_nombre,
                    COUNT(a.id) as total_aplicaciones,
                    CASE 
                        WHEN v.salario_min > 0 AND v.salario_max > 0 THEN 
                            CONCAT('Q', FORMAT(v.salario_min, 0), ' - Q', FORMAT(v.salario_max, 0))
                        WHEN v.salario_min > 0 THEN 
                            CONCAT('Desde Q', FORMAT(v.salario_min, 0))
                        WHEN v.salario_max > 0 THEN 
                            CONCAT('Hasta Q', FORMAT(v.salario_max, 0))
                        ELSE NULL
                    END as salario
                FROM {$wpdb->prefix}vacantes_minfin v
                LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
                LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
                LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
                GROUP BY v.id
                ORDER BY v.fecha_creacion DESC
            ");
            
            wp_send_json_success($vacantes);
            
        } catch (Exception $e) {
            wp_send_json_error('Error al cargar vacantes: ' . $e->getMessage());
        }
    }
    
    /**
     * Crear nueva vacante
     */
    public static function crear_vacante() {
        if (!wp_verify_nonce($_POST['nonce'], 'vacantes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            // Generar código automático
            $codigo = self::generar_codigo_vacante();
            
            // Solo incluir campos que existen en la tabla
            $data = array(
                'codigo' => $codigo,
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => wp_kses_post($_POST['descripcion']),
                'requisitos' => wp_kses_post($_POST['requisitos']),
                'beneficios' => wp_kses_post($_POST['beneficios']),
                'direccion_id' => intval($_POST['direccion_id']),
                'tipo_contrato_id' => intval($_POST['tipo_contrato_id']),
                'salario_min' => floatval($_POST['salario_min']),
                'salario_max' => floatval($_POST['salario_max']),
                'fecha_limite' => sanitize_text_field($_POST['fecha_limite']),
                'ubicacion' => sanitize_text_field($_POST['ubicacion']),
                'modalidad' => sanitize_text_field($_POST['modalidad']),
                'experiencia_requerida' => intval($_POST['experiencia_requerida']),
                'nivel_educativo' => sanitize_text_field($_POST['nivel_educativo']),
                'estado' => sanitize_text_field($_POST['estado']),
                'fecha_creacion' => current_time('mysql')
            );
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'vacantes_minfin',
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                wp_send_json_error('Error al crear la vacante');
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'Vacante creada correctamente',
                'id' => $wpdb->insert_id
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error al crear vacante: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar vacante existente
     */
    public static function actualizar_vacante() {
        if (!wp_verify_nonce($_POST['nonce'], 'vacantes_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            $id = intval($_POST['id']);
            
            // Solo actualizar campos que existen en la tabla
            $data = array(
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => wp_kses_post($_POST['descripcion']),
                'requisitos' => wp_kses_post($_POST['requisitos']),
                'beneficios' => wp_kses_post($_POST['beneficios']),
                'direccion_id' => intval($_POST['direccion_id']),
                'tipo_contrato_id' => intval($_POST['tipo_contrato_id']),
                'salario_min' => floatval($_POST['salario_min']),
                'salario_max' => floatval($_POST['salario_max']),
                'fecha_limite' => sanitize_text_field($_POST['fecha_limite']),
                'ubicacion' => sanitize_text_field($_POST['ubicacion']),
                'modalidad' => sanitize_text_field($_POST['modalidad']),
                'experiencia_requerida' => intval($_POST['experiencia_requerida']),
                'nivel_educativo' => sanitize_text_field($_POST['nivel_educativo']),
                'estado' => sanitize_text_field($_POST['estado'])
            );
            
            $result = $wpdb->update(
                $wpdb->prefix . 'vacantes_minfin',
                $data,
                array('id' => $id),
                array('%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                wp_send_json_error('Error al actualizar la vacante');
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'Vacante actualizada correctamente'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error al actualizar vacante: ' . $e->getMessage());
        }
    }
    
    /**
     * Generar código automático para vacante
     */
    private static function generar_codigo_vacante() {
        global $wpdb;
        
        $year = date('Y');
        $prefix = 'VAC-' . $year . '-';
        
        // Obtener el último número usado
        $last_code = $wpdb->get_var($wpdb->prepare("
            SELECT codigo 
            FROM {$wpdb->prefix}vacantes_minfin 
            WHERE codigo LIKE %s 
            ORDER BY codigo DESC 
            LIMIT 1
        ", $prefix . '%'));
        
        if ($last_code) {
            $last_number = intval(str_replace($prefix, '', $last_code));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Exportar reportes a Excel
     */
    public static function exportar_reporte_nuevo() {
        // Limpiar output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Verificar permisos básicos
        if (!wp_verify_nonce($_POST['nonce'], 'exportar_reporte') || !current_user_can('manage_options')) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="error_' . date('Y-m-d') . '.xls"');
            echo '<html><body><h1>Error de Acceso</h1></body></html>';
            exit;
        }
        
        $tipo_reporte = sanitize_text_field($_POST['tipo_reporte']);
        
        // Headers para Excel
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo_reporte . '_' . date('Y-m-d_H-i-s') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM para UTF-8
        echo "\xEF\xBB\xBF";
        
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        
        echo '<h1>Reporte de ' . ucfirst($tipo_reporte) . '</h1>';
        echo '<p>Generado el: ' . date('d/m/Y H:i:s') . '</p>';
        echo '<p>Sistema de Vacantes MINFIN</p>';
        echo '<br>';
        
        global $wpdb;
        
        try {
            switch ($tipo_reporte) {
                case 'efectividad':
                    self::exportar_efectividad($wpdb);
                    break;
                case 'tendencias':
                    self::exportar_tendencias($wpdb);
                    break;
                case 'perfiles':
                    self::exportar_perfiles($wpdb);
                    break;
                case 'vacantes':
                    self::exportar_vacantes($wpdb);
                    break;
                case 'aplicaciones':
                    self::exportar_aplicaciones($wpdb);
                    break;
                default:
                    echo '<h2>Error: Tipo de reporte no válido</h2>';
            }
        } catch (Exception $e) {
            echo '<h2>Error al generar reporte</h2>';
            echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
        }
        
        echo '</body></html>';
        exit;
    }
    
    /**
     * Exportar reporte de efectividad
     */
    private static function exportar_efectividad($wpdb) {
        $datos = $wpdb->get_results("
            SELECT 
                d.nombre as direccion,
                COUNT(DISTINCT v.id) as total_vacantes,
                COUNT(DISTINCT CASE WHEN v.estado = 'Activa' AND v.fecha_limite > NOW() THEN v.id END) as vacantes_activas,
                COUNT(a.id) as total_aplicaciones,
                COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas,
                COUNT(CASE WHEN a.estado = 'Pendiente' THEN a.id END) as aplicaciones_pendientes,
                COUNT(CASE WHEN a.estado = 'Rechazada' THEN a.id END) as aplicaciones_rechazadas,
                ROUND(
                    CASE 
                        WHEN COUNT(a.id) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) / COUNT(a.id)) * 100
                        ELSE 0 
                    END, 2
                ) as porcentaje_efectividad,
                ROUND(
                    CASE 
                        WHEN COUNT(DISTINCT v.id) > 0 THEN COUNT(a.id) / COUNT(DISTINCT v.id)
                        ELSE 0 
                    END, 2
                ) as promedio_aplicaciones
            FROM {$wpdb->prefix}direcciones_minfin d
            LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id
            LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
            GROUP BY d.id, d.nombre
            HAVING total_vacantes > 0
            ORDER BY porcentaje_efectividad DESC
        ");
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #4285f4; color: white; font-weight: bold;">';
        echo '<td>Dirección</td>';
        echo '<td>Total Vacantes</td>';
        echo '<td>Vacantes Activas</td>';
        echo '<td>Total Aplicaciones</td>';
        echo '<td>Aplicaciones Aceptadas</td>';
        echo '<td>Aplicaciones Pendientes</td>';
        echo '<td>Aplicaciones Rechazadas</td>';
        echo '<td>% Efectividad</td>';
        echo '<td>Promedio Aplicaciones</td>';
        echo '</tr>';
        
        if ($datos && count($datos) > 0) {
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . esc_html($fila->direccion) . '</td>';
                echo '<td>' . $fila->total_vacantes . '</td>';
                echo '<td>' . $fila->vacantes_activas . '</td>';
                echo '<td>' . $fila->total_aplicaciones . '</td>';
                echo '<td>' . $fila->aplicaciones_aceptadas . '</td>';
                echo '<td>' . $fila->aplicaciones_pendientes . '</td>';
                echo '<td>' . $fila->aplicaciones_rechazadas . '</td>';
                echo '<td>' . $fila->porcentaje_efectividad . '%</td>';
                echo '<td>' . $fila->promedio_aplicaciones . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9">No hay datos disponibles</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Exportar tendencias mensuales
     */
    private static function exportar_tendencias($wpdb) {
        $datos = $wpdb->get_results("
            SELECT 
                YEAR(a.fecha_aplicacion) as anio,
                MONTH(a.fecha_aplicacion) as mes,
                MONTHNAME(a.fecha_aplicacion) as mes_nombre,
                COUNT(*) as total_aplicaciones,
                COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) as aceptadas,
                COUNT(CASE WHEN a.estado = 'Rechazada' THEN 1 END) as rechazadas,
                COUNT(CASE WHEN a.estado = 'Pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN a.estado = 'Revisada' THEN 1 END) as revisadas,
                ROUND(
                    CASE 
                        WHEN COUNT(*) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) / COUNT(*)) * 100
                        ELSE 0 
                    END, 2
                ) as porcentaje_aceptacion
            FROM {$wpdb->prefix}aplicaciones_minfin a
            WHERE a.fecha_aplicacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(a.fecha_aplicacion), MONTH(a.fecha_aplicacion)
            ORDER BY anio DESC, mes DESC
        ");
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #34a853; color: white; font-weight: bold;">';
        echo '<td>Período</td>';
        echo '<td>Total Aplicaciones</td>';
        echo '<td>Aceptadas</td>';
        echo '<td>Rechazadas</td>';
        echo '<td>Pendientes</td>';
        echo '<td>Revisadas</td>';
        echo '<td>% Aceptación</td>';
        echo '</tr>';
        
        if ($datos && count($datos) > 0) {
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . $fila->mes_nombre . ' ' . $fila->anio . '</td>';
                echo '<td>' . $fila->total_aplicaciones . '</td>';
                echo '<td>' . $fila->aceptadas . '</td>';
                echo '<td>' . $fila->rechazadas . '</td>';
                echo '<td>' . $fila->pendientes . '</td>';
                echo '<td>' . $fila->revisadas . '</td>';
                echo '<td>' . $fila->porcentaje_aceptacion . '%</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">No hay datos de tendencias disponibles</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Exportar perfiles de candidatos
     */
    private static function exportar_perfiles($wpdb) {
        $datos = $wpdb->get_results("
            SELECT 
                COALESCE(a.nivel_educativo, 'No especificado') as nivel_educativo,
                CASE 
                    WHEN a.experiencia_laboral <= 1 THEN '0-1 años'
                    WHEN a.experiencia_laboral <= 3 THEN '2-3 años'
                    WHEN a.experiencia_laboral <= 5 THEN '4-5 años'
                    WHEN a.experiencia_laboral <= 10 THEN '6-10 años'
                    ELSE '10+ años'
                END as rango_experiencia,
                COUNT(*) as total_candidatos,
                COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) as aceptados,
                ROUND(
                    CASE 
                        WHEN COUNT(*) > 0 THEN (COUNT(CASE WHEN a.estado = 'Aceptada' THEN 1 END) / COUNT(*)) * 100
                        ELSE 0 
                    END, 2
                ) as porcentaje_aceptacion,
                ROUND(AVG(a.experiencia_laboral), 1) as promedio_experiencia
            FROM {$wpdb->prefix}aplicaciones_minfin a
            WHERE a.nivel_educativo IS NOT NULL AND a.nivel_educativo != ''
            GROUP BY nivel_educativo, rango_experiencia
            ORDER BY total_candidatos DESC
        ");
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #ff9800; color: white; font-weight: bold;">';
        echo '<td>Nivel Educativo</td>';
        echo '<td>Rango Experiencia</td>';
        echo '<td>Total Candidatos</td>';
        echo '<td>Aceptados</td>';
        echo '<td>% Aceptación</td>';
        echo '<td>Promedio Experiencia</td>';
        echo '</tr>';
        
        if ($datos && count($datos) > 0) {
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . esc_html($fila->nivel_educativo) . '</td>';
                echo '<td>' . esc_html($fila->rango_experiencia) . '</td>';
                echo '<td>' . $fila->total_candidatos . '</td>';
                echo '<td>' . $fila->aceptados . '</td>';
                echo '<td>' . $fila->porcentaje_aceptacion . '%</td>';
                echo '<td>' . $fila->promedio_experiencia . ' años</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">No hay datos de perfiles disponibles</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Exportar reporte de vacantes
     */
    private static function exportar_vacantes($wpdb) {
        $datos = $wpdb->get_results("
            SELECT 
                v.codigo,
                v.titulo,
                d.nombre as direccion,
                tc.nombre as tipo_contrato,
                v.fecha_limite,
                v.estado,
                v.fecha_creacion,
                COUNT(a.id) as total_aplicaciones,
                COUNT(CASE WHEN a.estado = 'Aceptada' THEN a.id END) as aplicaciones_aceptadas,
                COUNT(CASE WHEN a.estado = 'Pendiente' THEN a.id END) as aplicaciones_pendientes,
                COUNT(CASE WHEN a.estado = 'Rechazada' THEN a.id END) as aplicaciones_rechazadas,
                DATEDIFF(v.fecha_limite, CURDATE()) as dias_por_vencer
            FROM {$wpdb->prefix}vacantes_minfin v
            LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
            LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
            LEFT JOIN {$wpdb->prefix}aplicaciones_minfin a ON v.id = a.vacante_id
            GROUP BY v.id
            ORDER BY v.fecha_creacion DESC
        ");
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #00bcd4; color: white; font-weight: bold;">';
        echo '<td>Código</td>';
        echo '<td>Título</td>';
        echo '<td>Dirección</td>';
        echo '<td>Tipo Contrato</td>';
        echo '<td>Fecha Límite</td>';
        echo '<td>Estado</td>';
        echo '<td>Total Aplicaciones</td>';
        echo '<td>Aceptadas</td>';
        echo '<td>Pendientes</td>';
        echo '<td>Rechazadas</td>';
        echo '<td>Días por Vencer</td>';
        echo '</tr>';
        
        if ($datos && count($datos) > 0) {
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . esc_html($fila->codigo) . '</td>';
                echo '<td>' . esc_html($fila->titulo) . '</td>';
                echo '<td>' . esc_html($fila->direccion) . '</td>';
                echo '<td>' . esc_html($fila->tipo_contrato) . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($fila->fecha_limite)) . '</td>';
                echo '<td>' . esc_html($fila->estado) . '</td>';
                echo '<td>' . $fila->total_aplicaciones . '</td>';
                echo '<td>' . $fila->aplicaciones_aceptadas . '</td>';
                echo '<td>' . $fila->aplicaciones_pendientes . '</td>';
                echo '<td>' . $fila->aplicaciones_rechazadas . '</td>';
                echo '<td>' . $fila->dias_por_vencer . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="11">No hay vacantes disponibles</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Exportar reporte de aplicaciones
     */
    private static function exportar_aplicaciones($wpdb) {
        $datos = $wpdb->get_results("
            SELECT 
                CONCAT(a.nombres, ' ', a.apellidos) as nombre_completo,
                a.email,
                a.telefono,
                v.codigo as vacante_codigo,
                v.titulo as vacante_titulo,
                d.nombre as direccion,
                a.estado,
                a.fecha_aplicacion,
                a.nivel_educativo,
                a.profesion,
                a.experiencia_laboral,
                DATEDIFF(CURDATE(), a.fecha_aplicacion) as dias_en_estado
            FROM {$wpdb->prefix}aplicaciones_minfin a
            LEFT JOIN {$wpdb->prefix}vacantes_minfin v ON a.vacante_id = v.id
            LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
            ORDER BY a.fecha_aplicacion DESC
        ");
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #9c27b0; color: white; font-weight: bold;">';
        echo '<td>Candidato</td>';
        echo '<td>Email</td>';
        echo '<td>Teléfono</td>';
        echo '<td>Código Vacante</td>';
        echo '<td>Título Vacante</td>';
        echo '<td>Dirección</td>';
        echo '<td>Estado</td>';
        echo '<td>Nivel Educativo</td>';
        echo '<td>Profesión</td>';
        echo '<td>Experiencia</td>';
        echo '<td>Fecha Aplicación</td>';
        echo '<td>Días en Estado</td>';
        echo '</tr>';
        
        if ($datos && count($datos) > 0) {
            foreach ($datos as $fila) {
                echo '<tr>';
                echo '<td>' . esc_html($fila->nombre_completo) . '</td>';
                echo '<td>' . esc_html($fila->email) . '</td>';
                echo '<td>' . esc_html($fila->telefono) . '</td>';
                echo '<td>' . esc_html($fila->vacante_codigo) . '</td>';
                echo '<td>' . esc_html($fila->vacante_titulo) . '</td>';
                echo '<td>' . esc_html($fila->direccion) . '</td>';
                echo '<td>' . esc_html($fila->estado) . '</td>';
                echo '<td>' . esc_html($fila->nivel_educativo ?: 'No especificado') . '</td>';
                echo '<td>' . esc_html($fila->profesion ?: 'No especificado') . '</td>';
                echo '<td>' . ($fila->experiencia_laboral ?: 0) . ' años</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($fila->fecha_aplicacion)) . '</td>';
                echo '<td>' . $fila->dias_en_estado . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="12">No hay aplicaciones disponibles</td></tr>';
        }
        
        echo '</table>';
    }

    /**
     * Cargar aplicaciones para administración
     */
    public static function cargar_aplicaciones_admin() {
        // Log para debug
        error_log('AJAX cargar_aplicaciones_admin llamado');
        
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'aplicaciones_nonce')) {
            error_log('Nonce inválido');
            wp_send_json_error('Nonce inválido');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            error_log('Usuario sin permisos');
            wp_send_json_error('Sin permisos');
            return;
        }
        
        global $wpdb;
        
        // Consulta súper simple
        $tabla = $wpdb->prefix . 'aplicaciones_minfin';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
        
        error_log("Tabla $tabla tiene $count registros");
        
        if ($count == 0) {
            wp_send_json_success(array());
            return;
        }
        
        // Obtener datos básicos
        $datos = $wpdb->get_results("
            SELECT 
                id,
                nombres,
                apellidos,
                email,
                telefono,
                estado,
                fecha_aplicacion
            FROM $tabla
            ORDER BY fecha_aplicacion DESC
        ");
        
        error_log('Datos obtenidos: ' . count($datos) . ' registros');
        
        // Agregar campos faltantes
        foreach ($datos as $dato) {
            $dato->vacante_codigo = 'TEST-' . $dato->id;
            $dato->vacante_titulo = 'Vacante de Prueba';
            $dato->profesion = 'No especificado';
            $dato->nivel_educativo = 'No especificado';
            $dato->experiencia_laboral = 0;
        }
        
        wp_send_json_success($datos);
    }

    /**
     * Actualizar estado de aplicación
     */
    public static function actualizar_estado_aplicacion() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'aplicaciones_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        $aplicacion_id = intval($_POST['aplicacion_id']);
        $estado = sanitize_text_field($_POST['estado']);
        
        // Validar estado
        $estados_validos = array('Pendiente', 'Revisada', 'Aceptada', 'Rechazada');
        if (!in_array($estado, $estados_validos)) {
            wp_send_json_error('Estado no válido');
            return;
        }
        
        global $wpdb;
        
        try {
            $resultado = $wpdb->update(
                $wpdb->prefix . 'aplicaciones_minfin',
                array(
                    'estado' => $estado,
                    'fecha_actualizacion' => current_time('mysql')
                ),
                array('id' => $aplicacion_id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($resultado !== false) {
                wp_send_json_success(array(
                    'message' => 'Estado actualizado correctamente',
                    'nuevo_estado' => $estado
                ));
            } else {
                wp_send_json_error('Error al actualizar el estado');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error al actualizar estado: ' . $e->getMessage());
        }
    }

    /**
     * Exportar aplicaciones a Excel
     */
    public static function exportar_aplicaciones_excel() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'aplicaciones_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }

        // Limpiar cualquier output previo
        if (ob_get_level()) {
            ob_end_clean();
        }

        $datos_json = stripslashes($_POST['datos']);
        $datos = json_decode($datos_json, true);

        if (empty($datos)) {
            wp_send_json_error('No hay datos para exportar');
            return;
        }

        // Headers para descarga de Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="aplicaciones_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');

        // Generar contenido Excel
        echo '<html>';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        
        // Headers
        if (!empty($datos)) {
            echo '<tr style="background-color: #4CAF50; color: white; font-weight: bold;">';
            foreach (array_keys($datos[0]) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            
            // Datos
            foreach ($datos as $fila) {
                echo '<tr>';
                foreach ($fila as $valor) {
                    echo '<td>' . htmlspecialchars($valor) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    /**
     * Crear datos de prueba para aplicaciones
     */
    public static function crear_datos_prueba_aplicaciones() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'aplicaciones_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            // Verificar si ya hay aplicaciones
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin");
            if ($count > 0) {
                wp_send_json_error('Ya existen aplicaciones en la base de datos');
                return;
            }
            
            // Obtener una vacante existente
            $vacante = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}vacantes_minfin LIMIT 1");
            if (!$vacante) {
                wp_send_json_error('No hay vacantes disponibles. Crea una vacante primero.');
                return;
            }
            
            // Datos de prueba
            $aplicaciones_prueba = array(
                array(
                    'nombres' => 'Juan Carlos',
                    'apellidos' => 'García López',
                    'dpi' => '1234567890101',
                    'email' => 'juan.garcia@email.com',
                    'telefono' => '12345678',
                    'profesion' => 'Ingeniero en Sistemas',
                    'nivel_educativo' => 'Universitario',
                    'experiencia_laboral' => 5,
                    'estado' => 'Pendiente'
                ),
                array(
                    'nombres' => 'María Elena',
                    'apellidos' => 'Rodríguez Morales',
                    'dpi' => '1234567890102',
                    'email' => 'maria.rodriguez@email.com',
                    'telefono' => '87654321',
                    'profesion' => 'Contadora Pública',
                    'nivel_educativo' => 'Universitario',
                    'experiencia_laboral' => 3,
                    'estado' => 'Revisada'
                ),
                array(
                    'nombres' => 'Pedro Antonio',
                    'apellidos' => 'Martínez Pérez',
                    'dpi' => '1234567890103',
                    'email' => 'pedro.martinez@email.com',
                    'telefono' => '11223344',
                    'profesion' => 'Administrador',
                    'nivel_educativo' => 'Diversificado',
                    'experiencia_laboral' => 2,
                    'estado' => 'Aceptada'
                )
            );
            
            $insertados = 0;
            foreach ($aplicaciones_prueba as $aplicacion) {
                $aplicacion['vacante_id'] = $vacante->id;
                $aplicacion['fecha_aplicacion'] = current_time('mysql');
                
                $result = $wpdb->insert(
                    $wpdb->prefix . 'aplicaciones_minfin',
                    $aplicacion
                );
                
                if ($result) {
                    $insertados++;
                }
            }
            
            wp_send_json_success("Se crearon $insertados aplicaciones de prueba");
            
        } catch (Exception $e) {
            wp_send_json_error('Error al crear datos de prueba: ' . $e->getMessage());
        }
    }

    /**
     * Probar conexión a base de datos
     */
    public static function test_database_aplicaciones() {
        // Verificar nonce y permisos
        if (!wp_verify_nonce($_POST['nonce'], 'aplicaciones_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Acceso denegado');
            return;
        }
        
        global $wpdb;
        
        try {
            // Verificar tablas
            $table_aplicaciones = $wpdb->prefix . 'aplicaciones_minfin';
            $table_vacantes = $wpdb->prefix . 'vacantes_minfin';
            
            $aplicaciones_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_aplicaciones'");
            $vacantes_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_vacantes'");
            
            if (!$aplicaciones_exists) {
                wp_send_json_error("Tabla $table_aplicaciones no existe");
                return;
            }
            
            if (!$vacantes_exists) {
                wp_send_json_error("Tabla $table_vacantes no existe");
                return;
            }
            
            // Contar registros
            $count_aplicaciones = $wpdb->get_var("SELECT COUNT(*) FROM $table_aplicaciones");
            $count_vacantes = $wpdb->get_var("SELECT COUNT(*) FROM $table_vacantes");
            
            $mensaje = "Tablas OK. Aplicaciones: $count_aplicaciones, Vacantes: $count_vacantes";
            
            // Obtener una muestra
            if ($count_aplicaciones > 0) {
                $muestra = $wpdb->get_row("SELECT nombres, apellidos, email FROM $table_aplicaciones LIMIT 1");
                $mensaje .= ". Ejemplo: {$muestra->nombres} {$muestra->apellidos}";
            }
            
            wp_send_json_success($mensaje);
            
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

// Registrar handlers AJAX
add_action('wp_ajax_cargar_reporte_efectividad', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_reporte_efectividad'));
add_action('wp_ajax_cargar_reporte_tendencias', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_reporte_tendencias'));
add_action('wp_ajax_cargar_reporte_perfiles', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_reporte_perfiles'));
add_action('wp_ajax_cargar_reporte_vacantes', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_reporte_vacantes'));
add_action('wp_ajax_cargar_reporte_aplicaciones', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_reporte_aplicaciones'));
add_action('wp_ajax_cargar_direcciones', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_direcciones'));
add_action('wp_ajax_exportar_reporte_nuevo', array('Vacantes_Ajax_Reportes_Nuevo', 'exportar_reporte_nuevo'));
add_action('wp_ajax_cargar_vacantes', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_vacantes'));
add_action('wp_ajax_crear_vacante', array('Vacantes_Ajax_Reportes_Nuevo', 'crear_vacante'));
add_action('wp_ajax_actualizar_vacante', array('Vacantes_Ajax_Reportes_Nuevo', 'actualizar_vacante'));

// Handlers para gestión de aplicaciones
add_action('wp_ajax_cargar_aplicaciones_admin', array('Vacantes_Ajax_Reportes_Nuevo', 'cargar_aplicaciones_admin'));
add_action('wp_ajax_actualizar_estado_aplicacion', array('Vacantes_Ajax_Reportes_Nuevo', 'actualizar_estado_aplicacion'));
add_action('wp_ajax_exportar_aplicaciones_excel', array('Vacantes_Ajax_Reportes_Nuevo', 'exportar_aplicaciones_excel'));
add_action('wp_ajax_crear_datos_prueba_aplicaciones', array('Vacantes_Ajax_Reportes_Nuevo', 'crear_datos_prueba_aplicaciones'));
add_action('wp_ajax_test_database_aplicaciones', array('Vacantes_Ajax_Reportes_Nuevo', 'test_database_aplicaciones'));

// Handlers para envío de emails de respuesta
add_action('wp_ajax_enviar_email_respuesta', 'handle_enviar_email_respuesta_ajax');
add_action('wp_ajax_test_email_config', 'handle_test_email_config_ajax');

function handle_test_email_config_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
        return;
    }
    
    $admin_email = get_option('admin_email');
    $test_subject = 'Prueba de configuración de email - ' . get_bloginfo('name');
    $test_message = 'Este es un email de prueba para verificar que WordPress puede enviar correos.';
    
    $sent = wp_mail($admin_email, $test_subject, $test_message);
    
    if ($sent) {
        wp_send_json_success('Email de prueba enviado a ' . $admin_email);
    } else {
        wp_send_json_error('No se pudo enviar el email de prueba. Verifica la configuración SMTP.');
    }
}

function handle_enviar_email_respuesta_ajax() {
    try {
        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error('Método no permitido');
            return;
        }
        
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'enviar_email_respuesta_nonce')) {
            wp_send_json_error('Token de seguridad inválido');
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta acción');
            return;
        }
        
        // Obtener y validar datos
        $email_destinatario = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $asunto = isset($_POST['asunto']) ? sanitize_text_field($_POST['asunto']) : '';
        $mensaje = isset($_POST['mensaje']) ? sanitize_textarea_field($_POST['mensaje']) : '';
        
        // Validar datos
        if (empty($email_destinatario) || !is_email($email_destinatario)) {
            wp_send_json_error('Email de destinatario inválido: ' . $email_destinatario);
            return;
        }
        
        if (empty($asunto)) {
            wp_send_json_error('El asunto es requerido');
            return;
        }
        
        if (empty($mensaje)) {
            wp_send_json_error('El mensaje es requerido');
            return;
        }
        
        // Configurar headers para HTML
        $headers = array(
            'Content-Type: text/html; charset=UTF-8'
        );
        
        // Agregar From si está configurado
        $from_name = get_option('vacantes_minfin_email_from_name', get_bloginfo('name'));
        $from_email = get_option('admin_email');
        if (!empty($from_name) && !empty($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        
        // Convertir saltos de línea a HTML
        $mensaje_html = nl2br(esc_html($mensaje));
        
        // Hook para capturar errores de wp_mail
        $mail_error = '';
        add_action('wp_mail_failed', function($wp_error) use (&$mail_error) {
            $mail_error = $wp_error->get_error_message();
        });
        
        // Enviar email usando wp_mail
        $enviado = wp_mail($email_destinatario, $asunto, $mensaje_html, $headers);
        
        if ($enviado) {
            wp_send_json_success('Email enviado correctamente a ' . $email_destinatario);
        } else {
            $error_message = 'Error al enviar el email.';
            if (!empty($mail_error)) {
                $error_message .= ' Detalle: ' . $mail_error;
            } else {
                $error_message .= ' Verifica la configuración de correo en WordPress.';
            }
            wp_send_json_error($error_message);
        }
        
    } catch (Exception $e) {
        wp_send_json_error('Error inesperado: ' . $e->getMessage());
    }
}
?>