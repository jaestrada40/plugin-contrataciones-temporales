<?php
/**
 * Servicio de Email para Vacantes MINFIN
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vacantes_Email_Service {
    
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->from_email = get_option('vacantes_minfin_email_from', get_option('admin_email'));
        $this->from_name = get_option('vacantes_minfin_email_from_name', get_bloginfo('name'));
    }
    
    /**
     * Enviar email de confirmación de aplicación
     */
    public function send_aplicacion_confirmacion($aplicacion_data, $vacante_data) {
        if (!get_option('vacantes_minfin_enable_notifications', 1)) {
            return false;
        }
        
        // No enviar si no hay email
        if (empty($aplicacion_data['email'])) {
            return false;
        }
        
        $to = $aplicacion_data['email'];
        $subject = sprintf(
            __('Confirmación de aplicación - %s', 'vacantes-minfin'),
            $vacante_data->titulo
        );
        
        $message = $this->get_aplicacion_confirmacion_template($aplicacion_data, $vacante_data);
        
        return $this->send_email($to, $subject, $message);
    }
    
    /**
     * Enviar notificación de nueva aplicación a administradores
     */
    public function send_nueva_aplicacion_admin($aplicacion_data, $vacante_data) {
        if (!get_option('vacantes_minfin_enable_notifications', 1)) {
            return false;
        }
        
        $admin_emails = $this->get_admin_emails();
        if (empty($admin_emails)) {
            return false;
        }
        
        $subject = sprintf(
            __('Nueva aplicación recibida - %s', 'vacantes-minfin'),
            $vacante_data->titulo
        );
        
        $message = $this->get_nueva_aplicacion_admin_template($aplicacion_data, $vacante_data);
        
        $sent = true;
        foreach ($admin_emails as $email) {
            if (!$this->send_email($email, $subject, $message)) {
                $sent = false;
            }
        }
        
        return $sent;
    }
    
    /**
     * Enviar notificación de cambio de estado
     */
    public function send_cambio_estado($aplicacion_data, $estado_anterior, $estado_nuevo) {
        if (!get_option('vacantes_minfin_enable_notifications', 1)) {
            return false;
        }
        
        // Solo enviar para estados finales
        if (!in_array($estado_nuevo, array('Aceptado', 'Rechazado'))) {
            return false;
        }
        
        $to = $aplicacion_data->email;
        $subject = sprintf(
            __('Actualización de su aplicación - %s', 'vacantes-minfin'),
            $aplicacion_data->vacante_titulo
        );
        
        $message = $this->get_cambio_estado_template($aplicacion_data, $estado_anterior, $estado_nuevo);
        
        return $this->send_email($to, $subject, $message);
    }
    
    /**
     * Enviar recordatorio de vacantes próximas a vencer
     */
    public function send_recordatorio_vencimiento($vacantes_proximas) {
        if (empty($vacantes_proximas)) {
            return false;
        }
        
        $admin_emails = $this->get_admin_emails();
        if (empty($admin_emails)) {
            return false;
        }
        
        $subject = __('Recordatorio: Vacantes próximas a vencer', 'vacantes-minfin');
        $message = $this->get_recordatorio_vencimiento_template($vacantes_proximas);
        
        $sent = true;
        foreach ($admin_emails as $email) {
            if (!$this->send_email($email, $subject, $message)) {
                $sent = false;
            }
        }
        
        return $sent;
    }
    
    /**
     * Enviar email genérico
     */
    private function send_email($to, $subject, $message, $headers = array()) {
        // Configurar headers por defecto
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $this->from_name, $this->from_email)
        );
        
        $headers = array_merge($default_headers, $headers);
        
        // Aplicar filtros para personalización
        $to = apply_filters('vacantes_email_to', $to);
        $subject = apply_filters('vacantes_email_subject', $subject);
        $message = apply_filters('vacantes_email_message', $message);
        $headers = apply_filters('vacantes_email_headers', $headers);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Template de confirmación de aplicación
     */
    private function get_aplicacion_confirmacion_template($aplicacion_data, $vacante_data) {
        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin: 0;">%s</h2>
                <p style="color: #7f8c8d; margin: 5px 0 0 0;">Ministerio de Finanzas Públicas</p>
            </div>
            
            <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px;">
                <h3 style="color: #27ae60; margin-top: 0;">¡Aplicación Recibida Exitosamente!</h3>
                
                <p>Estimado/a <strong>%s</strong>,</p>
                
                <p>Hemos recibido su aplicación para la siguiente vacante:</p>
                
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50;">%s</h4>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Código:</strong> %s</p>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Dirección:</strong> %s</p>
                </div>
                
                <h4>Detalles de su aplicación:</h4>
                <ul style="color: #555;">
                    <li><strong>Fecha de aplicación:</strong> %s</li>
                    <li><strong>Email:</strong> %s</li>
                    <li><strong>Teléfono:</strong> %s</li>
                    <li><strong>DPI:</strong> %s</li>
                </ul>
                
                <div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p style="margin: 0; color: #27ae60;"><strong>¿Qué sigue?</strong></p>
                    <p style="margin: 5px 0 0 0; color: #555;">
                        Su aplicación será revisada por nuestro equipo de Recursos Humanos. 
                        Le notificaremos por email sobre cualquier actualización en el proceso.
                    </p>
                </div>
                
                <p style="color: #7f8c8d; font-size: 14px; margin-top: 30px;">
                    Este es un email automático, por favor no responda a este mensaje.
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 12px;">
                <p>Ministerio de Finanzas Públicas - Guatemala</p>
                <p>Sistema de Gestión de Vacantes</p>
            </div>
        </div>';
        
        return sprintf(
            $template,
            __('Confirmación de Aplicación', 'vacantes-minfin'),
            esc_html($aplicacion_data['nombre_completo']),
            esc_html($vacante_data->titulo),
            esc_html($vacante_data->codigo),
            esc_html($vacante_data->direccion_nombre),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            esc_html($aplicacion_data['email']),
            esc_html($aplicacion_data['telefono'] ?? 'No proporcionado'),
            esc_html($aplicacion_data['dpi'])
        );
    }
    
    /**
     * Template de nueva aplicación para administradores
     */
    private function get_nueva_aplicacion_admin_template($aplicacion_data, $vacante_data) {
        $admin_url = admin_url('admin.php?page=aplicaciones-list&vacante_id=' . $vacante_data->id);
        
        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #3498db; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin: 0;">Nueva Aplicación Recibida</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Sistema de Vacantes - MINFIN</p>
            </div>
            
            <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px;">
                <h3 style="color: #2c3e50; margin-top: 0;">Detalles de la Aplicación</h3>
                
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50;">%s</h4>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Código:</strong> %s</p>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Dirección:</strong> %s</p>
                </div>
                
                <h4>Datos del Candidato:</h4>
                <ul style="color: #555;">
                    <li><strong>Nombre:</strong> %s</li>
                    <li><strong>Email:</strong> %s</li>
                    <li><strong>Teléfono:</strong> %s</li>
                    <li><strong>DPI:</strong> %s</li>
                    <li><strong>Fecha de aplicación:</strong> %s</li>
                </ul>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="%s" style="background-color: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        Ver Aplicación en el Sistema
                    </a>
                </div>
                
                <p style="color: #7f8c8d; font-size: 14px;">
                    Puede revisar y gestionar esta aplicación desde el panel administrativo del sistema.
                </p>
            </div>
        </div>';
        
        return sprintf(
            $template,
            esc_html($vacante_data->titulo),
            esc_html($vacante_data->codigo),
            esc_html($vacante_data->direccion_nombre),
            esc_html($aplicacion_data['nombre_completo']),
            esc_html($aplicacion_data['email']),
            esc_html($aplicacion_data['telefono'] ?? 'No proporcionado'),
            esc_html($aplicacion_data['dpi']),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            esc_url($admin_url)
        );
    }
    
    /**
     * Template de cambio de estado
     */
    private function get_cambio_estado_template($aplicacion_data, $estado_anterior, $estado_nuevo) {
        $color = $estado_nuevo === 'Aceptado' ? '#27ae60' : '#e74c3c';
        $mensaje = $estado_nuevo === 'Aceptado' 
            ? '¡Felicitaciones! Su aplicación ha sido aceptada.' 
            : 'Lamentamos informarle que su aplicación no ha sido seleccionada en esta ocasión.';
        
        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: %s; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin: 0;">Actualización de Aplicación</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Ministerio de Finanzas Públicas</p>
            </div>
            
            <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px;">
                <p>Estimado/a <strong>%s</strong>,</p>
                
                <p>%s</p>
                
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50;">%s</h4>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Estado anterior:</strong> %s</p>
                    <p style="margin: 0; color: #7f8c8d;"><strong>Estado actual:</strong> <span style="color: %s; font-weight: bold;">%s</span></p>
                </div>
                
                %s
                
                <p style="color: #7f8c8d; font-size: 14px; margin-top: 30px;">
                    Gracias por su interés en formar parte del Ministerio de Finanzas Públicas.
                </p>
            </div>
        </div>';
        
        $comentarios_html = '';
        if (!empty($aplicacion_data->comentarios)) {
            $comentarios_html = sprintf(
                '<div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404;"><strong>Comentarios:</strong></p>
                    <p style="margin: 5px 0 0 0; color: #856404;">%s</p>
                </div>',
                esc_html($aplicacion_data->comentarios)
            );
        }
        
        return sprintf(
            $template,
            $color,
            esc_html($aplicacion_data->nombre_completo),
            $mensaje,
            esc_html($aplicacion_data->vacante_titulo),
            esc_html($estado_anterior),
            $color,
            esc_html($estado_nuevo),
            $comentarios_html
        );
    }
    
    /**
     * Template de recordatorio de vencimiento
     */
    private function get_recordatorio_vencimiento_template($vacantes_proximas) {
        $template = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f39c12; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="margin: 0;">Recordatorio: Vacantes Próximas a Vencer</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Sistema de Vacantes - MINFIN</p>
            </div>
            
            <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px;">
                <p>Las siguientes vacantes están próximas a vencer:</p>
                
                %s
                
                <p style="color: #7f8c8d; font-size: 14px; margin-top: 30px;">
                    Revise estas vacantes y considere extender las fechas si es necesario.
                </p>
            </div>
        </div>';
        
        $vacantes_html = '';
        foreach ($vacantes_proximas as $vacante) {
            $dias_restantes = ceil((strtotime($vacante->fecha_fin) - time()) / (60 * 60 * 24));
            $vacantes_html .= sprintf(
                '<div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">
                    <h4 style="margin: 0 0 5px 0; color: #856404;">%s</h4>
                    <p style="margin: 0; color: #856404;"><strong>Código:</strong> %s</p>
                    <p style="margin: 0; color: #856404;"><strong>Vence:</strong> %s (%d días restantes)</p>
                </div>',
                esc_html($vacante->titulo),
                esc_html($vacante->codigo),
                date_i18n(get_option('date_format'), strtotime($vacante->fecha_fin)),
                $dias_restantes
            );
        }
        
        return sprintf($template, $vacantes_html);
    }
    
    /**
     * Obtener emails de administradores
     */
    private function get_admin_emails() {
        $emails = array();
        
        // Email configurado en opciones del plugin
        $notification_email = get_option('vacantes_email_admin');
        if (!empty($notification_email) && is_email($notification_email)) {
            $emails[] = $notification_email;
        }
        
        // Emails de usuarios administradores
        $users = get_users(array(
            'role' => 'administrator'
        ));
        
        foreach ($users as $user) {
            if (is_email($user->user_email)) {
                $emails[] = $user->user_email;
            }
        }
        
        // Email del administrador por defecto si no hay otros
        if (empty($emails)) {
            $admin_email = get_option('admin_email');
            if (is_email($admin_email)) {
                $emails[] = $admin_email;
            }
        }
        
        return array_unique($emails);
    }
}