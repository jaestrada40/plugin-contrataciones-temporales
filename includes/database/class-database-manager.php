<?php
/**
 * Gestor de base de datos para Vacantes MINFIN
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Database_Manager {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de Direcciones
        $table_direcciones = $wpdb->prefix . 'direcciones_minfin';
        $sql_direcciones = "CREATE TABLE $table_direcciones (
            id int(11) NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            responsable varchar(255),
            email_contacto varchar(255),
            telefono varchar(50),
            direccion_fisica text,
            icono_url varchar(500),
            correlativo varchar(10) DEFAULT '',
            formato_codigo varchar(50) DEFAULT 'VAC-{CORRELATIVO}-{NUMERO}',
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY activa (activa)
        ) $charset_collate;";
        
        // Tabla de Tipos de Contrato
        $table_tipos = $wpdb->prefix . 'tipos_contrato_minfin';
        $sql_tipos = "CREATE TABLE $table_tipos (
            id int(11) NOT NULL AUTO_INCREMENT,
            codigo varchar(20) NOT NULL,
            nombre varchar(100) NOT NULL,
            descripcion text,
            es_estandar tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY activo (activo),
            KEY es_estandar (es_estandar)
        ) $charset_collate;";
        
        // Tabla de Vacantes
        $table_vacantes = $wpdb->prefix . 'vacantes_minfin';
        $sql_vacantes = "CREATE TABLE $table_vacantes (
            id int(11) NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            requisitos text,
            beneficios text,
            salario_min decimal(10,2) DEFAULT 0,
            salario_max decimal(10,2) DEFAULT 0,
            direccion_id int(11) NOT NULL,
            tipo_contrato_id int(11) NOT NULL,
            fecha_limite date NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime ON UPDATE CURRENT_TIMESTAMP,
            estado varchar(50) DEFAULT 'Activa',
            ubicacion varchar(255),
            modalidad varchar(50) DEFAULT 'Presencial',
            experiencia_requerida int DEFAULT 0,
            nivel_educativo varchar(100),
            bases_pdf_nombre varchar(255),
            bases_pdf_ruta varchar(500),
            bases_pdf_tamano bigint DEFAULT 0,
            bases_pdf_fecha_subida datetime,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY direccion_id (direccion_id),
            KEY tipo_contrato_id (tipo_contrato_id),
            KEY estado (estado),
            KEY fecha_limite (fecha_limite)
        ) $charset_collate;";
        
        // Tabla de Aplicaciones
        $table_aplicaciones = $wpdb->prefix . 'aplicaciones_minfin';
        $sql_aplicaciones = "CREATE TABLE $table_aplicaciones (
            id int(11) NOT NULL AUTO_INCREMENT,
            vacante_id int(11) NOT NULL,
            nombres varchar(100) NOT NULL,
            apellidos varchar(100) NOT NULL,
            dpi varchar(20) NOT NULL,
            email varchar(255) NOT NULL,
            telefono varchar(50),
            direccion text,
            nivel_educativo varchar(100),
            profesion varchar(100),
            experiencia_anios int DEFAULT 0,
            carta_presentacion text,
            cv_nombre varchar(255),
            cv_url varchar(500),
            cv_ruta_archivo varchar(500),
            cv_tamano bigint DEFAULT 0,
            cv_fecha_subida datetime,
            fecha_aplicacion datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(50) DEFAULT 'Pendiente',
            comentarios_admin text,
            fecha_actualizacion datetime ON UPDATE CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY vacante_id (vacante_id),
            KEY estado (estado),
            KEY fecha_aplicacion (fecha_aplicacion),
            KEY email (email),
            KEY dpi (dpi),
            UNIQUE KEY unique_aplicacion (vacante_id, dpi)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_direcciones);
        dbDelta($sql_tipos);
        dbDelta($sql_vacantes);
        dbDelta($sql_aplicaciones);
        
        // Crear índices adicionales si es necesario
        self::create_additional_indexes();
        
        // Actualizar versión de la base de datos
        update_option('vacantes_minfin_db_version', VACANTES_MINFIN_VERSION);
    }
    
    private static function create_additional_indexes() {
        global $wpdb;
        
        // Índices para mejorar rendimiento de búsquedas
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_vacantes_busqueda ON {$wpdb->prefix}vacantes_minfin (titulo, descripcion(100))");
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_aplicaciones_busqueda ON {$wpdb->prefix}aplicaciones_minfin (nombres, apellidos, email)");
    }
    
    public static function insert_initial_data() {
        global $wpdb;
        
        // Verificar si ya existen datos
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}direcciones_minfin");
        if ($count > 0) {
            return; // Ya hay datos, no insertar duplicados
        }
        
        // Insertar direcciones iniciales
        $direcciones = array(
            array(
                'nombre' => 'Dirección General de Recursos Humanos',
                'descripcion' => 'Encargada de la gestión del talento humano del ministerio',
                'email_contacto' => 'rrhh@minfin.gob.gt',
                'correlativo' => 'DGRH',
                'formato_codigo' => 'VAC-{CORRELATIVO}-{NUMERO}',
                'activa' => 1
            ),
            array(
                'nombre' => 'Dirección General de Administración Financiera',
                'descripcion' => 'Responsable de la administración financiera',
                'email_contacto' => 'admin@minfin.gob.gt',
                'correlativo' => 'DGAF',
                'formato_codigo' => 'VAC-{CORRELATIVO}-{NUMERO}',
                'activa' => 1
            ),
            array(
                'nombre' => 'Dirección General de Contrataciones Públicas',
                'descripcion' => 'Gestión de contrataciones y adquisiciones',
                'email_contacto' => 'contrataciones@minfin.gob.gt',
                'correlativo' => 'DGCP',
                'formato_codigo' => 'VAC-{CORRELATIVO}-{NUMERO}',
                'activa' => 1
            )
        );
        
        foreach ($direcciones as $direccion) {
            $wpdb->insert(
                $wpdb->prefix . 'direcciones_minfin',
                $direccion,
                array('%s', '%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Insertar tipos de contrato iniciales
        $tipos_contrato = array(
            array(
                'codigo' => 'CTI',
                'nombre' => 'Contrato por Tiempo Indefinido',
                'descripcion' => 'Contrato permanente según la ley de servicio civil',
                'es_estandar' => 1,
                'activo' => 1
            ),
            array(
                'codigo' => 'CTD',
                'nombre' => 'Contrato por Tiempo Definido',
                'descripcion' => 'Contrato temporal con fecha de finalización',
                'es_estandar' => 1,
                'activo' => 1
            ),
            array(
                'codigo' => 'CONS',
                'nombre' => 'Consultoría',
                'descripcion' => 'Contrato de servicios profesionales',
                'es_estandar' => 1,
                'activo' => 1
            ),
            array(
                'codigo' => 'PRAC',
                'nombre' => 'Práctica Profesional',
                'descripcion' => 'Programa de prácticas para estudiantes',
                'es_estandar' => 1,
                'activo' => 1
            )
        );
        
        foreach ($tipos_contrato as $tipo) {
            $wpdb->insert(
                $wpdb->prefix . 'tipos_contrato_minfin',
                $tipo,
                array('%s', '%s', '%s', '%d', '%d')
            );
        }
    }
    
    public static function update_database() {
        // Verificar si necesita actualizaciones
        $current_version = get_option('vacantes_minfin_db_version', '1.0');
        
        if (version_compare($current_version, VACANTES_MINFIN_VERSION, '<')) {
            // Ejecutar actualizaciones necesarias
            self::run_database_updates($current_version);
            
            // Actualizar versión
            update_option('vacantes_minfin_db_version', VACANTES_MINFIN_VERSION);
        }
    }
    
    private static function run_database_updates($from_version) {
        global $wpdb;
        
        // Ejemplo de actualización de versión 1.0 a 2.0
        if (version_compare($from_version, '2.0', '<')) {
            // Agregar nuevas columnas si no existen
            $wpdb->query("ALTER TABLE {$wpdb->prefix}vacantes_minfin 
                         ADD COLUMN IF NOT EXISTS beneficios text AFTER requisitos");
            
            $wpdb->query("ALTER TABLE {$wpdb->prefix}aplicaciones_minfin 
                         ADD COLUMN IF NOT EXISTS ip_address varchar(45) AFTER fecha_actualizacion");
        }
    }
    
    public static function check_database_integrity() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'direcciones_minfin',
            $wpdb->prefix . 'tipos_contrato_minfin',
            $wpdb->prefix . 'vacantes_minfin',
            $wpdb->prefix . 'aplicaciones_minfin'
        );
        
        $missing_tables = array();
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                $missing_tables[] = $table;
            }
        }
        
        return empty($missing_tables) ? true : $missing_tables;
    }
    
    public static function drop_tables() {
        global $wpdb;
        
        // Solo para desinstalación completa
        $tables = array(
            $wpdb->prefix . 'aplicaciones_minfin',
            $wpdb->prefix . 'vacantes_minfin',
            $wpdb->prefix . 'tipos_contrato_minfin',
            $wpdb->prefix . 'direcciones_minfin'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Eliminar opciones
        delete_option('vacantes_minfin_db_version');
    }
}