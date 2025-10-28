<div class="vacante-aplicacion-container">
    <div class="vacante-info">
        <h2><?php echo esc_html($vacante->titulo); ?></h2>
        <div class="vacante-meta">
            <span><strong>Código:</strong> <?php echo esc_html($vacante->codigo); ?></span>
            <span><strong>Dirección:</strong> <?php echo esc_html($vacante->direccion_nombre); ?></span>
            <span><strong>Tipo de Contrato:</strong> <?php echo esc_html($vacante->tipo_contrato_nombre); ?></span>
            <span><strong>Salario:</strong> Q. <?php echo number_format($vacante->salario, 2); ?></span>
        </div>
        
        <div class="vacante-descripcion">
            <h3>Descripción del Puesto</h3>
            <?php echo wpautop($vacante->descripcion); ?>
        </div>
        
        <?php if ($vacante->requisitos): ?>
        <div class="vacante-requisitos">
            <h3>Requisitos</h3>
            <?php echo wpautop($vacante->requisitos); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($vacante->beneficios): ?>
        <div class="vacante-beneficios">
            <h3>Beneficios</h3>
            <?php echo wpautop($vacante->beneficios); ?>
        </div>
        <?php endif; ?>
        
        <div class="vacante-fechas">
            <p><strong>Fecha de cierre:</strong> <?php echo date('d/m/Y', strtotime($vacante->fecha_cierre)); ?></p>
        </div>
    </div>

    <div class="aplicacion-form-container">
        <h3>Aplicar a esta Vacante</h3>
        
        <!-- Botones de prueba AJAX -->
        <button type="button" id="test-ajax" class="btn btn-info">Probar AJAX</button>
        <button type="button" id="debug-test" class="btn btn-warning">Debug Test</button>
        <div id="test-result"></div>
        
        <form id="form-aplicacion" enctype="multipart/form-data">
            <input type="hidden" name="vacante_id" value="<?php echo $vacante->id; ?>">
            <?php wp_nonce_field('vacantes_ajax_nonce', 'nonce'); ?>
            
            <div class="form-group">
                <label for="nombre">Nombre Completo *</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono *</label>
                <input type="tel" id="telefono" name="telefono" required>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="cv">Curriculum Vitae (PDF, DOC, DOCX - Máx. 5MB)</label>
                <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx">
                <small>Formatos permitidos: PDF, DOC, DOCX. Tamaño máximo: 5MB</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="acepto-terminos" required>
                    Acepto los términos y condiciones y autorizo el tratamiento de mis datos personales
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Enviar Aplicación</button>
                <button type="button" class="btn btn-secondary" onclick="history.back()">Cancelar</button>
            </div>
        </form>
        
        <div id="mensaje-resultado" style="display: none;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Prueba AJAX
    $('#test-ajax').on('click', function() {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'test_ajax',
                nonce: '<?php echo wp_create_nonce('vacantes_ajax_nonce'); ?>'
            },
            success: function(response) {
                $('#test-result').html('<p style="color: green;">AJAX OK: ' + response + '</p>');
            },
            error: function(xhr, status, error) {
                $('#test-result').html('<p style="color: red;">AJAX Error: ' + error + ' - Status: ' + xhr.status + ' - Response: ' + xhr.responseText + '</p>');
            }
        });
    });
    
    // Debug test más básico
    $('#debug-test').on('click', function() {
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'debug_test'
        }, function(response) {
            $('#test-result').html('<p style="color: blue;">Debug OK: ' + response + '</p>');
        }).fail(function(xhr) {
            $('#test-result').html('<p style="color: red;">Debug Error: ' + xhr.status + ' - ' + xhr.responseText + '</p>');
        });
    });
    
    $('#form-aplicacion').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Formulario enviado');
        
        var formData = new FormData(this);
        formData.append('action', 'aplicar_vacante');
        
        // Debug: mostrar datos del formulario
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Mostrar loading
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.text('Enviando...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta recibida:', response);
                $('#mensaje-resultado')
                    .removeClass('error')
                    .addClass('success')
                    .html('<p>Respuesta del servidor: ' + response + '</p>')
                    .show();
                $('#form-aplicacion')[0].reset();
            },
            error: function(xhr, status, error) {
                console.log('Error AJAX:', xhr, status, error);
                console.log('Respuesta recibida:', xhr.responseText);
                console.log('Datos de respuesta:', xhr.responseText);
                console.log('Error del servidor:', error);
                $('#mensaje-resultado')
                    .removeClass('success')
                    .addClass('error')
                    .html('<p>Error: ' + error + '<br>Status: ' + xhr.status + '<br>Respuesta: ' + xhr.responseText + '</p>')
                    .show();
            },
            complete: function() {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>