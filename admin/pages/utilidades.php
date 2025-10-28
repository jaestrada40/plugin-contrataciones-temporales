<?php
/**
 * Página de Utilidades - Reparar y actualizar tablas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}

global $wpdb;

$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'reparar_tipos_contrato':
            $table_name = $wpdb->prefix . 'vs_tipos_contrato';
            
            try {
                // Verificar si la tabla existe
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                
                if (!$table_exists) {
                    // Crear tabla completa
                    $sql = "CREATE TABLE $table_name (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        codigo varchar(10) NOT NULL,
                        nombre varchar(100) NOT NULL,
                        descripcion text,
                        activo tinyint(1) DEFAULT 1,
                        es_estandar tinyint(1) DEFAULT 0,
                        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        UNIQUE KEY codigo (codigo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                    
                    $wpdb->query($sql);
                    $message = 'Tabla vs_tipos_contrato creada exitosamente.';
                } else {
                    // Verificar columnas existentes
                    $columns = $wpdb->get_results("DESCRIBE $table_name");
                    $existing_columns = array();
                    
                    foreach ($columns as $column) {
                        $existing_columns[] = $column->Field;
                    }
                    
                    $updates = array();
                    
                    // Agregar columna 'codigo' si no existe
                    if (!in_array('codigo', $existing_columns)) {
                        $wpdb->query("ALTER TABLE $table_name ADD COLUMN codigo varchar(10) NOT NULL DEFAULT '' AFTER id");
                        $updates[] = 'Columna codigo agregada';
                    }
                    
                    // Agregar columna 'es_estandar' si no existe
                    if (!in_array('es_estandar', $existing_columns)) {
                        $wpdb->query("ALTER TABLE $table_name ADD COLUMN es_estandar tinyint(1) DEFAULT 0 AFTER activo");
                        $updates[] = 'Columna es_estandar agregada';
                    }
                    
                    // Agregar columna 'fecha_creacion' si no existe
                    if (!in_array('fecha_creacion', $existing_columns)) {
                        $wpdb->query("ALTER TABLE $table_name ADD COLUMN fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP");
                        $updates[] = 'Columna fecha_creacion agregada';
                    }
                    
                    // Actualizar registros sin código
                    $sin_codigo = $wpdb->get_results("SELECT * FROM $table_name WHERE codigo = '' OR codigo IS NULL");
                    
                    foreach ($sin_codigo as $registro) {
                        // Generar código basado en el nombre
                        $codigo = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $registro->nombre), 0, 6));
                        
                        // Asegurar que el código sea único
                        $contador = 1;
                        $codigo_original = $codigo;
                        
                        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE codigo = %s AND id != %d", $codigo, $registro->id)) > 0) {
                            $codigo = $codigo_original . $contador;
                            $contador++;
                        }
                        
                        // Actualizar el registro
                        $wpdb->update(
                            $table_name,
                            array('codigo' => $codigo),
                            array('id' => $registro->id)
                        );
                        
                        $updates[] = "Código '$codigo' asignado a '{$registro->nombre}'";
                    }
                    
                    // Agregar índice único si no existe
                    $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = 'codigo'");
                    if (empty($indexes)) {
                        $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY codigo (codigo)");
                        $updates[] = 'Índice único agregado para codigo';
                    }
                    
                    if (!empty($updates)) {
                        $message = 'Tabla actualizada: ' . implode(', ', $updates);
                    } else {
                        $message = 'La tabla ya está actualizada correctamente.';
                    }
                }
                
                // Insertar tipos estándar si no existen
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                
                if ($count == 0) {
                    $tipos_estandar = array(
                        array('codigo' => 'INDEF', 'nombre' => 'Tiempo Indefinido', 'descripcion' => 'Contrato permanente'),
                        array('codigo' => 'DEFIN', 'nombre' => 'Tiempo Definido', 'descripcion' => 'Contrato temporal'),
                        array('codigo' => 'SERV', 'nombre' => 'Servicios Profesionales', 'descripcion' => 'Contrato por servicios profesionales'),
                        array('codigo' => 'PRACT', 'nombre' => 'Práctica Profesional', 'descripcion' => 'Contrato de práctica profesional')
                    );
                    
                    foreach ($tipos_estandar as $tipo) {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'codigo' => $tipo['codigo'],
                                'nombre' => $tipo['nombre'],
                                'descripcion' => $tipo['descripcion'],
                                'activo' => 1,
                                'es_estandar' => 1
                            )
                        );
                    }
                    
                    $message .= ' Tipos estándar insertados.';
                }
                
            } catch (Exception $e) {
                $error = 'Error reparando tabla: ' . $e->getMessage();
            }
            break;
            
        case 'verificar_tablas':
            $tablas = array(
                'vs_direcciones',
                'vs_tipos_contrato',
                'vs_vacantes',
                'vs_aplicaciones'
            );
            
            $resultados = array();
            
            foreach ($tablas as $tabla) {
                $table_name = $wpdb->prefix . $tabla;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                
                if ($exists) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                    $resultados[] = "$tabla: ✅ Existe ($count registros)";
                } else {
                    $resultados[] = "$tabla: ❌ No existe";
                }
            }
            
            $message = 'Estado de las tablas: ' . implode(', ', $resultados);
            break;
    }
}

// Obtener información actual de las tablas
$table_info = array();

$tablas = array('vs_direcciones', 'vs_tipos_contrato', 'vs_vacantes', 'vs_aplicaciones');

foreach ($tablas as $tabla) {
    $table_name = $wpdb->prefix . $tabla;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        
        $table_info[$tabla] = array(
            'exists' => true,
            'count' => $count,
            'columns' => $columns
        );
    } else {
        $table_info[$tabla] = array(
            'exists' => false,
            'count' => 0,
            'columns' => array()
        );
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-tools"></span>
        Utilidades del Sistema
    </h1>
    
    <hr class="wp-header-end">
    
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="postbox-container" style="width: 100%;">
        <!-- Reparar Tipos de Contrato -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>🔧 Reparar Tabla Tipos de Contrato</h2>
            </div>
            <div class="inside">
                <p>Si tienes problemas creando tipos de contrato, usa esta herramienta para reparar la tabla.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="reparar_tipos_contrato">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Reparar Tabla Tipos de Contrato
                    </button>
                </form>
                
                <p class="description">
                    Esta acción agregará las columnas faltantes (codigo, es_estandar, fecha_creacion) y 
                    creará los tipos estándar si no existen.
                </p>
            </div>
        </div>
        
        <!-- Verificar Tablas -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>📊 Verificar Estado de Tablas</h2>
            </div>
            <div class="inside">
                <form method="POST">
                    <input type="hidden" name="action" value="verificar_tablas">
                    <button type="submit" class="button">
                        <span class="dashicons dashicons-search"></span>
                        Verificar Tablas
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Estado Actual -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>📋 Estado Actual de las Tablas</h2>
            </div>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Estado</th>
                            <th>Registros</th>
                            <th>Columnas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($table_info as $tabla => $info): ?>
                            <tr>
                                <td><strong><?php echo $tabla; ?></strong></td>
                                <td>
                                    <?php if ($info['exists']): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Existe
                                    <?php else: ?>
                                        <span class="dashicons dashicons-dismiss" style="color: red;"></span> No existe
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $info['count']; ?></td>
                                <td>
                                    <?php if (!empty($info['columns'])): ?>
                                        <details>
                                            <summary><?php echo count($info['columns']); ?> columnas</summary>
                                            <ul style="margin: 10px 0; padding-left: 20px;">
                                                <?php foreach ($info['columns'] as $column): ?>
                                                    <li><?php echo $column->Field . ' (' . $column->Type . ')'; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Información del Sistema -->
        <div class="postbox">
            <div class="postbox-header">
                <h2>ℹ️ Información del Sistema</h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th>Versión del Plugin:</th>
                        <td><?php echo VACANTES_SIMPLE_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Versión de WordPress:</th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th>Versión de PHP:</th>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th>Prefijo de Base de Datos:</th>
                        <td><?php echo $wpdb->prefix; ?></td>
                    </tr>
                    <tr>
                        <th>Charset de Base de Datos:</th>
                        <td><?php echo $wpdb->charset; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.postbox {
    margin-bottom: 20px;
}

.postbox-header h2 {
    font-size: 16px;
    margin: 0;
    padding: 0;
}

details summary {
    cursor: pointer;
    color: #0073aa;
}

details summary:hover {
    color: #005a87;
}
</style>