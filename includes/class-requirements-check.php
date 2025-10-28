<?php

/**
 * Verificación de requisitos del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Requirements_Check {

    /**
     * Verificar todos los requisitos
     */
    public static function check_requirements() {
        $errors = array();
        
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = sprintf(
                __('El plugin requiere PHP 7.4 o superior. Versión actual: %s', 'vacantes-minfin'),
                PHP_VERSION
            );
        }
        
        // Verificar versión de WordPress
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            $errors[] = sprintf(
                __('El plugin requiere WordPress 5.0 o superior. Versión actual: %s', 'vacantes-minfin'),
                $wp_version
            );
        }
        
        // Verificar extensiones PHP necesarias
        $required_extensions = array('mysqli', 'json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $errors[] = sprintf(
                    __('Extensión PHP requerida no encontrada: %s', 'vacantes-minfin'),
                    $extension
                );
            }
        }
        
        // Verificar permisos de escritura
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $errors[] = __('El directorio de uploads no tiene permisos de escritura', 'vacantes-minfin');
        }
        
        return $errors;
    }

    /**
     * Mostrar errores de requisitos
     */
    public static function show_requirements_errors($errors) {
        if (empty($errors)) {
            return;
        }
        
        echo '<div class="notice notice-error">';
        echo '<p><strong>' . __('Plugin Vacantes MINFIN - Errores de requisitos:', 'vacantes-minfin') . '</strong></p>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
        echo '<p>' . __('Por favor, corrija estos problemas antes de activar el plugin.', 'vacantes-minfin') . '</p>';
        echo '</div>';
    }

    /**
     * Verificar archivos del plugin
     */
    public static function check_plugin_files() {
        $required_files = array(
            'includes/class-vacantes-core.php',
            'includes/class-vacantes-activator.php',
            'includes/class-vacantes-deactivator.php',
            'includes/database/class-database-manager.php',
            'models/class-vacante.php',
            'models/class-aplicacion.php',
            'models/class-direccion.php',
            'models/class-tipo-contrato.php',
            'services/class-email-service.php',
            'services/class-file-service.php',
            'admin/class-vacantes-admin.php',
            'public/class-vacantes-public.php'
        );
        
        $missing_files = array();
        
        foreach ($required_files as $file) {
            $file_path = VACANTES_MINFIN_PLUGIN_DIR . $file;
            if (!file_exists($file_path)) {
                $missing_files[] = $file;
            }
        }
        
        return $missing_files;
    }
}