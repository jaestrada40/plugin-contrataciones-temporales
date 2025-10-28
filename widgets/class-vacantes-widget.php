<?php
/**
 * Widget de Vacantes Destacadas
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vacantes_widget',
            'Vacantes Destacadas',
            array(
                'description' => 'Muestra las vacantes más recientes o destacadas'
            )
        );
    }

    /**
     * Mostrar el widget en el frontend
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Vacantes Disponibles';
        $numero = !empty($instance['numero']) ? intval($instance['numero']) : 5;
        $mostrar_direccion = !empty($instance['mostrar_direccion']) ? true : false;
        $mostrar_salario = !empty($instance['mostrar_salario']) ? true : false;
        $solo_activas = !empty($instance['solo_activas']) ? true : false;

        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Obtener vacantes
        $vacante_model = new Vacante_Model();
        $filtros = array(
            'limite' => $numero,
            'activas' => $solo_activas ? 'si' : 'no'
        );
        
        $vacantes = $vacante_model->get_vacantes_publicas($filtros);

        if (!empty($vacantes)) {
            echo '<div class="vacantes-widget-list">';
            
            foreach ($vacantes as $vacante) {
                echo '<div class="vacante-widget-item">';
                
                echo '<h4 class="vacante-widget-titulo">';
                echo '<a href="#" class="ver-vacante-detalle" data-vacante-id="' . $vacante->id . '">';
                echo esc_html($vacante->titulo);
                echo '</a>';
                echo '</h4>';
                
                if ($mostrar_direccion && $vacante->direccion_nombre) {
                    echo '<div class="vacante-widget-direccion">';
                    echo '<small><i class="icon-building"></i> ' . esc_html($vacante->direccion_nombre) . '</small>';
                    echo '</div>';
                }
                
                if ($mostrar_salario && $vacante->salario) {
                    echo '<div class="vacante-widget-salario">';
                    echo '<small><i class="icon-money"></i> Q. ' . number_format($vacante->salario, 2) . '</small>';
                    echo '</div>';
                }
                
                echo '<div class="vacante-widget-fecha">';
                echo '<small>Cierra: ' . date('d/m/Y', strtotime($vacante->fecha_fin)) . '</small>';
                echo '</div>';
                
                echo '</div>';
            }
            
            echo '</div>';
            
            // Enlace para ver todas
            echo '<div class="vacantes-widget-footer">';
            echo '<a href="#" class="btn-ver-todas-vacantes">Ver todas las vacantes →</a>';
            echo '</div>';
            
        } else {
            echo '<p class="no-vacantes-widget">No hay vacantes disponibles en este momento.</p>';
        }

        echo $args['after_widget'];
    }

    /**
     * Formulario de configuración del widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Vacantes Disponibles';
        $numero = !empty($instance['numero']) ? $instance['numero'] : 5;
        $mostrar_direccion = !empty($instance['mostrar_direccion']) ? true : false;
        $mostrar_salario = !empty($instance['mostrar_salario']) ? true : false;
        $solo_activas = !empty($instance['solo_activas']) ? true : false;
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Título:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('numero'); ?>">Número de vacantes:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('numero'); ?>" 
                   name="<?php echo $this->get_field_name('numero'); ?>" type="number" 
                   value="<?php echo esc_attr($numero); ?>" min="1" max="20">
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_direccion); ?> 
                   id="<?php echo $this->get_field_id('mostrar_direccion'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_direccion'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_direccion'); ?>">Mostrar dirección</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($mostrar_salario); ?> 
                   id="<?php echo $this->get_field_id('mostrar_salario'); ?>" 
                   name="<?php echo $this->get_field_name('mostrar_salario'); ?>">
            <label for="<?php echo $this->get_field_id('mostrar_salario'); ?>">Mostrar salario</label>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($solo_activas); ?> 
                   id="<?php echo $this->get_field_id('solo_activas'); ?>" 
                   name="<?php echo $this->get_field_name('solo_activas'); ?>">
            <label for="<?php echo $this->get_field_id('solo_activas'); ?>">Solo vacantes activas</label>
        </p>
        
        <?php
    }

    /**
     * Actualizar configuración del widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['numero'] = (!empty($new_instance['numero'])) ? intval($new_instance['numero']) : 5;
        $instance['mostrar_direccion'] = !empty($new_instance['mostrar_direccion']) ? 1 : 0;
        $instance['mostrar_salario'] = !empty($new_instance['mostrar_salario']) ? 1 : 0;
        $instance['solo_activas'] = !empty($new_instance['solo_activas']) ? 1 : 0;
        
        return $instance;
    }
}

/**
 * Registrar el widget
 */
function register_vacantes_widget() {
    register_widget('Vacantes_Widget');
}
add_action('widgets_init', 'register_vacantes_widget');