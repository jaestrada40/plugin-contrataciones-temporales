<?php

/**
 * Servicio de Reportes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Report_Service {

    /**
     * Generar reporte de vacantes
     */
    public function generar_reporte_vacantes($filtros = array()) {
        global $wpdb;
        
        $fecha_inicio = isset($filtros['fecha_inicio']) ? $filtros['fecha_inicio'] : date('Y-m-01');
        $fecha_fin = isset($filtros['fecha_fin']) ? $filtros['fecha_fin'] : date('Y-m-t');
        $direccion_id = isset($filtros['direccion_id']) ? intval($filtros['direccion_id']) : 0;
        
        $where_conditions = array();
        $where_conditions[] = $wpdb->prepare("v.fecha_creacion BETWEEN %s AND %s", $fecha_inicio, $fecha_fin);
        
        if ($direccion_id > 0) {
            $where_conditions[] = $wpdb->prepare("v.direccion_id = %d", $direccion_id);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT v.*, 
                   d.nombre as direccion_nombre,
                   COUNT(a.id) as total_aplicaciones
            FROM {$wpdb->prefix}vacantes v
            LEFT JOIN {$wpdb->prefix}vacantes_direcciones d ON v.direccion_id = d.id
            LEFT JOIN {$wpdb->prefix}vacantes_aplicaciones a ON v.id = a.vacante_id
            {$where_clause}
            GROUP BY v.id
            ORDER BY v.fecha_creacion DESC
        ";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Generar reporte de aplicaciones
     */
    public function generar_reporte_aplicaciones($filtros = array()) {
        global $wpdb;
        
        $fecha_inicio = isset($filtros['fecha_inicio']) ? $filtros['fecha_inicio'] : date('Y-m-01');
        $fecha_fin = isset($filtros['fecha_fin']) ? $filtros['fecha_fin'] : date('Y-m-t');
        $estado = isset($filtros['estado']) ? $filtros['estado'] : '';
        
        $where_conditions = array();
        $where_conditions[] = $wpdb->prepare("a.fecha_aplicacion BETWEEN %s AND %s", $fecha_inicio, $fecha_fin);
        
        if (!empty($estado)) {
            $where_conditions[] = $wpdb->prepare("a.estado = %s", $estado);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT a.*, 
                   v.titulo as vacante_titulo,
                   v.codigo as vacante_codigo,
                   d.nombre as direccion_nombre
            FROM {$wpdb->prefix}vacantes_aplicaciones a
            LEFT JOIN {$wpdb->prefix}vacantes v ON a.vacante_id = v.id
            LEFT JOIN {$wpdb->prefix}vacantes_direcciones d ON v.direccion_id = d.id
            {$where_clause}
            ORDER BY a.fecha_aplicacion DESC
        ";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Exportar a CSV
     */
    public function exportar_csv($datos, $nombre_archivo) {
        if (empty($datos)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $nombre_archivo . '.csv';
        
        $file = fopen($file_path, 'w');
        
        // Escribir encabezados
        $headers = array_keys((array) $datos[0]);
        fputcsv($file, $headers);
        
        // Escribir datos
        foreach ($datos as $row) {
            fputcsv($file, (array) $row);
        }
        
        fclose($file);
        
        return $upload_dir['url'] . '/' . $nombre_archivo . '.csv';
    }

    /**
     * Obtener estadísticas generales
     */
    public function obtener_estadisticas() {
        global $wpdb;
        
        $stats = array();
        
        // Vacantes por estado
        $stats['vacantes_por_estado'] = $wpdb->get_results("
            SELECT 
                CASE WHEN activa = 1 THEN 'Activa' ELSE 'Inactiva' END as estado,
                COUNT(*) as total
            FROM {$wpdb->prefix}vacantes 
            GROUP BY activa
        ");
        
        // Aplicaciones por estado
        $stats['aplicaciones_por_estado'] = $wpdb->get_results("
            SELECT estado, COUNT(*) as total
            FROM {$wpdb->prefix}vacantes_aplicaciones 
            GROUP BY estado
        ");
        
        // Vacantes por dirección
        $stats['vacantes_por_direccion'] = $wpdb->get_results("
            SELECT d.nombre, COUNT(v.id) as total
            FROM {$wpdb->prefix}vacantes_direcciones d
            LEFT JOIN {$wpdb->prefix}vacantes v ON d.id = v.direccion_id
            GROUP BY d.id, d.nombre
            ORDER BY total DESC
        ");
        
        // Aplicaciones por mes (últimos 6 meses)
        $stats['aplicaciones_por_mes'] = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(fecha_aplicacion, '%Y-%m') as mes,
                COUNT(*) as total
            FROM {$wpdb->prefix}vacantes_aplicaciones 
            WHERE fecha_aplicacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_aplicacion, '%Y-%m')
            ORDER BY mes DESC
        ");
        
        return $stats;
    }
}