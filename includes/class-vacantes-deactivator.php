<?php

/**
 * Clase para manejar la desactivación del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Deactivator {

    /**
     * Ejecutar cuando se desactiva el plugin
     */
    public static function deactivate() {
        // Limpiar eventos programados
        wp_clear_scheduled_hook('vacantes_cleanup_temp_files');
        wp_clear_scheduled_hook('vacantes_send_notifications');
        
        // Limpiar cache si existe
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Limpiar opciones temporales
        delete_option('vacantes_temp_data');
        delete_transient('vacantes_stats_cache');
        
        // Log de desactivación
        error_log('Plugin Vacantes MINFIN desactivado correctamente');
    }
}