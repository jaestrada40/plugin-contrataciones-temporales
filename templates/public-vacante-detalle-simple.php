<?php
/**
 * Template para mostrar detalles de una vacante específica - Versión Simple
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$vacante_id = intval($_GET['id'] ?? 0);

if (!$vacante_id) {
    echo '<p>Error: No se especificó una vacante válida.</p>';
    return;
}

// Obtener información de la vacante
$vacante = $wpdb->get_row($wpdb->prepare("
    SELECT v.*, d.nombre as direccion_nombre, tc.nombre as tipo_contrato_nombre
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
    LEFT JOIN {$wpdb->prefix}tipos_contrato_minfin tc ON v.tipo_contrato_id = tc.id
    WHERE v.id = %d AND v.estado = 'Activa'
", $vacante_id));

if (!$vacante) {
    echo '<p>Error: Vacante no encontrada.</p>';
    return;
}



// Obtener total de aplicaciones
$total_aplicaciones = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}aplicaciones_minfin WHERE vacante_id = %d",
    $vacante_id
));
?>

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
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 40px;
}

.header-left h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.header-left p {
    color: #64748b;
    margin: 0;
}

.codigo-vacante {
    margin-top: 8px;
    font-size: 14px;
}

.codigo-label {
    color: #64748b;
    font-weight: 500;
}

.codigo-value {
    color: #1e293b;
    font-weight: 600;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 4px;
}

.header-right {
    text-align: right;
}



.aplicaciones-info {
    font-size: 12px;
    color: #64748b;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

.main-content {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.section-header {
    background: #3b82f6;
    color: white;
    padding: 16px 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header.gray {
    background: #64748b;
}

.section-content {
    padding: 24px;
}

.sidebar {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    height: fit-content;
}

.sidebar-header {
    background: #1e293b;
    color: white;
    padding: 16px 24px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-content {
    padding: 24px;
}

.info-item {
    margin-bottom: 20px;
}

.info-label {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
    font-size: 14px;
}

.info-value {
    color: #64748b;
    font-size: 14px;
}

.info-value.highlight {
    color: #dc2626;
    font-weight: 600;
}

.info-value.codigo-highlight {
    color: #3b82f6;
    font-weight: 600;
    background: #eff6ff;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: monospace;
}

.pdf-section {
    background: #fef3c7;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #f59e0b;
    margin-bottom: 24px !important;
}

.pdf-section .info-label {
    color: #92400e;
    font-weight: 700;
    margin-bottom: 8px;
}

.pdf-download {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pdf-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f59e0b;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    width: fit-content;
}

.pdf-link:hover {
    background: #d97706;
    color: white;
    transform: translateY(-1px);
}

.pdf-note {
    font-size: 12px;
    color: #92400e;
    font-style: italic;
}

.cta-section {
    background: #f8fafc;
    padding: 32px;
    text-align: center;
    border-top: 1px solid #e2e8f0;
}

.cta-icon {
    width: 60px;
    height: 60px;
    background: #3b82f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: white;
}

.cta-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.cta-subtitle {
    color: #64748b;
    margin: 0 0 24px 0;
}

.cta-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
    cursor: pointer;
    border: 1px solid transparent;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    color: white;
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



@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .header {
        flex-direction: column;
        gap: 20px;
    }
    
    .header-right {
        text-align: left;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
}
</style>

<div class="container">
    <div class="header">
        <div class="header-left">
            <h1><?php echo esc_html($vacante->titulo); ?></h1>
            <p><?php echo esc_html($vacante->direccion_nombre); ?></p>
            <?php if (!empty($vacante->codigo)): ?>
            <div class="codigo-vacante">
                <span class="codigo-label">Código:</span>
                <span class="codigo-value"><?php echo esc_html($vacante->codigo); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <div class="aplicaciones-info">
                <?php echo $total_aplicaciones; ?> personas han aplicado
            </div>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="main-content">
            <div class="section-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17Z"/>
                </svg>
                Descripción del Puesto
            </div>
            <div class="section-content">
                <?php echo nl2br(esc_html($vacante->descripcion)); ?>
            </div>
            
            <div class="section-header gray">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"/>
                </svg>
                Requisitos
            </div>
            <div class="section-content">
                <?php echo nl2br(esc_html($vacante->requisitos)); ?>
            </div>
        </div>
        
        <div class="sidebar">
            <div class="sidebar-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17Z"/>
                </svg>
                Información del Puesto
            </div>
            <div class="sidebar-content">
                <?php if (!empty($vacante->codigo)): ?>
                <div class="info-item">
                    <div class="info-label">Código de la Vacante</div>
                    <div class="info-value codigo-highlight"><?php echo esc_html($vacante->codigo); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($vacante->bases_pdf) && !empty($vacante->bases_pdf)): ?>
                <div class="info-item pdf-section">
                    <div class="info-label">📄 Bases de la Convocatoria</div>
                    <div class="pdf-download">
                        <a href="<?php echo esc_url($vacante->bases_pdf); ?>" target="_blank" class="pdf-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                            </svg>
                            Descargar PDF
                        </a>
                        <div class="pdf-note">Documento oficial con términos y condiciones</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Tipo de Contrato</div>
                    <div class="info-value"><?php echo esc_html($vacante->tipo_contrato_nombre ?: '011'); ?></div>
                </div>
                
                <?php if ($vacante->salario): ?>
                <div class="info-item">
                    <div class="info-label">Salario</div>
                    <div class="info-value">Q<?php echo number_format($vacante->salario, 2); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Fecha de Inicio</div>
                    <div class="info-value"><?php echo date('d \d\e F \d\e Y', strtotime($vacante->fecha_creacion)); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Fecha Límite</div>
                    <div class="info-value highlight"><?php echo date('d \d\e F \d\e Y', strtotime($vacante->fecha_limite)); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Dirección</div>
                    <div class="info-value"><?php echo esc_html($vacante->direccion_nombre); ?></div>
                </div>
            </div>
            
            <div class="cta-section">
                <div class="cta-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17Z"/>
                    </svg>
                </div>
                <h3 class="cta-title">¿Interesado?</h3>
                <p class="cta-subtitle">Completa tu aplicación y únete a nuestro equipo.</p>
                
                <div class="cta-buttons">
                    <a href="<?php echo home_url('/aplicar-vacante/?id=' . $vacante->id); ?>" class="btn btn-primary">
                        Aplicar Ahora
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>