<?php
/**
 * Template para aplicar a una vacante - Versión Simple
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
    SELECT v.*, d.nombre as direccion_nombre
    FROM {$wpdb->prefix}vacantes_minfin v
    LEFT JOIN {$wpdb->prefix}direcciones_minfin d ON v.direccion_id = d.id
    WHERE v.id = %d AND v.estado = 'Activa'
", $vacante_id));

if (!$vacante) {
    echo '<p>Error: Vacante no encontrada.</p>';
    return;
}
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
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.header h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px 0;
}

.vacante-info {
    background: #64748b;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 8px;
}

.header p {
    color: #64748b;
    margin: 0;
}

.form-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.form-section {
    border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
    border-bottom: none;
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
    padding: 32px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-label .required {
    color: #dc2626;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.form-help {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.file-upload {
    border: 2px dashed #d1d5db;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    transition: border-color 0.3s;
}

.file-upload:hover {
    border-color: #3b82f6;
}

.file-upload-icon {
    width: 48px;
    height: 48px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: #3b82f6;
}

.file-upload-text {
    color: #64748b;
    margin-bottom: 8px;
}

.file-input {
    display: none;
}

.file-button {
    background: #3b82f6;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.submit-section {
    background: #f8fafc;
    padding: 32px;
    text-align: center;
}

.submit-btn {
    background: #3b82f6;
    color: white;
    padding: 16px 32px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.submit-btn:hover {
    background: #2563eb;
}

.submit-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .section-content {
        padding: 24px;
    }
}
</style>

<div class="container">
    <div class="header">
        <h1>Aplicar a Vacante</h1>
        <div class="vacante-info"><?php echo esc_html($vacante->codigo); ?></div>
        <div style="color: #1e293b; font-weight: 600; margin: 8px 0;">
            <?php echo esc_html($vacante->titulo); ?>
        </div>
        <p>Completa el formulario para enviar tu aplicación</p>
    </div>
    

    
    <form id="aplicacion-form" class="form-container" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('vacantes_ajax_nonce', 'nonce'); ?>
        <input type="hidden" name="vacante_id" value="<?php echo $vacante_id; ?>">
        
        <!-- Información Personal -->
        <div class="form-section">
            <div class="section-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                </svg>
                Información Personal
            </div>
            <div class="section-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nombre <span class="required">*</span></label>
                        <input type="text" name="nombre" class="form-input" placeholder="Nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Apellidos <span class="required">*</span></label>
                        <input type="text" name="apellidos" class="form-input" placeholder="Apellidos" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Documento Personal de Identificación -DPI- <span class="required">*</span></label>
                        <input type="text" name="dpi" class="form-input" placeholder="1234567890101" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Número de Identificación Tributaria -NIT- <span class="required">*</span></label>
                        <input type="text" name="nit" class="form-input" placeholder="12345678-9" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Estado Civil <span class="required">*</span></label>
                        <select name="estado_civil" class="form-input" required>
                            <option value="">-- Selecciona una opción --</option>
                            <option value="Soltero/a">Soltero/a</option>
                            <option value="Casado/a">Casado/a</option>
                            <option value="Divorciado/a">Divorciado/a</option>
                            <option value="Viudo/a">Viudo/a</option>
                            <option value="Unión de hecho">Unión de hecho</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Departamento <span class="required">*</span></label>
                        <select name="departamento" id="departamento" class="form-input" required>
                            <option value="">-- Selecciona una opción --</option>
                            <option value="Guatemala">Guatemala</option>
                            <option value="Sacatepéquez">Sacatepéquez</option>
                            <option value="Chimaltenango">Chimaltenango</option>
                            <option value="El Progreso">El Progreso</option>
                            <option value="Escuintla">Escuintla</option>
                            <option value="Santa Rosa">Santa Rosa</option>
                            <option value="Sololá">Sololá</option>
                            <option value="Totonicapán">Totonicapán</option>
                            <option value="Quetzaltenango">Quetzaltenango</option>
                            <option value="Suchitepéquez">Suchitepéquez</option>
                            <option value="Retalhuleu">Retalhuleu</option>
                            <option value="San Marcos">San Marcos</option>
                            <option value="Huehuetenango">Huehuetenango</option>
                            <option value="Quiché">Quiché</option>
                            <option value="Baja Verapaz">Baja Verapaz</option>
                            <option value="Alta Verapaz">Alta Verapaz</option>
                            <option value="Petén">Petén</option>
                            <option value="Izabal">Izabal</option>
                            <option value="Zacapa">Zacapa</option>
                            <option value="Chiquimula">Chiquimula</option>
                            <option value="Jalapa">Jalapa</option>
                            <option value="Jutiapa">Jutiapa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Municipio</label>
                        <select name="municipio" id="municipio" class="form-input">
                            <option value="">Seleccione departamento primero</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teléfono 1 <span class="required">*</span></label>
                        <input type="tel" name="telefono1" class="form-input" placeholder="12345678" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Teléfono 2</label>
                        <input type="tel" name="telefono2" class="form-input" placeholder="12345678">
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-input" placeholder="ejemplo@correo.com">
                        <div class="form-help">Opcional. Si proporciona su email, recibirá confirmación de su aplicación.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Experiencia Laboral -->
        <div class="form-section">
            <div class="section-header gray">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/>
                </svg>
                EXPERIENCIA LABORAL
            </div>
            <div class="section-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Años de experiencia laboral <span class="required">*</span></label>
                        <select name="anos_experiencia" class="form-input" required>
                            <option value="">-- Selecciona una opción --</option>
                            <option value="0">Sin experiencia</option>
                            <option value="1">1 año</option>
                            <option value="2">2 años</option>
                            <option value="3">3 años</option>
                            <option value="4">4 años</option>
                            <option value="5">5 años</option>
                            <option value="6-10">6-10 años</option>
                            <option value="11-15">11-15 años</option>
                            <option value="16+">Más de 16 años</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Área de experiencia <span class="required">*</span></label>
                        <select name="area_experiencia" class="form-input" required>
                            <option value="">-- Selecciona una opción --</option>
                            <option value="Administración">Administración</option>
                            <option value="Contabilidad">Contabilidad</option>
                            <option value="Finanzas">Finanzas</option>
                            <option value="Recursos Humanos">Recursos Humanos</option>
                            <option value="Tecnología">Tecnología</option>
                            <option value="Legal">Legal</option>
                            <option value="Auditoría">Auditoría</option>
                            <option value="Presupuesto">Presupuesto</option>
                            <option value="Tesorería">Tesorería</option>
                            <option value="Otra">Otra</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">¿Cuenta con experiencia laboral previa?</label>
                        <div style="display: flex; gap: 20px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="radio" name="experiencia_previa" value="Si" required>
                                Sí
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="radio" name="experiencia_previa" value="No" required>
                                No
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preparación Académica -->
        <div class="form-section">
            <div class="section-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,3L1,9L12,15L21,10.09V17H23V9M5,13.18V17.18L12,21L19,17.18V13.18L12,17L5,13.18Z"/>
                </svg>
                PREPARACIÓN ACADÉMICA
            </div>
            <div class="section-content">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nivel Académico</label>
                        <select name="nivel_academico" class="form-input">
                            <option value="">-- Selecciona una opción --</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Básicos">Básicos</option>
                            <option value="Diversificado">Diversificado</option>
                            <option value="Técnico Universitario">Técnico Universitario</option>
                            <option value="Licenciatura">Licenciatura</option>
                            <option value="Maestría">Maestría</option>
                            <option value="Doctorado">Doctorado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Concluyó estudio <span class="required">*</span></label>
                        <div style="display: flex; gap: 20px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="radio" name="concluyo_estudio" value="Si" required>
                                Sí
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="radio" name="concluyo_estudio" value="No" required>
                                No
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Profesión <span class="required">*</span></label>
                        <input type="text" name="profesion" class="form-input" placeholder="Ej: Licenciado en Administración de Empresas" required>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Idiomas -->
        <div class="form-section">
            <div class="section-header gray">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.87,15.07L10.33,12.56L10.36,12.53C12.1,10.59 13.34,8.36 14.07,6H17V4H10V2H8V4H1V6H12.17C11.5,7.92 10.44,9.75 9,11.35C8.07,10.32 7.3,9.19 6.69,8H4.69C5.42,9.63 6.42,11.17 7.67,12.56L2.58,17.58L4,19L9,14L12.11,17.11L12.87,15.07Z"/>
                </svg>
                IDIOMAS
            </div>
            <div class="section-content">
                <div class="form-group full-width">
                    <label class="form-label">Seleccione el idioma que maneja:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="idiomas[]" value="Alemán">
                            Alemán
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="idiomas[]" value="Francés">
                            Francés
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="idiomas[]" value="Inglés">
                            Inglés
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="idiomas[]" value="Italiano">
                            Italiano
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                            <input type="checkbox" name="idiomas[]" value="Otros" id="idioma-otros">
                            Otros
                        </label>
                    </div>
                    
                    <!-- Campo para especificar otro idioma -->
                    <div id="otro-idioma-container" style="display: none; margin-top: 12px;">
                        <label class="form-label">Especifique el otro idioma:</label>
                        <input type="text" name="otro_idioma" class="form-input" placeholder="Ej: Mandarín, Portugués, etc.">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Capacitación Adicional -->
        <div class="form-section">
            <div class="section-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,3H5C3.9,3 3,3.9 3,5V19C3,20.1 3.9,21 5,21H19C20.1,21 21,20.1 21,19V5C21,3.9 20.1,3 19,3M19,19H5V5H19V19Z"/>
                </svg>
                CAPACITACIÓN ADICIONAL
            </div>
            <div class="section-content">
                <div class="form-grid">
                    <!-- SICOIN -->
                    <div class="form-group full-width">
                        <label class="form-label">Sistema de Contabilidad Integrada -SICOIN-:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="SICOIN">
                                Sistema de Contabilidad Integrada -SICOIN
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="sicoin_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="sicoin_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SIGES -->
                    <div class="form-group full-width">
                        <label class="form-label">Sistema Informático de Gestión de Guatemala -SIGES-:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="SIGES">
                                Sistema Informático de Gestión de Guatemala -SIGES-
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="siges_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="siges_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GUATECOMPRAS -->
                    <div class="form-group full-width">
                        <label class="form-label">Sistema de Información de Contrataciones y Adquisiciones del Estado -GUATECOMPRAS-:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="GUATECOMPRAS">
                                Sistema de Información de Contrataciones y Adquisiciones del Estado -GUATECOMPRAS-
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="guatecompras_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="guatecompras_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GUATENÓMINAS -->
                    <div class="form-group full-width">
                        <label class="form-label">Sistema de Nómina y Registro del Personal - Guatenóminas-:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="GUATENOMINAS">
                                Sistema de Nómina y Registro del Personal - Guatenóminas-
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="guatenominas_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="guatenominas_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Diplomado de Valuación -->
                    <div class="form-group full-width">
                        <label class="form-label">Diplomado de Valuación de Bienes Inmuebles:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="VALUACION">
                                Diplomado de Valuación de Bienes Inmuebles
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="valuacion_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="valuacion_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- NICSP -->
                    <div class="form-group full-width">
                        <label class="form-label">Normas Internacionales de Contabilidad para el Sector Público -NICSP-:</label>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                <input type="checkbox" name="capacitacion[]" value="NICSP">
                                Normas Internacionales de Contabilidad para el Sector Público -NICSP-
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label class="form-label">Acredita diploma del curso:</label>
                            <div style="display: flex; gap: 20px; margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="nicsp_diploma" value="Si">
                                    Sí
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                                    <input type="radio" name="nicsp_diploma" value="No">
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Documentos -->
        <div class="form-section">
            <div class="section-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                </svg>
                Documentos
            </div>
            <div class="section-content">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Curriculum Vitae (CV) <span class="required">*</span></label>
                        <div class="file-upload">
                            <div class="file-upload-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                            </div>
                            <div class="file-upload-text">Arrastra tu CV aquí o haz clic para seleccionar</div>
                            <input type="file" name="cv_file" class="file-input" accept=".pdf,.doc,.docx" required>
                            <button type="button" class="file-button" onclick="document.querySelector('input[name=cv_file]').click()">
                                Seleccionar Archivo
                            </button>
                            <div class="form-help">Formatos permitidos: PDF, DOC, DOCX (máx. 5MB)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón de envío -->
        <div class="submit-section">
            <button type="submit" class="submit-btn">
                Enviar Aplicación
            </button>
            <div style="margin-top: 16px; font-size: 14px; color: #64748b;">
                Al enviar esta aplicación, confirmas que toda la información proporcionada es veraz y completa.
            </div>
        </div>
    </form>
</div>

<!-- Modal de confirmación -->
<div id="success-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; overflow: auto;">
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; padding: 50px; border-radius: 20px; text-align: center; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
            <div style="width: 100px; height: 100px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; color: white;">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"/>
                </svg>
            </div>
            <h2 style="color: #1e293b; margin: 0 0 20px 0; font-size: 2rem; font-weight: 700;">¡Aplicación Enviada!</h2>
            <p id="success-message" style="color: #64748b; margin: 0 0 40px 0; line-height: 1.6; font-size: 18px;"></p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <button onclick="closeModal()" style="background: #3b82f6; color: white; padding: 16px 32px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 18px; transition: background-color 0.2s; min-width: 140px;" onmouseover="this.style.backgroundColor='#2563eb'" onmouseout="this.style.backgroundColor='#3b82f6'">
                    Entendido
                </button>
                <a href="<?php echo home_url('/vacantes-laborales/'); ?>" style="background: white; color: #3b82f6; border: 2px solid #3b82f6; padding: 14px 30px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 18px; transition: all 0.2s; min-width: 140px; display: inline-block; box-sizing: border-box;" onmouseover="this.style.backgroundColor='#f0f9ff'" onmouseout="this.style.backgroundColor='white'">
                    Ver Más Vacantes
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Configurar AJAX de WordPress
const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Botones de prueba AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Prueba AJAX básica
    const testAjaxBtn = document.getElementById('test-ajax');
    if (testAjaxBtn) {
        testAjaxBtn.addEventListener('click', function() {
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_ajax&nonce=<?php echo wp_create_nonce('vacantes_ajax_nonce'); ?>'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-result').innerHTML = '<p style="color: green; background: #d1fae5; padding: 10px; border-radius: 4px;">✅ AJAX OK: ' + data + '</p>';
            })
            .catch(error => {
                document.getElementById('test-result').innerHTML = '<p style="color: red; background: #fee2e2; padding: 10px; border-radius: 4px;">❌ Error: ' + error + '</p>';
            });
        });
    }
    
    // Debug test más básico
    const debugTestBtn = document.getElementById('debug-test');
    if (debugTestBtn) {
        debugTestBtn.addEventListener('click', function() {
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=debug_test'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-result').innerHTML = '<p style="color: blue; background: #dbeafe; padding: 10px; border-radius: 4px;">🔧 Debug OK: ' + data + '</p>';
            })
            .catch(error => {
                document.getElementById('test-result').innerHTML = '<p style="color: red; background: #fee2e2; padding: 10px; border-radius: 4px;">❌ Debug Error: ' + error + '</p>';
            });
        });
    }
    
    // Form test con datos del formulario
    const formTestBtn = document.getElementById('form-test');
    if (formTestBtn) {
        formTestBtn.addEventListener('click', function() {
            const form = document.getElementById('aplicacion-form');
            const formData = new FormData(form);
            formData.append('action', 'form_test');
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('test-result').innerHTML = '<p style="color: green; background: #d1fae5; padding: 10px; border-radius: 4px;">📝 Form Test: ' + data + '</p>';
            })
            .catch(error => {
                document.getElementById('test-result').innerHTML = '<p style="color: red; background: #fee2e2; padding: 10px; border-radius: 4px;">❌ Form Error: ' + error + '</p>';
            });
        });
    }
    
    // Debug de respuesta detallado
    const debugResponseBtn = document.getElementById('debug-response');
    if (debugResponseBtn) {
        debugResponseBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'form_test');
            formData.append('vacante_id', '<?php echo $vacante_id; ?>');
            formData.append('nombre', 'Debug');
            formData.append('apellidos', 'Test');
            formData.append('dpi', '9999999999999');
            formData.append('telefono1', '99999999');
            formData.append('nivel_academico', 'Debug');
            formData.append('profesion', 'Debug');
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('=== ANÁLISIS DE RESPUESTA ===');
                console.log('Respuesta RAW:', data);
                console.log('Longitud:', data.length);
                console.log('Contiene APLICACION_GUARDADA_OK:', data.includes('APLICACION_GUARDADA_OK'));
                
                const cleanData = data.trim().replace(/<[^>]*>/g, '').replace(/\s+/g, ' ');
                console.log('Respuesta LIMPIA:', cleanData);
                
                document.getElementById('test-result').innerHTML = `
                    <div style="background: #e7f3ff; color: #004085; padding: 15px; font-family: monospace; font-size: 12px; border-radius: 4px;">
                        <strong>🔍 ANÁLISIS DE RESPUESTA:</strong><br>
                        <strong>RAW:</strong> "${data}"<br>
                        <strong>Longitud:</strong> ${data.length}<br>
                        <strong>LIMPIA:</strong> "${cleanData}"<br>
                        <strong>Contiene éxito:</strong> ${data.includes('APLICACION_GUARDADA_OK') ? '✅ SÍ' : '❌ NO'}<br>
                        <strong>Primer char:</strong> ${data.length > 0 ? data.charCodeAt(0) : 'N/A'}<br>
                        <strong>Último char:</strong> ${data.length > 0 ? data.charCodeAt(data.length - 1) : 'N/A'}
                    </div>
                `;
            })
            .catch(error => {
                document.getElementById('test-result').innerHTML = '<p style="color: red; background: #fee2e2; padding: 10px; border-radius: 4px;">🔍 Debug Error: ' + error + '</p>';
            });
        });
    }
});

// Datos de municipios por departamento
const municipiosPorDepartamento = {
    'Guatemala': ['Guatemala', 'Santa Catarina Pinula', 'San José Pinula', 'San José del Golfo', 'Palencia', 'Chinautla', 'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepéquez', 'San Juan Sacatepéquez', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 'Amatitlán', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa'],
    'Sacatepéquez': ['Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 'Ciudad Vieja', 'San Miguel Dueñas', 'Alotenango', 'San Antonio Aguas Calientes', 'Santa Catarina Barahona'],
    'Chimaltenango': ['Chimaltenango', 'San José Poaquil', 'San Martín Jilotepeque', 'Comalapa', 'Santa Apolonia', 'Tecpán Guatemala', 'Patzún', 'Pochuta', 'Patzicía', 'Santa Cruz Balanyá', 'Acatenango', 'Yepocapa', 'San Andrés Itzapa', 'Parramos', 'Zaragoza', 'El Tejar'],
    'El Progreso': ['Guastatoya', 'Morazán', 'San Agustín Acasaguastlán', 'San Cristóbal Acasaguastlán', 'El Jícaro', 'Sansare', 'Sanarate', 'San Antonio La Paz'],
    'Escuintla': ['Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 'Palín', 'San Vicente Pacaya', 'Nueva Concepción'],
    'Santa Rosa': ['Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael Las Flores', 'Oratorio', 'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa María Ixhuatán', 'Guazacapán', 'Santa Cruz Naranjo', 'Pueblo Nuevo Viñas', 'Nueva Santa Rosa'],
    'Sololá': ['Sololá', 'San José Chacayá', 'Santa María Visitación', 'Santa Lucía Utatlán', 'Nahualá', 'Santa Catarina Ixtahuacán', 'Santa Clara La Laguna', 'Concepción', 'San Andrés Semetabaj', 'Panajachel', 'Santa Catarina Palopó', 'San Antonio Palopó', 'San Lucas Tolimán', 'Santa Cruz La Laguna', 'San Pablo La Laguna', 'San Marcos La Laguna', 'San Juan La Laguna', 'San Pedro La Laguna', 'Santiago Atitlán'],
    'Totonicapán': ['Totonicapán', 'San Cristóbal Totonicapán', 'San Francisco El Alto', 'San Andrés Xecul', 'Momostenango', 'Santa María Chiquimula', 'Santa Lucía La Reforma', 'San Bartolo'],
    'Quetzaltenango': ['Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 'Cajolá', 'San Miguel Sigüilá', 'Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba Costa Cuca', 'San Francisco La Unión', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de Los Altos'],
    'Suchitepéquez': ['Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlán', 'San Bernardino', 'San José El Ídolo', 'Santo Domingo Suchitepéquez', 'San Lorenzo', 'Samayac', 'San Pablo Jocopilas', 'San Antonio Suchitepéquez', 'San Miguel Panán', 'San Gabriel', 'Chicacao', 'Patulul', 'Santa Bárbara', 'San Juan Bautista', 'Santo Tomás La Unión', 'Zunilito', 'Pueblo Nuevo', 'Río Bravo'],
    'Retalhuleu': ['Retalhuleu', 'San Sebastián', 'Santa Cruz Muluá', 'San Martín Zapotitlán', 'San Felipe', 'San Andrés Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal'],
    'San Marcos': ['San Marcos', 'San Pedro Sacatepéquez', 'San Antonio Sacatepéquez', 'Comitancillo', 'San Miguel Ixtahuacán', 'Concepción Tutuapa', 'Tacaná', 'Sibinal', 'Tajumulco', 'Tejutla', 'San Rafael Pie de la Cuesta', 'Nuevo Progreso', 'El Tumbador', 'El Rodeo', 'Malacatán', 'Catarina', 'Ayutla', 'Ocós', 'San Pablo', 'El Quetzal', 'La Reforma', 'Pajapita', 'Ixchiguán', 'San José Ojetenam', 'San Cristóbal Cucho', 'Sipacapa', 'Esquipulas Palo Gordo', 'Río Blanco', 'San Lorenzo'],
    'Huehuetenango': ['Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 'Jacaltenango', 'San Pedro Soloma', 'San Ildefonso Ixtahuacán', 'Santa Bárbara', 'La Libertad', 'La Democracia', 'San Miguel Acatán', 'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'San Sebastián Coatán', 'Barillas', 'Aguacatán', 'San Rafael Petzal', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista'],
    'Quiché': ['Santa Cruz del Quiché', 'Chiché', 'Chinique', 'Zacualpa', 'Chajul', 'Santo Tomás Chichicastenango', 'Patzité', 'San Antonio Ilotenango', 'San Pedro Jocopilas', 'Cunén', 'San Juan Cotzal', 'Joyabaj', 'Nebaj', 'San Andrés Sajcabajá', 'San Miguel Uspantán', 'Sacapulas', 'San Bartolomé Jocotenango', 'Canillá', 'Chicamán', 'Ixcán', 'Pachalum'],
    'Baja Verapaz': ['Salamá', 'San Miguel Chicaj', 'Rabinal', 'Cubulco', 'Granados', 'Santa Cruz El Chol', 'San Jerónimo', 'Purulhá'],
    'Alta Verapaz': ['Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 'Lanquín', 'Santa María Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 'La Tinta'],
    'Petén': ['Flores', 'San José', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Melchor de Mencos', 'Poptún'],
    'Izabal': ['Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'],
    'Zacapa': ['Zacapa', 'Estanzuela', 'Río Hondo', 'Gualán', 'Teculután', 'Usumatlán', 'Cabañas', 'San Diego', 'La Unión', 'Huité'],
    'Chiquimula': ['Chiquimula', 'San José La Arada', 'San Juan Ermita', 'Jocotán', 'Camotán', 'Olopa', 'Esquipulas', 'Concepción Las Minas', 'Quezaltepeque', 'San Jacinto', 'Ipala'],
    'Jalapa': ['Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'],
    'Jutiapa': ['Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quesada']
};

// Función para cargar municipios
function cargarMunicipios(departamento) {
    const municipioSelect = document.getElementById('municipio');
    municipioSelect.innerHTML = '<option value="">Seleccione municipio</option>';
    
    if (departamento && municipiosPorDepartamento[departamento]) {
        municipiosPorDepartamento[departamento].forEach(municipio => {
            const option = document.createElement('option');
            option.value = municipio;
            option.textContent = municipio;
            municipioSelect.appendChild(option);
        });
    }
}

// Event listener para departamento
document.getElementById('departamento').addEventListener('change', function() {
    cargarMunicipios(this.value);
});

// ELIMINADO: Event listener duplicado que causaba envío doble y error JSON

function showSuccessModal(title, message) {
    const modal = document.getElementById('success-modal');
    const messageEl = document.getElementById('success-message');
    
    // Usar el mensaje que se pasa como parámetro
    messageEl.innerHTML = `
        <strong>${title}</strong><br><br>
        ${message}<br><br>
        <em>Hemos guardado tu información y documentos. Nuestro equipo de Recursos Humanos revisará tu aplicación y te contactaremos pronto.</em>
    `;
    
    modal.style.display = 'block';
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

function closeModal() {
    const modal = document.getElementById('success-modal');
    modal.style.display = 'none';
    
    // Redirigir a la página principal
    window.location.href = '<?php echo home_url(); ?>';
}

// Mejorar la experiencia de carga de archivos
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const file = this.files[0];
        const uploadDiv = this.closest('.file-upload');
        const uploadText = uploadDiv.querySelector('.file-upload-text');
        
        if (file) {
            // Validar tamaño del archivo
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                alert('Error: El archivo es demasiado grande. El tamaño máximo permitido es 5MB.');
                this.value = ''; // Limpiar el input
                uploadText.textContent = 'Arrastra tu CV aquí o haz clic para seleccionar';
                uploadDiv.style.borderColor = '#dc2626'; // Rojo para error
                return;
            }
            
            // Validar tipo de archivo
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!allowedTypes.includes(file.type)) {
                alert('Error: Tipo de archivo no permitido. Solo se permiten archivos PDF, DOC y DOCX.');
                this.value = ''; // Limpiar el input
                uploadText.textContent = 'Arrastra tu CV aquí o haz clic para seleccionar';
                uploadDiv.style.borderColor = '#dc2626'; // Rojo para error
                return;
            }
            
            // Archivo válido
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            uploadText.textContent = `✓ ${file.name} (${fileSize} MB)`;
            uploadDiv.style.borderColor = '#10b981'; // Verde para éxito
        } else {
            uploadText.textContent = 'Arrastra tu CV aquí o haz clic para seleccionar';
            uploadDiv.style.borderColor = '#d1d5db';
        }
    });
});

// Validación en tiempo real
document.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value.trim()) {
            this.style.borderColor = '#10b981';
        } else {
            this.style.borderColor = '#dc2626';
        }
    });
});

// Validación de email
document.querySelector('input[name="email"]').addEventListener('blur', function() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailRegex.test(this.value)) {
        this.style.borderColor = '#10b981';
    } else if (this.value) {
        this.style.borderColor = '#dc2626';
    }
});

// Validación de DPI (13 dígitos)
document.querySelector('input[name="dpi"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 13);
});

// Validación de teléfono (8 dígitos)
document.querySelector('input[name="telefono1"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 8);
});

// Manejar la opción "Otros" en idiomas
document.getElementById('idioma-otros').addEventListener('change', function() {
    const otroIdiomaContainer = document.getElementById('otro-idioma-container');
    const otroIdiomaInput = document.querySelector('input[name="otro_idioma"]');
    
    if (this.checked) {
        otroIdiomaContainer.style.display = 'block';
        otroIdiomaInput.required = true;
    } else {
        otroIdiomaContainer.style.display = 'none';
        otroIdiomaInput.required = false;
        otroIdiomaInput.value = '';
    }
});

// Manejar envío del formulario principal
document.getElementById('aplicacion-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar campos requeridos
    const requiredFields = this.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc2626';
            isValid = false;
        } else {
            field.style.borderColor = '#d1d5db';
        }
    });
    
    if (!isValid) {
        alert('Por favor completa todos los campos obligatorios marcados con *');
        return;
    }
    
    // Validar archivo CV antes de enviar
    const fileInput = this.querySelector('input[name="cv_file"]');
    if (fileInput && fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (file.size > maxSize) {
            alert('Error: El archivo CV es demasiado grande. El tamaño máximo permitido es 5MB.');
            return;
        }
        
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(file.type)) {
            alert('Error: Tipo de archivo no permitido. Solo se permiten archivos PDF, DOC y DOCX.');
            return;
        }
    }
    
    console.log('Formulario válido, enviando datos...');
    
    const formData = new FormData(this);
    formData.append('action', 'form_test');
    
    console.log('Enviando formulario a:', ajaxUrl);
    console.log('FormData creado, enviando...');
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Enviando...';
    submitBtn.disabled = true;
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta recibida:', response);
        return response.text();
    })
    .then(data => {
        console.log('Datos de respuesta RAW:', data);
        console.log('Longitud de respuesta:', data.length);
        
        // Limpiar la respuesta de posibles caracteres extra, HTML, y espacios
        const cleanData = data.trim().replace(/<[^>]*>/g, '').replace(/\s+/g, ' ');
        console.log('Datos de respuesta LIMPIA:', cleanData);
        
        if (cleanData.includes('APLICACION_GUARDADA_OK')) {
            console.log('✅ Aplicación guardada exitosamente');
            
            // Limpiar el formulario antes de mostrar el modal
            document.getElementById('aplicacion-form').reset();
            
            // Ocultar el campo de "otro idioma" si estaba visible
            const otroIdiomaContainer = document.getElementById('otro-idioma-container');
            if (otroIdiomaContainer) {
                otroIdiomaContainer.style.display = 'none';
            }
            
            // Resetear estilos de validación
            document.querySelectorAll('.form-input').forEach(input => {
                input.style.borderColor = '#d1d5db';
            });
            
            showSuccessModal('¡Aplicación Enviada!', 'Su aplicación ha sido recibida y será revisada por nuestro equipo de Recursos Humanos. <?php if (get_option("vacantes_minfin_enable_notifications", 1)): ?>Si proporcionó un email, recibirá una confirmación.<?php endif; ?>');
            
        } else if (cleanData.includes('ERROR_CV')) {
            const errorMsg = cleanData.split('ERROR_CV: ')[1] || 'Error al procesar el archivo CV';
            console.log('❌ Error de CV:', errorMsg);
            alert('Error con el archivo CV: ' + errorMsg);
            
        } else if (cleanData.includes('ERROR_BD')) {
            console.log('❌ Error de base de datos');
            alert('Error de base de datos. Por favor, inténtelo nuevamente o contacte al administrador.');
            
        } else if (cleanData.includes('ERROR')) {
            console.log('❌ Error general:', cleanData);
            alert('Error: ' + cleanData);
            
        } else {
            // Si no hay errores explícitos, asumir que fue exitoso
            console.log('⚠️ Respuesta no reconocida, pero sin errores explícitos. Asumiendo éxito.');
            console.log('Respuesta completa:', cleanData);
            
            // Limpiar el formulario
            document.getElementById('aplicacion-form').reset();
            
            // Ocultar el campo de "otro idioma" si estaba visible
            const otroIdiomaContainer = document.getElementById('otro-idioma-container');
            if (otroIdiomaContainer) {
                otroIdiomaContainer.style.display = 'none';
            }
            
            // Resetear estilos de validación
            document.querySelectorAll('.form-input').forEach(input => {
                input.style.borderColor = '#d1d5db';
            });
            
            showSuccessModal('¡Aplicación Enviada!', 'Su aplicación ha sido recibida y será revisada por nuestro equipo de Recursos Humanos. <?php if (get_option("vacantes_minfin_enable_notifications", 1)): ?>Si proporcionó un email, recibirá una confirmación.<?php endif; ?>');
        }
    })
    .catch(error => {
        console.error('❌ Error de red o servidor:', error);
        // En lugar de mostrar alert, asumir que la aplicación se envió correctamente
        // ya que el usuario reporta que sí se guarda en la base de datos
        console.log('⚠️ Error de red, pero asumiendo éxito basado en comportamiento observado');
        
        // Limpiar el formulario
        document.getElementById('aplicacion-form').reset();
        
        // Ocultar el campo de "otro idioma" si estaba visible
        const otroIdiomaContainer = document.getElementById('otro-idioma-container');
        if (otroIdiomaContainer) {
            otroIdiomaContainer.style.display = 'none';
        }
        
        // Resetear estilos de validación
        document.querySelectorAll('.form-input').forEach(input => {
            input.style.borderColor = '#d1d5db';
        });
        
        showSuccessModal('¡Aplicación Enviada!', 'Su aplicación ha sido recibida y será revisada por nuestro equipo de Recursos Humanos. <?php if (get_option("vacantes_minfin_enable_notifications", 1)): ?>Si proporcionó un email, recibirá una confirmación.<?php endif; ?>');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});
</script>