<?php
/**
 * Template para mostrar vacantes de una dirección específica
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$direccion_id = intval($_GET['direccion_id'] ?? 0);

if (!$direccion_id) {
    wp_redirect(home_url('/vacantes-laborales/'));
    exit;
}

// Obtener información de la dirección
$direccion = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}vs_direcciones WHERE id = %d AND activa = 1",
    $direccion_id
));

if (!$direccion) {
    wp_redirect(home_url('/vacantes-laborales/'));
    exit;
}

// Obtener vacantes activas de esta dirección
$vacantes = $wpdb->get_results($wpdb->prepare("
    SELECT v.*, tc.nombre as tipo_contrato_nombre
    FROM {$wpdb->prefix}vs_vacantes v
    LEFT JOIN {$wpdb->prefix}vs_tipos_contrato tc ON v.tipo_contrato_id = tc.id
    WHERE v.direccion_id = %d AND v.estado = 'Activa' AND v.fecha_limite >= CURDATE()
    ORDER BY v.fecha_creacion DESC
", $direccion_id));

// Obtener total de aplicaciones por vacante
$aplicaciones_count = array();
if (!empty($vacantes)) {
    $vacante_ids = array_column($vacantes, 'id');
    $aplicaciones_data = $wpdb->get_results("
        SELECT vacante_id, COUNT(*) as total
        FROM {$wpdb->prefix}vs_aplicaciones 
        WHERE vacante_id IN (" . implode(',', array_map('intval', $vacante_ids)) . ")
        GROUP BY vacante_id
    ");
    
    foreach ($aplicaciones_data as $app) {
        $aplicaciones_count[$app->vacante_id] = $app->total;
    }
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vacantes - <?php echo esc_html($direccion->nombre); ?> - <?php bloginfo('name'); ?></title>
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
        
        .breadcrumb {
            margin-bottom: 30px;
            color: #64748b;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .direccion-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 5px 0;
        }
        
        .header p {
            color: #64748b;
            margin: 0;
            font-size: 14px;
        }
        
        .vacantes-lista {
            display: grid;
            gap: 20px;
        }
        
        .vacante-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
        }
        
        .vacante-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }
        
        .vacante-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .vacante-badges {
            display: flex;
            gap: 8px;
        }
        
        .vacante-codigo {
            background: #64748b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .vacante-estado {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .aplicaciones-count {
            color: #64748b;
            font-size: 12px;
        }
        
        .vacante-titulo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        
        .vacante-descripcion {
            color: #64748b;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }
        
        .vacante-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 14px;
        }
        
        .meta-item svg {
            color: #3b82f6;
        }
        
        .vacante-acciones {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .btn-outline {
            background: white;
            color: #3b82f6;
            border-color: #3b82f6;
        }
        
        .btn-outline:hover {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            color: white;
        }
        
        .intro-text {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .intro-text p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }
        
        .vacantes-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .vacantes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .vacantes-table thead {
            background: #f8fafc;
        }
        
        .vacantes-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .vacantes-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        
        .vacantes-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .numero-cell {
            width: 80px;
            text-align: center;
        }
        
        .numero-badge {
            background: #e5e7eb;
            color: #374151;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .puesto-cell {
            width: 25%;
        }
        
        .puesto-cell strong {
            color: #1f2937;
            font-weight: 600;
        }
        
        .requisitos-cell {
            width: 35%;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .dependencia-cell {
            width: 20%;
            color: #6b7280;
        }
        
        .link-cell {
            width: 80px;
            text-align: center;
        }
        
        .download-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .download-link:hover {
            background: #2563eb;
            color: white;
        }
        
        .no-vacantes {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .no-vacantes-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .no-vacantes h3 {
            color: #64748b;
            margin: 0 0 10px 0;
        }
        
        .no-vacantes p {
            color: #94a3b8;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .header {
                padding: 24px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .vacante-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .vacante-titulo {
                margin: 0;
            }
            
            .vacante-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .vacante-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo home_url('/vacantes-laborales/'); ?>">Inicio</a> / 
            <span><?php echo esc_html($direccion->nombre); ?></span>
        </div>
        
        <div class="header">
            <div class="direccion-icon">
                <?php
                // Icono específico por dirección
                $nombre_lower = strtolower($direccion->nombre);
                if (strpos($nombre_lower, 'recursos humanos') !== false) {
                    echo '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
                } elseif (strpos($nombre_lower, 'tecnologia') !== false) {
                    echo '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M20,18C20.5,18 21,17.5 21,17V7C21,6.5 20.5,6 20,6H4C3.5,6 3,6.5 3,7V17C3,17.5 3.5,18 4,18H20M20,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6A2,2 0 0,0 20,4Z"/></svg>';
                } elseif (strpos($nombre_lower, 'administrativa') !== false) {
                    echo '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/></svg>';
                } else {
                    echo '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/></svg>';
                }
                ?>
            </div>
            <h1><?php echo esc_html($direccion->nombre); ?></h1>
            <p><?php echo esc_html($direccion->descripcion); ?></p>
        </div>
        
        <!-- Texto Descriptivo -->
        <div class="intro-text">
            <p>En esta sección se presentan las propuestas ciudadanas que fueron evaluadas como viables por la Secretaría de Planificación y Programación de la Presidencia (SEGEPLAN), conforme a los lineamientos establecidos.</p>
        </div>

        <?php if (empty($vacantes)): ?>
            <div class="no-vacantes">
                <div class="no-vacantes-icon">📋</div>
                <h3>No hay vacantes disponibles</h3>
                <p>Actualmente no hay vacantes activas en esta dirección. Te invitamos a revisar otras direcciones o volver más tarde.</p>
            </div>
        <?php else: ?>
            <!-- Tabla de Vacantes con Diseño Limpio -->
            <div class="vacantes-table-container">
                <table class="vacantes-table">
                    <thead>
                        <tr>
                            <th class="numero-cell">No.</th>
                            <th>No. de vacante</th>
                            <th class="puesto-cell">Puesto o servicio</th>
                            <th class="requisitos-cell">Requisitos</th>
                            <th class="dependencia-cell">Dependencia</th>
                            <th class="link-cell">Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $contador = 1;
                        foreach ($vacantes as $vacante): 
                        ?>
                        <tr>
                            <td class="numero-cell">
                                <span class="numero-badge"><?php echo str_pad($contador, 2, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td>
                                <?php echo esc_html($vacante->codigo ?: 'VAC-' . str_pad($vacante->id, 3, '0', STR_PAD_LEFT)); ?>
                            </td>
                            <td class="puesto-cell">
                                <strong><?php echo esc_html($vacante->titulo); ?></strong>
                            </td>
                            <td class="requisitos-cell">
                                <?php 
                                if ($vacante->requisitos) {
                                    echo esc_html(wp_trim_words($vacante->requisitos, 12));
                                } else {
                                    echo '<em>No especificados</em>';
                                }
                                ?>
                            </td>
                            <td class="dependencia-cell">
                                <?php echo esc_html($direccion->nombre); ?>
                            </td>
                            <td class="link-cell">
                                <a href="<?php echo home_url('/vacante-detalle/?id=' . $vacante->id); ?>" 
                                   class="download-link" 
                                   title="Ver detalles de la vacante">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php 
                        $contador++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>