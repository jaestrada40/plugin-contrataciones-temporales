<?php

/**
 * Servicio de Notificaciones
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Notification_Service {

    /**
     * Enviar notificación de nueva aplicación
     */
    public function notificar_nueva_aplicacion($aplicacion_id) {
        global $wpdb;
        
        // Obtener datos de la aplicación
        $aplicacion = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, v.titulo as vacante_titulo, v.codigo as vacante_codigo,
                   d.nombre as direccion_nombre
            FROM {$wpdb->prefix}vacantes_aplicaciones a
            LEFT JOIN {$wpdb->prefix}vacantes v ON a.vacante_id = v.id
            LEFT JOIN {$wpdb->prefix}vacantes_direcciones d ON v.direccion_id = d.id
            WHERE a.id = %d
        ", $aplicacion_id));
        
        if (!$aplicacion) {
            return false;
        }
        
        // Obtener administradores para notificar
        $admins = get_users(array(
            'role' => 'administrator',
            'meta_key' => 'vacantes_notifications',
            'meta_value' => '1'
        ));
        
        $email_service = new Vacantes_Email_Service();
        
        foreach ($admins as $admin) {
            $email_service->enviar_notificacion_admin($admin->user_email, $aplicacion);
        }
        
        return true;
    }

    /**
     * Notificar cambio de estado de aplicación
     */
    public function notificar_cambio_estado($aplicacion_id, $nuevo_estado) {
        global $wpdb;
        
        $aplicacion = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, v.titulo as vacante_titulo, v.codigo as vacante_codigo
            FROM {$wpdb->prefix}vacantes_aplicaciones a
            LEFT JOIN {$wpdb->prefix}vacantes v ON a.vacante_id = v.id
            WHERE a.id = %d
        ", $aplicacion_id));
        
        if (!$aplicacion) {
            return false;
        }
        
        $email_service = new Vacantes_Email_Service();
        return $email_service->enviar_cambio_estado($aplicacion->email, $aplicacion, $nuevo_estado);
    }

    /**
     * Recordatorio de cierre de vacantes
     */
    public function enviar_recordatorios_cierre() {
        global $wpdb;
        
        // Vacantes que cierran en 3 días
        $vacantes_por_cerrar = $wpdb->get_results("
            SELECT v.*, d.nombre as direccion_nombre,
                   COUNT(a.id) as total_aplicaciones
            FROM {$wpdb->prefix}vacantes v
            LEFT JOIN {$wpdb->prefix}vacantes_direcciones d ON v.direccion_id = d.id
            LEFT JOIN {$wpdb->prefix}vacantes_aplicaciones a ON v.id = a.vacante_id
            WHERE v.activa = 1 
            AND v.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
            GROUP BY v.id
        ");
        
        if (empty($vacantes_por_cerrar)) {
            return false;
        }
        
        $admins = get_users(array('role' => 'administrator'));
        $email_service = new Vacantes_Email_Service();
        
        foreach ($admins as $admin) {
            $email_service->enviar_recordatorio_cierre($admin->user_email, $vacantes_por_cerrar);
        }
        
        return true;
    }

    /**
     * Programar notificaciones automáticas
     */
    public function programar_notificaciones() {
        // Recordatorios diarios de cierre
        if (!wp_next_scheduled('vacantes_recordatorio_cierre')) {
            wp_schedule_event(time(), 'daily', 'vacantes_recordatorio_cierre');
        }
        
        // Limpieza semanal de archivos temporales
        if (!wp_next_scheduled('vacantes_cleanup_temp')) {
            wp_schedule_event(time(), 'weekly', 'vacantes_cleanup_temp');
        }
    }

    /**
     * Limpiar archivos temporales
     */
    public function limpiar_archivos_temporales() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/vacantes/temp/';
        
        if (!is_dir($temp_dir)) {
            return false;
        }
        
        $files = glob($temp_dir . '*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < strtotime('-7 days')) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    /**
     * Crear notificación en el dashboard
     */
    public function crear_notificacion_dashboard($titulo, $mensaje, $tipo = 'info') {
        $notificaciones = get_option('vacantes_dashboard_notifications', array());
        
        $notificacion = array(
            'id' => uniqid(),
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo, // info, success, warning, error
            'fecha' => current_time('mysql'),
            'leida' => false
        );
        
        array_unshift($notificaciones, $notificacion);
        
        // Mantener solo las últimas 50 notificaciones
        $notificaciones = array_slice($notificaciones, 0, 50);
        
        update_option('vacantes_dashboard_notifications', $notificaciones);
        
        return $notificacion['id'];
    }

    /**
     * Marcar notificación como leída
     */
    public function marcar_como_leida($notificacion_id) {
        $notificaciones = get_option('vacantes_dashboard_notifications', array());
        
        foreach ($notificaciones as &$notificacion) {
            if ($notificacion['id'] === $notificacion_id) {
                $notificacion['leida'] = true;
                break;
            }
        }
        
        update_option('vacantes_dashboard_notifications', $notificaciones);
        return true;
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function obtener_no_leidas() {
        $notificaciones = get_option('vacantes_dashboard_notifications', array());
        
        return array_filter($notificaciones, function($notificacion) {
            return !$notificacion['leida'];
        });
    }
}