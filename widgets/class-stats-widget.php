<?php
/**
 * Widget de Estadísticas de Vacantes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Stats_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vacantes_stats_widget',
            'Estadísticas de Vacantes',
            array(
                'description' => 'Muestra estadísticas generales del sistema de vacantes'
            )
        );
    }

    /**
     * Mostrar el widget en el frontend
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Estadísticas de Empleo';
        $mostrar_total = !empty($instance['mostrar_total']) ? true : false;
        $mostrar_activas = !empty($instance['mostrar_activas']) ? true : false;
        $mostrar_direcciones = !empty($instance['mostrar_direcciones']) ? true : false;
        $mostrar_aplicaciones = !empty($instance['mostrar_aplicaciones']) ? true : false;

        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Obtener estadísticas
        $vacante_model = new Vacante_Model();
        $aplicacion_model = new Aplicacion_Model();
        
        $stats_vacantes = $vacante_model->get_stats();
        $stats_aplicaciones = $aplicacion_model->get_stats();

        echo '<div class="vacantes-stats-widget">';
        
        if ($mostrar_total) {
            echo '<div class="stat-item stat-total">';
            echo '<div class="stat-number">' . $stats_vacantes['total'] . '</div>';
            echo '<div class="stat-label">Total de Vacantes</div>';
            echo '</div>';
        }
        
        if ($mostrar_activas) {
            echo '<div class="stat-item stat-activas">';
            echo '<div class="stat-number">' . $stats_vacantes['vigentes'] . '</div>';
            echo '<div class="stat-label">Vacantes Vigentes</div>';
            echo '</div>';
        }
        
        if ($mostrar_aplicaciones) {
            echo '<div class="stat-item stat-aplicaciones">';
            echo '<div class="stat-number">' . $stats_aplicaciones['total'] . '</div>';
            echo '<div class="stat-label">Aplicaciones Recibidas</div>';
            echo '</div>';
        }
        
        if ($mostrar_direcciones && !empty($stats_vacantes['por_direccion'])) {
            echo '<div class="stat-direcciones">';
            echo '<h4>Vacantes por Dirección</h4>';
            echo '<ul class="direcciones-list">';
            
            foreach ($stats_vacantes['por_direccion'] as $direccion) {
                if ($direccion->total > 0) {
                    echo '<li>';
                    echo '<span class="direccion-nombre">' . esc_html($direccion->nombre) . '</span>';
                    echo '<span class="direccion-count">' . $direccion->total . '</span>';
                    echo '</li>';
                }
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>';

        echo $args['after_widget'];
        
        // Agregar estilos inline
        $this->add_widget_styles();
    }

    /**
     * Formulario de configuración del widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Estadísticas de Empleo';
        $mostrar_total = !empty($instance['mostrar_total']) ? true : false;
        $mostrar_activas = !empty($instance['mostrar_activas']) ? true : false;
        $mostrar_direcciones = !empty($instance['mostrar_direcciones']) ? true : false;
        $mostrar_aplicaciones = !empty($instance['mostrar_aplicaciones']) ? true : false;
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Título:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p><strong>Mostrar:</strong></p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_total); ?> 
                   id="<?php echo $this->get_field_id('mostrar_total'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_total'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_total'); ?>">Total de vacantes</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_activas); ?> 
                   id="<?php echo $this->get_field_id('mostrar_activas'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_activas'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_activas'); ?>">Vacantes vigentes</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_aplicaciones); ?> 
                   id="<?php echo $this->get_field_id('mostrar_aplicaciones'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_aplicaciones'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_aplicaciones'); ?>">Total de aplicaciones</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_direcciones); ?> 
                   id="<?php echo $this->get_field_id('mostrar_direcciones'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_direcciones'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_direcciones'); ?>">Vacantes por dirección</label>
        </p>
        
        <?php
    }

    /**
     * Actualizar configuración del widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['mostrar_total'] = !empty($new_instance['mostrar_total']) ? 1 : 0;
        $instance['mostrar_activas'] = !empty($new_instance['mostrar_activas']) ? 1 : 0;
        $instance['mostrar_direcciones'] = !empty($new_instance['mostrar_direcciones']) ? 1 : 0;
        $instance['mostrar_aplicaciones'] = !empty($new_instance['mostrar_aplicaciones']) ? 1 : 0;
        
        return $instance;
    }

    /**
     * Agregar estilos CSS para el widget
     */
    private function add_widget_styles() {
        static $styles_added = false;
        
        if (!$styles_added) {
            echo '<style>
                .vacantes-stats-widget {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                
                .stat-item {
                    background: #f8f9fa;
                    padding: 15px;
                    margin-bottom: 10px;
                    border-radius: 6px;
                    text-align: center;
                    border-left: 4px solid #007cba;
                }
                
                .stat-number {
                    font-size: 2em;
                    font-weight: bold;
                    color: #2c3e50;
                    line-height: 1;
                }
                
                .stat-label {
                    font-size: 0.9em;
                    color: #6c757d;
                    margin-top: 5px;
                }
                
                .stat-total { border-left-color: #007cba; }
                .stat-activas { border-left-color: #28a745; }
                .stat-aplicaciones { border-left-color: #ffc107; }
                
                .stat-direcciones {
                    margin-top: 15px;
                }
                
                .stat-direcciones h4 {
                    margin: 0 0 10px 0;
                    font-size: 1em;
                    color: #495057;
                    border-bottom: 1px solid #e9ecef;
                    padding-bottom: 5px;
                }
                
                .direcciones-list {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }
                
                .direcciones-list li {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 5px 0;
                    border-bottom: 1px solid #f1f3f4;
                }
                
                .direcciones-list li:last-child {
                    border-bottom: none;
                }
                
                .direccion-nombre {
                    font-size: 0.9em;
                    color: #495057;
                }
                
                .direccion-count {
                    background: #007cba;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 0.8em;
                    font-weight: bold;
                }
            </style>';
            
            $styles_added = true;
        }
    }
}

/**
 * Registrar el widget
 */
function register_vacantes_stats_widget() {
    register_widget('Vacantes_Stats_Widget');
}
add_action('widgets_init', 'register_vacantes_stats_widget');