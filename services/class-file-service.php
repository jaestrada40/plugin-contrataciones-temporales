<?php
/**
 * Servicio de Archivos para Vacantes MINFIN
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_File_Service {
    
    private $upload_dir;
    private $max_file_size;
    private $allowed_extensions;
    
    public function __construct() {
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/vacantes';
        $this->max_file_size = get_option('vacantes_minfin_max_file_size', 10 * 1024 * 1024); // 10MB
        $this->allowed_extensions = explode(',', get_option('vacantes_minfin_allowed_extensions', 'pdf,doc,docx'));
        
        // Crear directorio si no existe
        $this->ensure_upload_directory();
    }
    
    /**
     * Subir archivo CV
     */
    public function upload_cv($file_data, $aplicacion_id = null) {
        try {
            // Validar archivo
            $validation = $this->validate_file($file_data);
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            // Generar nombre único
            $file_info = pathinfo($file_data['name']);
            $extension = strtolower($file_info['extension']);
            $filename = $this->generate_unique_filename('cv', $extension, $aplicacion_id);
            
            // Ruta completa
            $cv_dir = $this->upload_dir . '/cv';
            $file_path = $cv_dir . '/' . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($file_data['tmp_name'], $file_path)) {
                return new WP_Error('upload_failed', 'Error al subir el archivo');
            }
            
            // Generar URL
            $wp_upload_dir = wp_upload_dir();
            $file_url = $wp_upload_dir['baseurl'] . '/vacantes/cv/' . $filename;
            
            return array(
                'nombre' => $filename,
                'ruta' => $file_url,
                'tamano' => filesize($file_path),
                'tipo' => $file_data['type']
            );
            
        } catch (Exception $e) {
            return new WP_Error('upload_error', $e->getMessage());
        }
    }
    
    /**
     * Subir archivo PDF de bases
     */
    public function upload_bases_pdf($file_data, $vacante_id = null) {
        try {
            // Validar que sea PDF
            if (!$this->is_pdf($file_data)) {
                return new WP_Error('invalid_pdf', 'El archivo debe ser un PDF');
            }
            
            // Validar tamaño
            if ($file_data['size'] > $this->max_file_size) {
                return new WP_Error('file_too_large', 'El archivo es demasiado grande');
            }
            
            // Generar nombre único
            $filename = $this->generate_unique_filename('bases', 'pdf', $vacante_id);
            
            // Ruta completa
            $bases_dir = $this->upload_dir . '/bases-pdf';
            $file_path = $bases_dir . '/' . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($file_data['tmp_name'], $file_path)) {
                return new WP_Error('upload_failed', 'Error al subir el archivo PDF');
            }
            
            // Generar URL
            $wp_upload_dir = wp_upload_dir();
            $file_url = $wp_upload_dir['baseurl'] . '/vacantes/bases-pdf/' . $filename;
            
            return array(
                'nombre' => $filename,
                'ruta' => $file_url,
                'tamano' => filesize($file_path),
                'tipo' => 'application/pdf'
            );
            
        } catch (Exception $e) {
            return new WP_Error('upload_error', $e->getMessage());
        }
    }
    
    /**
     * Eliminar archivo
     */
    public function delete_file($file_path) {
        if (empty($file_path)) {
            return false;
        }
        
        // Convertir URL a path si es necesario
        $wp_upload_dir = wp_upload_dir();
        $local_path = str_replace($wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $file_path);
        
        // Verificar que el archivo esté en nuestro directorio
        if (strpos($local_path, $this->upload_dir) !== 0) {
            return new WP_Error('invalid_path', 'Ruta de archivo no válida');
        }
        
        if (file_exists($local_path)) {
            return wp_delete_file($local_path);
        }
        
        return false;
    }
    
    /**
     * Obtener información de archivo
     */
    public function get_file_info($file_path) {
        if (empty($file_path)) {
            return null;
        }
        
        $wp_upload_dir = wp_upload_dir();
        $local_path = str_replace($wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $file_path);
        
        if (!file_exists($local_path)) {
            return null;
        }
        
        return array(
            'exists' => true,
            'size' => filesize($local_path),
            'modified' => filemtime($local_path),
            'readable' => is_readable($local_path),
            'mime_type' => wp_check_filetype($local_path)['type']
        );
    }
    
    /**
     * Generar enlace de descarga seguro
     */
    public function generate_download_link($file_path, $filename = null) {
        if (empty($file_path)) {
            return '';
        }
        
        // Generar nonce para seguridad
        $nonce = wp_create_nonce('vacantes_download_' . md5($file_path));
        
        return add_query_arg(array(
            'action' => 'vacantes_download_file',
            'file' => base64_encode($file_path),
            'filename' => $filename ? base64_encode($filename) : '',
            'nonce' => $nonce
        ), admin_url('admin-ajax.php'));
    }
    
    /**
     * Manejar descarga de archivo
     */
    public function handle_file_download() {
        // Verificar nonce
        $file_encoded = $_GET['file'] ?? '';
        $nonce = $_GET['nonce'] ?? '';
        
        if (empty($file_encoded) || empty($nonce)) {
            wp_die('Acceso denegado');
        }
        
        $file_path = base64_decode($file_encoded);
        
        if (!wp_verify_nonce($nonce, 'vacantes_download_' . md5($file_path))) {
            wp_die('Acceso denegado');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_vacantes') && !current_user_can('manage_aplicaciones')) {
            wp_die('No tiene permisos para descargar este archivo');
        }
        
        // Convertir URL a path
        $wp_upload_dir = wp_upload_dir();
        $local_path = str_replace($wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $file_path);
        
        // Verificar que el archivo existe y está en nuestro directorio
        if (!file_exists($local_path) || strpos($local_path, $this->upload_dir) !== 0) {
            wp_die('Archivo no encontrado');
        }
        
        // Obtener nombre de archivo
        $filename = $_GET['filename'] ? base64_decode($_GET['filename']) : basename($local_path);
        
        // Headers para descarga
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($local_path));
        header('Cache-Control: no-cache, must-revalidate');
        
        // Enviar archivo
        readfile($local_path);
        exit;
    }
    
    /**
     * Validar archivo subido
     */
    private function validate_file($file_data) {
        // Verificar errores de subida
        if ($file_data['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', $this->get_upload_error_message($file_data['error']));
        }
        
        // Verificar tamaño
        if ($file_data['size'] > $this->max_file_size) {
            $max_mb = round($this->max_file_size / (1024 * 1024), 1);
            return new WP_Error('file_too_large', sprintf('El archivo es demasiado grande. Máximo permitido: %s MB', $max_mb));
        }
        
        // Verificar extensión
        $file_info = pathinfo($file_data['name']);
        $extension = strtolower($file_info['extension'] ?? '');
        
        if (!in_array($extension, $this->allowed_extensions)) {
            return new WP_Error('invalid_extension', 'Tipo de archivo no permitido. Extensiones válidas: ' . implode(', ', $this->allowed_extensions));
        }
        
        // Verificar tipo MIME
        $allowed_mimes = $this->get_allowed_mime_types();
        $file_type = wp_check_filetype($file_data['name'], $allowed_mimes);
        
        if (!$file_type['type']) {
            return new WP_Error('invalid_mime', 'Tipo de archivo no válido');
        }
        
        // Verificar que el archivo no esté vacío
        if ($file_data['size'] === 0) {
            return new WP_Error('empty_file', 'El archivo está vacío');
        }
        
        return true;
    }
    
    /**
     * Verificar si es PDF
     */
    private function is_pdf($file_data) {
        $file_info = pathinfo($file_data['name']);
        $extension = strtolower($file_info['extension'] ?? '');
        
        return $extension === 'pdf' && $file_data['type'] === 'application/pdf';
    }
    
    /**
     * Generar nombre único de archivo
     */
    private function generate_unique_filename($prefix, $extension, $id = null) {
        $timestamp = time();
        $random = wp_generate_password(8, false);
        $id_part = $id ? "_{$id}" : '';
        
        return "{$prefix}{$id_part}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Asegurar que el directorio de subida existe
     */
    private function ensure_upload_directory() {
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
        
        // Crear subdirectorios
        $subdirs = array('cv', 'bases-pdf');
        foreach ($subdirs as $subdir) {
            $dir_path = $this->upload_dir . '/' . $subdir;
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
            }
        }
        
        // Crear archivo .htaccess para seguridad
        $htaccess_path = $this->upload_dir . '/.htaccess';
        if (!file_exists($htaccess_path)) {
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_path, $htaccess_content);
        }
        
        // Crear archivo index.php para prevenir listado
        $index_path = $this->upload_dir . '/index.php';
        if (!file_exists($index_path)) {
            file_put_contents($index_path, '<?php // Silence is golden');
        }
    }
    
    /**
     * Obtener tipos MIME permitidos
     */
    private function get_allowed_mime_types() {
        return array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        );
    }
    
    /**
     * Obtener mensaje de error de subida
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo es demasiado grande';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se seleccionó ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta el directorio temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Subida detenida por extensión';
            default:
                return 'Error desconocido al subir el archivo';
        }
    }
    
    /**
     * Limpiar archivos antiguos
     */
    public function cleanup_old_files($days = 30) {
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        $deleted_count = 0;
        
        $directories = array(
            $this->upload_dir . '/cv',
            $this->upload_dir . '/bases-pdf'
        );
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === 'index.php') {
                    continue;
                }
                
                $file_path = $dir . '/' . $file;
                if (is_file($file_path) && filemtime($file_path) < $cutoff_time) {
                    // Verificar que el archivo no esté referenciado en la BD
                    if (!$this->is_file_referenced($file)) {
                        if (wp_delete_file($file_path)) {
                            $deleted_count++;
                        }
                    }
                }
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Verificar si un archivo está referenciado en la base de datos
     */
    private function is_file_referenced($filename) {
        global $wpdb;
        
        // Buscar en aplicaciones
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes_aplicaciones WHERE cv_nombre = %s",
                $filename
            )
        );
        
        if ($count > 0) {
            return true;
        }
        
        // Buscar en vacantes
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vacantes WHERE bases_pdf_nombre = %s",
                $filename
            )
        );
        
        return $count > 0;
    }
    
    /**
     * Obtener estadísticas de archivos
     */
    public function get_file_stats() {
        $stats = array(
            'cv_count' => 0,
            'cv_size' => 0,
            'bases_count' => 0,
            'bases_size' => 0,
            'total_size' => 0
        );
        
        // Estadísticas de CV
        $cv_dir = $this->upload_dir . '/cv';
        if (is_dir($cv_dir)) {
            $files = scandir($cv_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && $file !== 'index.php') {
                    $file_path = $cv_dir . '/' . $file;
                    if (is_file($file_path)) {
                        $stats['cv_count']++;
                        $stats['cv_size'] += filesize($file_path);
                    }
                }
            }
        }
        
        // Estadísticas de bases PDF
        $bases_dir = $this->upload_dir . '/bases-pdf';
        if (is_dir($bases_dir)) {
            $files = scandir($bases_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && $file !== 'index.php') {
                    $file_path = $bases_dir . '/' . $file;
                    if (is_file($file_path)) {
                        $stats['bases_count']++;
                        $stats['bases_size'] += filesize($file_path);
                    }
                }
            }
        }
        
        $stats['total_size'] = $stats['cv_size'] + $stats['bases_size'];
        
        return $stats;
    }
}