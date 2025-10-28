<?php
/**
 * Template para aplicar a una vacante
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$vacante_id = intval($_GET['id'] ?? 0);

if (!$vacante_id) {
    wp_redirect(home_url('/vacantes-laborales/'));
    exit;
}

// Obtener información de la vacante
$vacante = $wpdb->get_row($wpdb->prepare("
    SELECT v.*, d.nombre as direccion_nombre
    FROM {$wpdb->prefix}vs_vacantes v
    LEFT JOIN {$wpdb->prefix}vs_direcciones d ON v.direccion_id = d.id
    WHERE v.id = %d AND v.estado = 'Activa'
", $vacante_id));

if (!$vacante) {
    wp_redirect(home_url('/vacantes-laborales/'));
    exit;
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplicar a Vacante - <?php echo esc_html($vacante->titulo); ?> - <?php bloginfo('name'); ?></title>
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
</head>
<body>
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
            <?php wp_nonce_field('aplicar_vacante', 'aplicacion_nonce'); ?>
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
                            <label class="form-label">Nombres <span class="required">*</span></label>
                            <input type="text" name="nombres" class="form-input" placeholder="Ingresa tus nombres" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Apellidos <span class="required">*</span></label>
                            <input type="text" name="apellidos" class="form-input" placeholder="Ingresa tus apellidos" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">DPI <span class="required">*</span></label>
                            <input type="text" name="dpi" class="form-input" placeholder="1234567890101" required>
                            <div class="form-help">Ingresa tu DPI sin espacios ni guiones</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Teléfono <span class="required">*</span></label>
                            <input type="tel" name="telefono" class="form-input" placeholder="12345678" required>
                            <div class="form-help">Número de teléfono sin espacios ni guiones</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-input" placeholder="tu.email@ejemplo.com" required>
                            <div class="form-help">Te enviaremos confirmaciones y actualizaciones a este email</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Dirección</label>
                            <textarea name="direccion" class="form-input form-textarea" placeholder="Dirección completa (opcional)"></textarea>
                            <div class="form-help">Incluye zona, municipio y departamento</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información Profesional -->
            <div class="form-section">
                <div class="section-header gray">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12,7V3H2V21H22V7H12M6,19H4V17H6V19M6,15H4V13H6V15M6,11H4V9H6V11M6,7H4V5H6V7M10,19H8V17H10V19M10,15H8V13H10V15M10,11H8V9H10V11M10,7H8V5H10V7M20,19H12V17H20V19M20,15H12V13H20V15M20,11H12V9H20V11Z"/>
                    </svg>
                    Información Profesional
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nivel Educativo</label>
                            <select name="nivel_educativo" class="form-input">
                                <option value="">Selecciona tu nivel educativo</option>
                                <option value="Primaria">Primaria</option>
                                <option value="Básicos">Básicos</option>
                                <option value="Diversificado">Diversificado</option>
                                <option value="Técnico">Técnico</option>
                                <option value="Universitario">Universitario</option>
                                <option value="Postgrado">Postgrado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Profesión/Carrera</label>
                            <input type="text" name="profesion" class="form-input" placeholder="Ej: Licenciatura en Administración">
                        </div>
                        
                        <div class="form-group full-width">
                            <label class="form-label">Experiencia Laboral</label>
                            <textarea name="experiencia" class="form-input form-textarea" placeholder="Describe tu experiencia laboral relevante..."></textarea>
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
                        <div class="form-group">
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
                        
                        <div class="form-group">
                            <label class="form-label">Carta de Presentación</label>
                            <div class="file-upload">
                                <div class="file-upload-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                </div>
                                <div class="file-upload-text">Carta de presentación (opcional)</div>
                                <input type="file" name="carta_file" class="file-input" accept=".pdf,.doc,.docx">
                                <button type="button" class="file-button" onclick="document.querySelector('input[name=carta_file]').click()">
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
    
    <script>
    document.getElementById('aplicacion-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
        
        // Aquí iría la lógica AJAX para enviar el formulario
        // Por ahora simulamos el envío
        setTimeout(() => {
            alert('¡Aplicación enviada exitosamente! Te contactaremos pronto.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 2000);
    });
    
    // Mejorar la experiencia de carga de archivos
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                const uploadText = this.closest('.file-upload').querySelector('.file-upload-text');
                uploadText.textContent = `Archivo seleccionado: ${fileName}`;
            }
        });
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>