<?php
/**
 * Template para mostrar Vacantes Laborales públicas
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener direcciones con vacantes activas
$direcciones = $wpdb->get_results("
    SELECT d.*, COUNT(v.id) as total_vacantes
    FROM {$wpdb->prefix}direcciones_minfin d
    INNER JOIN {$wpdb->prefix}vacantes_minfin v ON d.id = v.direccion_id
    WHERE d.activa = 1 AND v.estado = 'Activa' AND v.fecha_limite >= CURDATE()
    GROUP BY d.id
    ORDER BY d.nombre ASC
");
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vacantes Laborales - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e40af;
            margin: 0 0 10px 0;
        }
        
        .header p {
            font-size: 1.1rem;
            color: #64748b;
            margin: 0;
        }
        
        /* Contenedor adaptativo */
        .direcciones-container {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
        }
        
        /* Una sola dirección - tarjeta centrada como la imagen */
        .direcciones-single {
            max-width: 400px;
            width: 100%;
        }
        
        /* Múltiples direcciones - grid */
        .direcciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            width: 100%;
        }
        
        .direccion-card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            cursor: pointer;
        }
        
        .direccion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border-color: #3b82f6;
        }
        
        .direccion-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px auto;
            font-size: 28px;
            color: white;
        }
        
        .direccion-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 12px 0;
            line-height: 1.3;
        }
        
        .direccion-card p {
            color: #6b7280;
            margin: 0 0 24px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .vacantes-count {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 16px;
        }
        
        .card-arrow {
            position: absolute;
            top: 24px;
            right: 24px;
            color: #9ca3af;
            font-size: 1.1rem;
        }
        
        /* Múltiples direcciones - tarjetas más pequeñas */
        .direcciones-grid .direccion-card {
            padding: 24px;
        }
        
        .direcciones-grid .direccion-icon {
            width: 48px;
            height: 48px;
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        .direcciones-grid .direccion-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .direcciones-grid .direccion-card p {
            font-size: 0.875rem;
            margin-bottom: 20px;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 60px;
        }
        
        .info-card {
            text-align: center;
            padding: 20px;
        }
        
        .info-icon {
            width: 56px;
            height: 56px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            font-size: 24px;
            color: #3b82f6;
        }
        
        .info-card h4 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 8px 0;
        }
        
        .info-card p {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .direcciones-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .direccion-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Vacantes Laborales</h1>
            <p>Encuentra oportunidades de empleo en las diferentes direcciones del Ministerio de Finanzas Públicas</p>
        </div>
        
        <div class="direcciones-container">
            <?php if (count($direcciones) === 1): ?>
                <!-- Una sola dirección - tarjeta centrada como la imagen -->
                <div class="direcciones-single">
                    <?php $direccion = $direcciones[0]; ?>
                    <div class="direccion-card" onclick="window.location.href='<?php echo home_url('/vacantes-direccion/?direccion_id=' . $direccion->id); ?>'">
                        <div class="card-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                            </svg>
                        </div>
                        
                        <div class="direccion-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/>
                            </svg>
                        </div>
                        
                        <h3><?php echo esc_html($direccion->nombre); ?></h3>
                        <p><?php echo esc_html($direccion->descripcion); ?></p>
                        
                        <div class="vacantes-count">
                            <?php echo $direccion->total_vacantes; ?> vacante<?php echo $direccion->total_vacantes != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Múltiples direcciones - grid -->
                <div class="direcciones-grid">
                    <?php foreach ($direcciones as $direccion): ?>
                        <div class="direccion-card" onclick="window.location.href='<?php echo home_url('/vacantes-direccion/?direccion_id=' . $direccion->id); ?>'">
                            <div class="card-arrow">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                                </svg>
                            </div>
                            
                            <div class="direccion-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/>
                                </svg>
                            </div>
                            
                            <h3><?php echo esc_html($direccion->nombre); ?></h3>
                            <p><?php echo esc_html($direccion->descripcion); ?></p>
                            
                            <div class="vacantes-count">
                                <?php echo $direccion->total_vacantes; ?> vacante<?php echo $direccion->total_vacantes != 1 ? 's' : ''; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-section">
            <div class="info-card">
                <div class="info-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                </div>
                <h4>Explora Oportunidades</h4>
                <p>Navega por las diferentes direcciones y encuentra la vacante ideal para ti.</p>
            </div>
            
            <div class="info-card">
                <div class="info-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                </div>
                <h4>Aplica Fácilmente</h4>
                <p>Completa el formulario de aplicación con tu información y documentos.</p>
            </div>
            
            <div class="info-card">
                <div class="info-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11,14H9A5,5 0 0,1 4,9A5,5 0 0,1 9,4H11V6H9A3,3 0 0,0 6,9A3,3 0 0,0 9,12H11V14M8,10V8H16V10H8M13,4H15A5,5 0 0,1 20,9A5,5 0 0,1 15,14H13V12H15A3,3 0 0,0 18,9A3,3 0 0,0 15,6H13V4Z"/>
                    </svg>
                </div>
                <h4>Únete al Equipo</h4>
                <p>Forma parte del Ministerio de Finanzas y contribuye al desarrollo del país.</p>
            </div>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>