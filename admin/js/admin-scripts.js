/**
 * Scripts del panel administrativo de Vacantes MINFIN
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Inicializar funcionalidades del admin
        initDashboard();
        initForms();
        initTables();
        
        /**
         * Inicializar dashboard
         */
        function initDashboard() {
            // Cargar estadísticas si estamos en el dashboard
            if ($('#vacantes-dashboard').length) {
                loadDashboardStats();
            }
        }
        
        /**
         * Cargar estadísticas del dashboard
         */
        function loadDashboardStats() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vacantes_get_dashboard_data',
                    nonce: vacantes_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateDashboardStats(response.data);
                    }
                },
                error: function() {
                    console.log('Error al cargar estadísticas del dashboard');
                }
            });
        }
        
        /**
         * Actualizar estadísticas en el dashboard
         */
        function updateDashboardStats(data) {
            if (data.total_vacantes !== undefined) {
                $('#stat-total-vacantes').text(data.total_vacantes);
            }
            if (data.vacantes_activas !== undefined) {
                $('#stat-vacantes-activas').text(data.vacantes_activas);
            }
            if (data.total_aplicaciones !== undefined) {
                $('#stat-total-aplicaciones').text(data.total_aplicaciones);
            }
            if (data.aplicaciones_pendientes !== undefined) {
                $('#stat-aplicaciones-pendientes').text(data.aplicaciones_pendientes);
            }
        }
        
        /**
         * Inicializar formularios
         */
        function initForms() {
            // Validación de formularios
            $('.vacantes-form').on('submit', function(e) {
                if (!validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Auto-guardar borradores
            $('.vacantes-form input, .vacantes-form textarea').on('input', debounce(function() {
                saveDraft($(this).closest('form'));
            }, 2000));
        }
        
        /**
         * Validar formulario
         */
        function validateForm($form) {
            var isValid = true;
            
            // Limpiar errores previos
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validar campos requeridos
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    showFieldError($field, 'Este campo es obligatorio');
                    isValid = false;
                }
            });
            
            // Validar emails
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !isValidEmail(email)) {
                    showFieldError($field, 'Ingresa un email válido');
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        /**
         * Mostrar error en campo
         */
        function showFieldError($field, message) {
            $field.addClass('error');
            $field.after('<div class="error-message" style="color: #dc3232; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        }
        
        /**
         * Guardar borrador
         */
        function saveDraft($form) {
            var formData = $form.serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=vacantes_save_draft&nonce=' + vacantes_admin.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification('Borrador guardado automáticamente', 'success');
                    }
                }
            });
        }
        
        /**
         * Inicializar tablas
         */
        function initTables() {
            // Ordenamiento de tablas
            $('.vacantes-table th[data-sort]').on('click', function() {
                var $th = $(this);
                var column = $th.data('sort');
                var direction = $th.hasClass('asc') ? 'desc' : 'asc';
                
                sortTable($th.closest('table'), column, direction);
                
                // Actualizar clases
                $th.siblings().removeClass('asc desc');
                $th.removeClass('asc desc').addClass(direction);
            });
            
            // Selección múltiple
            $('.vacantes-table .select-all').on('change', function() {
                var checked = $(this).is(':checked');
                $(this).closest('table').find('.select-item').prop('checked', checked);
            });
            
            // Acciones en lote
            $('.bulk-action-btn').on('click', function() {
                var action = $('#bulk-action-select').val();
                var selected = $('.select-item:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selected.length === 0) {
                    alert('Selecciona al menos un elemento');
                    return;
                }
                
                if (confirm('¿Estás seguro de realizar esta acción?')) {
                    performBulkAction(action, selected);
                }
            });
        }
        
        /**
         * Ordenar tabla
         */
        function sortTable($table, column, direction) {
            var $tbody = $table.find('tbody');
            var rows = $tbody.find('tr').toArray();
            
            rows.sort(function(a, b) {
                var aVal = $(a).find('[data-sort="' + column + '"]').text().trim();
                var bVal = $(b).find('[data-sort="' + column + '"]').text().trim();
                
                // Intentar convertir a número si es posible
                if (!isNaN(aVal) && !isNaN(bVal)) {
                    aVal = parseFloat(aVal);
                    bVal = parseFloat(bVal);
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            $tbody.empty().append(rows);
        }
        
        /**
         * Realizar acción en lote
         */
        function performBulkAction(action, ids) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vacantes_bulk_action',
                    bulk_action: action,
                    ids: ids,
                    nonce: vacantes_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        location.reload();
                    } else {
                        showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    showNotification('Error al realizar la acción', 'error');
                }
            });
        }
        
        /**
         * Mostrar notificación
         */
        function showNotification(message, type) {
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notification);
            
            setTimeout(function() {
                $notification.fadeOut();
            }, 5000);
        }
        
        /**
         * Validar email
         */
        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        /**
         * Debounce function
         */
        function debounce(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    });

})(jQuery);