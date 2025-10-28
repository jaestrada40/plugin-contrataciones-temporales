<?php
/**
 * Template para mostrar vacantes de una dirección específica - Versión Simple
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$direccion_id = intval($_GET['direccion_id'] ?? 0);

if (!$direccion_id) {
    echo '<p>Error: No se especificó una dirección válida.</p>';
    return;
}

// Obtener información de la dirección
$direccion = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}direcciones_minfin WHERE id = %d AND activa = 1",
    $direccion_id
));

if (!$direccion) {
    echo '<p>Error: Dirección no encontrada.</p>';
    return;
}

// Obtener vacantes activas de esta dirección
$vacantes = $wpdb->get_results($wpdb->prepare("
    SELECT v.*, tc.nombre as tipo_contrato_nombre
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
    WHERE v.direccion_id = %d AND v.estado = 'Activa' AND v.fecha_limite >= CURDATE()
    ORDER BY v.fecha_creacion DESC
", $direccion_id));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

/* LISTA DE VACANTES LIMPIA SIN BORDES */
.vacantes-lista-limpia {
    width: 100%;
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.vacante-header-row {
    display: grid;
    grid-template-columns: 80px 1fr 150px 200px 120px;
    gap: 20px;
    padding: 15px 0;
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
    border-radius: 8px 8px 0 0;
}

.vacante-row {
    display: grid;
    grid-template-columns: 80px 1fr 150px 200px 120px;
    gap: 20px;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    align-items: center;
    font-size: 14px;
    color: #333;
}

.vacante-row:hover {
    background-color: #f8f9fa;
}

.col-num {
    text-align: center;
    font-weight: 600;
    color: #666;
}

.col-puesto {
    font-weight: 500;
    color: #333;
}

.col-bases, .col-aplicar {
    text-align: center;
}

.col-dependencia {
    font-size: 13px;
    color: #666;
}

.btn-descargar-icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
    background-color: #f8f9ff;
    border: 2px solid #6366f1;
    color: #6366f1;
    font-size: 16px;
}

.btn-descargar-icono:hover {
    background-color: #6366f1;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.btn-descargar-icono i {
    font-size: 16px;
}



.btn-aplicar {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-aplicar {
    background-color: #e8f5e8;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.btn-aplicar:hover {
    background-color: #2e7d32;
    color: white;
}

.no-disponible {
    color: #999;
    font-style: italic;
    font-size: 12px;
}

/* Responsive */
@media (max-width: 768px) {
    .vacante-header-row,
    .vacante-row {
        grid-template-columns: 1fr;
        gap: 10px;
        text-align: left;
    }
    
    .col-num {
        text-align: left;
    }
    
    .col-bases, .col-aplicar {
        text-align: left;
    }
}

h4 {
    color: #333;
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 20px;
    font-weight: normal;
}

/* Cargar FontAwesome 6 igual que en admin */

@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .vacante-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .vacante-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .vacante-acciones {
        flex-direction: column;
    }
}
</style>

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
        <div>
            <h1><?php echo esc_html($direccion->nombre); ?></h1>
            <p><?php echo esc_html($direccion->descripcion); ?></p>
        </div>
    </div>
    
    <p>En esta sección se presentan las vacantes laborales disponibles en el Ministerio de Finanzas Públicas, conforme a los lineamientos establecidos por la Dirección de Recursos Humanos.</p>

    <?php if (empty($vacantes)): ?>
        <div class="no-vacantes">
            <div class="no-vacantes-icon">📋</div>
            <h3>No hay vacantes disponibles</h3>
            <p>Actualmente no hay vacantes activas en esta dirección. Te invitamos a revisar otras direcciones o volver más tarde.</p>
        </div>
    <?php else: ?>
        <div class="vacantes-lista-limpia">
            <div class="vacante-header-row">
                <div class="col-num">No.</div>
                <div class="col-puesto">Puesto</div>
                <div class="col-bases">Bases</div>
                <div class="col-dependencia">Dependencia</div>
                <div class="col-aplicar">Aplicar</div>
            </div>
            
            <?php 
            $contador = 1;
            foreach ($vacantes as $vacante): 
                // Mostrar el código de la vacante o generar uno si no existe
                $numero_vacante = !empty($vacante->codigo) ? $vacante->codigo : 'VAC-' . str_pad($vacante->id, 3, '0', STR_PAD_LEFT);
            ?>
            <div class="vacante-row">
                <div class="col-num"><?php echo esc_html($numero_vacante); ?></div>
                <div class="col-puesto"><?php echo esc_html($vacante->titulo); ?></div>
                <div class="col-bases">
                    <?php if (!empty($vacante->bases_pdf)): ?>
                        <a href="<?php echo esc_url($vacante->bases_pdf); ?>" target="_blank" class="btn-descargar-icono" title="Descargar bases">
                            <i class="fas fa-download"></i>
                        </a>
                    <?php else: ?>
                        <span class="no-disponible">No disponible</span>
                    <?php endif; ?>
                </div>
                <div class="col-dependencia"><?php echo esc_html($direccion->nombre); ?></div>
                <div class="col-aplicar">
                    <a href="<?php echo home_url('/aplicar-vacante/?id=' . $vacante->id); ?>" class="btn-aplicar">
                        <i class="fa fa-paper-plane"></i> Aplicar
                    </a>
                </div>
            </div>
            <?php 
            $contador++;
            endforeach; 
            ?>
        </div>
    <?php endif; ?>
</div>