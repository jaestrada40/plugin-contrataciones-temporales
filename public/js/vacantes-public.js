(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Inicializar funcionalidades
        initBuscador();
        initModales();
        initAplicaciones();
        
        /**
         * Inicializar buscador de vacantes
         */
        function initBuscador() {
            $('#btn-buscar-vacantes').on('click', function() {
                buscarVacantes();
            });
            
            // Buscar al presionar Enter en el campo de búsqueda
            $('#buscar-termino').on('keypress', function(e) {
                if (e.which === 13) {
                    buscarVacantes();
                }
            });
        }
        
        /**
         * Realizar búsqueda de vacantes
         */
        function buscarVacantes() {
            var termino = $('#buscar-termino').val();
            var direccionId = $('#filtro-direccion').val();
            var tipoContratoId = $('#filtro-tipo-contrato').val();
            
            // Mostrar loading
            $('#vacantes-resultados').html('<div class="loading">Buscando vacantes...</div>');
            
            $.ajax({
                url: vacantes_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'buscar_vacantes',
                    nonce: vacantes_ajax.nonce,
                    termino: termino,
                    direccion_id: direccionId,
                    tipo_contrato_id: tipoContratoId
                },
                success: function(response) {
                    if (response.success) {
                        $('#vacantes-resultados').html(response.data);
                        if (response.data.trim() === '') {
                            $('#vacantes-resultados').html('<div class="no-vacantes"><p>No se encontraron vacantes con los criterios especificados.</p></div>');
                        }
                    } else {
                        $('#vacantes-resultados').html('<div class="error">Error al buscar vacantes.</div>');
                    }
                },
                error: function() {
                    $('#vacantes-resultados').html('<div class="error">Error de conexión.</div>');
                }
            });
        }
        
        /**
         * Inicializar modales
         */
        function initModales() {
            // Cerrar modales
            $('.modal-close').on('click', function() {
                $(this).closest('.vacantes-modal').hide();
            });
            
            // Cerrar modal al hacer clic fuera
            $('.vacantes-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Ver detalles de vacante
            $(document).on('click', '.ver-detalle', function(e) {
                e.preventDefault();
                var vacanteId = $(this).data('vacante-id');
                mostrarDetalleVacante(vacanteId);
            });
            
            // Aplicar a vacante
            $(document).on('click', '.aplicar-vacante', function(e) {
                e.preventDefault();
                var vacanteId = $(this).data('vacante-id');
                mostrarFormularioAplicacion(vacanteId);
            });
        }
        
        /**
         * Mostrar detalles de vacante en modal
         */
        function mostrarDetalleVacante(vacanteId) {
            $('#modal-detalle-content').html('<div class="loading">Cargando detalles...</div>');
            $('#modal-detalle-vacante').show();
            
            $.ajax({
                url: vacantes_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_vacante_detalle',
                    nonce: vacantes_ajax.nonce,
                    vacante_id: vacanteId
                },
                success: function(response) {
                    if (response.success) {
                        $('#modal-detalle-content').html(response.data);
                    } else {
                        $('#modal-detalle-content').html('<div class="error">Error al cargar los detalles.</div>');
                    }
                },
                error: function() {
                    $('#modal-detalle-content').html('<div class="error">Error de conexión.</div>');
                }
            });
        }
        
        /**
         * Mostrar formulario de aplicación en modal
         */
        function mostrarFormularioAplicacion(vacanteId) {
            $('#modal-aplicar-content').html('<div class="loading">Cargando formulario...</div>');
            $('#modal-aplicar-vacante').show();
            
            $.ajax({
                url: vacantes_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_formulario_aplicacion',
                    nonce: vacantes_ajax.nonce,
                    vacante_id: vacanteId
                },
                success: function(response) {
                    if (response.success) {
                        $('#modal-aplicar-content').html(response.data);
                    } else {
                        $('#modal-aplicar-content').html('<div class="error">Error al cargar el formulario.</div>');
                    }
                },
                error: function() {
                    $('#modal-aplicar-content').html('<div class="error">Error de conexión.</div>');
                }
            });
        }
        
        /**
         * Inicializar manejo de aplicaciones
         */
        function initAplicaciones() {
            // Manejar envío de aplicación (delegado para formularios cargados dinámicamente)
            $(document).on('submit', '#form-aplicacion', function(e) {
                e.preventDefault();
                enviarAplicacion(this);
            });
            
            // Validar archivo CV
            $(document).on('change', '#cv', function() {
                validarArchivo(this);
            });
        }
        
        /**
         * Enviar aplicación
         */
        function enviarAplicacion(form) {
            var $form = $(form);
            var formData = new FormData(form);
            formData.append('action', 'aplicar_vacante');
            formData.append('nonce', vacantes_ajax.nonce);
            
            // Validar formulario
            if (!validarFormulario($form)) {
                return;
            }
            
            // Mostrar loading
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            $submitBtn.text('Enviando...').prop('disabled', true);
            
            $.ajax({
                url: vacantes_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        mostrarMensaje('success', response.data);
                        $form[0].reset();
                        
                        // Cerrar modal después de 3 segundos
                        setTimeout(function() {
                            $('#modal-aplicar-vacante').hide();
                        }, 3000);
                    } else {
                        mostrarMensaje('error', response.data);
                    }
                },
                error: function() {
                    mostrarMensaje('error', 'Error al procesar la solicitud. Inténtalo de nuevo.');
                },
                complete: function() {
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            });
        }
        
        /**
         * Validar formulario de aplicación
         */
        function validarFormulario($form) {
            var isValid = true;
            
            // Limpiar errores previos
            $form.find('.error-message').remove();
            $form.find('.error').removeClass('error');
            
            // Validar campos requeridos
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    mostrarErrorCampo($field, 'Este campo es obligatorio');
                    isValid = false;
                }
            });
            
            // Validar email
            var email = $form.find('#email').val();
            if (email && !isValidEmail(email)) {
                mostrarErrorCampo($form.find('#email'), 'Ingresa un email válido');
                isValid = false;
            }
            
            // Validar teléfono
            var telefono = $form.find('#telefono').val();
            if (telefono && !isValidPhone(telefono)) {
                mostrarErrorCampo($form.find('#telefono'), 'Ingresa un teléfono válido');
                isValid = false;
            }
            
            // Validar términos y condiciones
            if (!$form.find('#acepto-terminos').is(':checked')) {
                mostrarErrorCampo($form.find('#acepto-terminos'), 'Debes aceptar los términos y condiciones');
                isValid = false;
            }
            
            return isValid;
        }
        
        /**
         * Mostrar error en campo específico
         */
        function mostrarErrorCampo($field, mensaje) {
            $field.addClass('error');
            $field.after('<div class="error-message">' + mensaje + '</div>');
        }
        
        /**
         * Validar archivo CV
         */
        function validarArchivo(input) {
            var file = input.files[0];
            var $input = $(input);
            
            // Limpiar errores previos
            $input.removeClass('error');
            $input.siblings('.error-message').remove();
            
            if (file) {
                // Validar tamaño (5MB máximo)
                var maxSize = 5 * 1024 * 1024; // 5MB en bytes
                if (file.size > maxSize) {
                    mostrarErrorCampo($input, 'El archivo no debe superar los 5MB');
                    input.value = '';
                    return false;
                }
                
                // Validar tipo de archivo
                var allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    mostrarErrorCampo($input, 'Solo se permiten archivos PDF, DOC o DOCX');
                    input.value = '';
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * Mostrar mensaje de resultado
         */
        function mostrarMensaje(tipo, mensaje) {
            var $mensajeDiv = $('#mensaje-resultado');
            $mensajeDiv.removeClass('success error')
                      .addClass(tipo)
                      .html('<p>' + mensaje + '</p>')
                      .show();
            
            // Scroll al mensaje
            $mensajeDiv[0].scrollIntoView({ behavior: 'smooth' });
        }
        
        /**
         * Validar email
         */
        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        /**
         * Validar teléfono
         */
        function isValidPhone(phone) {
            var phoneRegex = /^[\d\s\-\+\(\)]+$/;
            return phoneRegex.test(phone) && phone.replace(/\D/g, '').length >= 8;
        }
        
        /**
         * Formatear números
         */
        function formatNumber(num) {
            return new Intl.NumberFormat('es-GT').format(num);
        }
        
        /**
         * Formatear fechas
         */
        function formatDate(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('es-GT');
        }
    });

})(jQuery);