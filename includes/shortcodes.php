<?php
/**
 * Shortcodes para mostrar vacantes en el frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Shortcodes {
    
    /**
     * Shortcode para mostrar página principal de direcciones
     * Uso: [vacantes_laborales]
     */
    public static function vacantes_laborales($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/public-vacantes-laborales.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode para mostrar vacantes de una dirección específica
     * Uso: [vacantes_direccion]
     */
    public static function vacantes_direccion($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/public-lista-vacantes-direccion-simple.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode para lista de vacantes (legacy)
     * Uso: [vacantes_lista limite="10" direccion="" tipo_contrato=""]
     */
    public static function lista_vacantes($atts) {
        $atts = shortcode_atts(array(
            'limite' => 10,
            'direccion' => '',
            'tipo_contrato' => '',
            'mostrar_filtros' => 'true'
        ), $atts);
        
        global $wpdb;
        
        // Construir consulta con nombres de tabla correctos
        $sql = "SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre 
                FROM {$wpdb->prefix}vs_vacantes v
                LEFT JOIN {$wpdb->prefix}vs_direcciones d ON v.direccion_id = d.id
                LEFT JOIN {$wpdb->prefix}vs_tipos_contrato tc ON v.tipo_contrato_id = tc.id
                WHERE v.estado = 'Activa' AND v.fecha_limite >= CURDATE()";
        
        if (!empty($atts['direccion'])) {
            $sql .= $wpdb->prepare(" AND d.id = %d", intval($atts['direccion']));
        }
        
        if (!empty($atts['tipo_contrato'])) {
            $sql .= $wpdb->prepare(" AND tc.id = %d", intval($atts['tipo_contrato']));
        }
        
        $sql .= " ORDER BY v.fecha_creacion DESC LIMIT " . intval($atts['limite']);
        
        $vacantes = $wpdb->get_results($sql);
        
        ob_start();
        ?>
        <div class="vacantes-frontend-container">
            <?php if ($atts['mostrar_filtros'] === 'true'): ?>
            <div class="vacantes-filtros">
                <form class="vacantes-filtros-form" method="get">
                    <div class="filtro-grupo">
                        <label for="buscar-termino">Buscar:</label>
                        <input type="text" id="buscar-termino" name="buscar" placeholder="Título, descripción...">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label for="filtro-direccion">Dirección:</label>
                        <select id="filtro-direccion" name="direccion">
                            <option value="">Todas las direcciones</option>
                            <?php
                            $direcciones = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vacantes_direcciones WHERE activa = 1");
                            foreach ($direcciones as $direccion) {
                                echo '<option value="' . $direccion->correlativo . '">' . esc_html($direccion->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-buscar">Buscar</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="vacantes-lista">
                <?php if (empty($vacantes)): ?>
                    <div class="no-vacantes">
                        <p>No hay vacantes disponibles en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vacantes as $vacante): ?>
                        <div class="vacante-item">
                            <div class="vacante-header">
                                <h3><?php echo esc_html($vacante->titulo); ?></h3>
                                <span class="vacante-codigo"><?php echo esc_html($vacante->codigo); ?></span>
                            </div>
                            
                            <div class="vacante-meta">
                                <span class="direccion"><?php echo esc_html($vacante->direccion_nombre); ?></span>
                                <span class="tipo-contrato"><?php echo esc_html($vacante->tipo_contrato_nombre); ?></span>
                                <?php if ($vacante->salario): ?>
                                <span class="salario">Q. <?php echo number_format($vacante->salario, 2); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vacante-descripcion">
                                <?php echo wp_trim_words($vacante->descripcion, 30, '...'); ?>
                            </div>
                            
                            <div class="vacante-fechas">
                                <small>Cierra: <?php echo date('d/m/Y', strtotime($vacante->fecha_fin)); ?></small>
                            </div>
                            
                            <div class="vacante-acciones">
                                <a href="#" class="btn btn-primary ver-detalle" data-vacante-id="<?php echo $vacante->id; ?>">
                                    Ver Detalles
                                </a>
                                <a href="#" class="btn btn-success aplicar-vacante" data-vacante-id="<?php echo $vacante->id; ?>">
                                    Aplicar Ahora
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .vacantes-frontend-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .vacantes-filtros { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .vacantes-filtros-form { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        .filtro-grupo { display: flex; flex-direction: column; min-width: 200px; }
        .filtro-grupo label { font-weight: 600; margin-bottom: 5px; }
        .filtro-grupo input, .filtro-grupo select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-buscar { background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .vacante-item { background: white; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .vacante-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .vacante-header h3 { margin: 0; color: #2c3e50; }
        .vacante-codigo { background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
        .vacante-meta { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .vacante-meta span { font-size: 0.9em; color: #6c757d; }
        .vacante-acciones { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #007cba; color: white; }
        .btn-success { background: #28a745; color: white; }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Registrar shortcodes
add_shortcode('vacantes_laborales', array('Vacantes_Shortcodes', 'vacantes_laborales'));
add_shortcode('vacantes_direccion', array('Vacantes_Shortcodes', 'vacantes_direccion'));
add_shortcode('vacantes_lista', array('Vacantes_Shortcodes', 'lista_vacantes'));