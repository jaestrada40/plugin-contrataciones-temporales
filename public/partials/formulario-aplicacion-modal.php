<div class="aplicacion-modal-container">
    <div class="vacante-info-mini">
        <h3><?php echo esc_html($vacante->titulo); ?></h3>
        <div class="info-meta">
            <span><strong>Código:</strong> <?php echo esc_html($vacante->codigo); ?></span>
            <span><strong>Cierre:</strong> <?php echo date('d/m/Y', strtotime($vacante->fecha_cierre)); ?></span>
        </div>
    </div>

    <form id="form-aplicacion-modal" enctype="multipart/form-data">
        <input type="hidden" name="vacante_id" value="<?php echo $vacante->id; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="nombre-modal">Nombre Completo *</label>
                <input type="text" id="nombre-modal" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="email-modal">Correo Electrónico *</label>
                <input type="email" id="email-modal" name="email" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="telefono-modal">Teléfono *</label>
                <input type="tel" id="telefono-modal" name="telefono" required>
            </div>
            
            <div class="form-group">
                <label for="cv-modal">Curriculum Vitae</label>
                <input type="file" id="cv-modal" name="cv" accept=".pdf,.doc,.docx">
                <small>PDF, DOC, DOCX - Máx. 5MB</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="direccion-modal">Dirección</label>
            <textarea id="direccion-modal" name="direccion" rows="2" placeholder="Dirección completa (opcional)"></textarea>
        </div>
        
        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <input type="checkbox" id="acepto-terminos-modal" required>
                <span class="checkmark"></span>
                Acepto los términos y condiciones y autorizo el tratamiento de mis datos personales
            </label>
        </div>
        
        <div class="form-actions-modal">
            <button type="button" class="btn btn-secondary" onclick="$('#modal-aplicar-vacante').hide()">
                Cancelar
            </button>
            <button type="submit" class="btn btn-success">
                <i class="icon-send"></i>
                Enviar Aplicación
            </button>
        </div>
    </form>
    
    <div id="mensaje-resultado-modal" style="display: none;"></div>
</div>

<style>
.aplicacion-modal-container {
    padding: 10px;
}

.vacante-info-mini {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #007cba;
}

.vacante-info-mini h3 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 1.1em;
}

.info-meta {
    display: flex;
    gap: 20px;
    font-size: 0.9em;
    color: #6c757d;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
    font-size: 0.9em;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.25);
}

.form-group small {
    color: #6c757d;
    font-size: 0.8em;
    margin-top: 3px;
    display: block;
}

.checkbox-group {
    margin: 20px 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 0.9em;
    line-height: 1.4;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.form-actions-modal {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.form-actions-modal .btn {
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 6px;
}

#mensaje-resultado-modal {
    margin-top: 15px;
    padding: 12px;
    border-radius: 4px;
}

#mensaje-resultado-modal.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

#mensaje-resultado-modal.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.error-message {
    color: #dc3545;
    font-size: 0.8em;
    margin-top: 3px;
    display: block;
}

.form-group input.error,
.form-group textarea.error {
    border-color: #dc3545;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .info-meta {
        flex-direction: column;
        gap: 5px;
    }
    
    .form-actions-modal {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#form-aplicacion-modal').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'aplicar_vacante');
        formData.append('nonce', vacantes_ajax.nonce);
        
        // Validar formulario
        if (!validarFormularioModal($(this))) {
            return;
        }
        
        // Mostrar loading
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        $submitBtn.html('<i class="icon-loading"></i> Enviando...').prop('disabled', true);
        
        $.ajax({
            url: vacantes_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#mensaje-resultado-modal')
                        .removeClass('error')
                        .addClass('success')
                        .html('<p><strong>¡Éxito!</strong> ' + response.data + '</p>')
                        .show();
                    $('#form-aplicacion-modal')[0].reset();
                    
                    // Cerrar modal después de 3 segundos
                    setTimeout(function() {
                        $('#modal-aplicar-vacante').hide();
                    }, 3000);
                } else {
                    $('#mensaje-resultado-modal')
                        .removeClass('success')
                        .addClass('error')
                        .html('<p><strong>Error:</strong> ' + response.data + '</p>')
                        .show();
                }
            },
            error: function() {
                $('#mensaje-resultado-modal')
                    .removeClass('success')
                    .addClass('error')
                    .html('<p><strong>Error:</strong> No se pudo procesar la solicitud. Inténtalo de nuevo.</p>')
                    .show();
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    function validarFormularioModal($form) {
        var isValid = true;
        
        // Limpiar errores previos
        $form.find('.error-message').remove();
        $form.find('.error').removeClass('error');
        
        // Validar campos requeridos
        $form.find('[required]').each(function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            if (!value) {
                mostrarErrorCampoModal($field, 'Este campo es obligatorio');
                isValid = false;
            }
        });
        
        // Validar email
        var email = $form.find('#email-modal').val();
        if (email && !isValidEmail(email)) {
            mostrarErrorCampoModal($form.find('#email-modal'), 'Ingresa un email válido');
            isValid = false;
        }
        
        // Validar teléfono
        var telefono = $form.find('#telefono-modal').val();
        if (telefono && !isValidPhone(telefono)) {
            mostrarErrorCampoModal($form.find('#telefono-modal'), 'Ingresa un teléfono válido');
            isValid = false;
        }
        
        return isValid;
    }
    
    function mostrarErrorCampoModal($field, mensaje) {
        $field.addClass('error');
        $field.after('<span class="error-message">' + mensaje + '</span>');
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidPhone(phone) {
        var phoneRegex = /^[\d\s\-\+\(\)]+$/;
        return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 8;
    }
});
</script>