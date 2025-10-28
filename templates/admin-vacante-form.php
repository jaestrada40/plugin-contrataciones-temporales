<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php echo $vacante ? 'Editar Vacante' : 'Nueva Vacante'; ?></h1>
    
    <div class="card">
        <div class="card-body">
            <form id="vacante-form">
                <?php if ($vacante): ?>
                <input type="hidden" name="vacante_id" value="<?php echo $vacante->id; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" class="form-control" 
                                   value="<?php echo esc_attr($vacante->codigo ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="Activa" <?php selected($vacante->estado ?? 'Activa', 'Activa'); ?>>Activa</option>
                                <option value="Pausada" <?php selected($vacante->estado ?? '', 'Pausada'); ?>>Pausada</option>
                                <option value="Cerrada" <?php selected($vacante->estado ?? '', 'Cerrada'); ?>>Cerrada</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" name="titulo" class="form-control" 
                           value="<?php echo esc_attr($vacante->titulo ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="4"><?php echo esc_textarea($vacante->descripcion ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Requisitos</label>
                    <textarea name="requisitos" class="form-control" rows="4"><?php echo esc_textarea($vacante->requisitos ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Salario Mínimo</label>
                            <input type="number" name="salario_min" class="form-control" step="0.01"
                                   value="<?php echo esc_attr($vacante->salario_min ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Salario Máximo</label>
                            <input type="number" name="salario_max" class="form-control" step="0.01"
                                   value="<?php echo esc_attr($vacante->salario_max ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Dirección *</label>
                            <select name="direccion_id" class="form-select" required>
                                <option value="">Seleccionar dirección</option>
                                <?php foreach ($direcciones as $direccion): ?>
                                <option value="<?php echo $direccion->id; ?>" 
                                        <?php selected($vacante->direccion_id ?? '', $direccion->id); ?>>
                                    <?php echo esc_html($direccion->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Contrato *</label>
                            <select name="tipo_contrato_id" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <?php foreach ($tipos_contrato as $tipo): ?>
                                <option value="<?php echo $tipo->id; ?>" 
                                        <?php selected($vacante->tipo_contrato_id ?? '', $tipo->id); ?>>
                                    <?php echo esc_html($tipo->nombre); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Fecha Límite *</label>
                    <input type="date" name="fecha_limite" class="form-control" 
                           value="<?php echo esc_attr($vacante->fecha_limite ?? ''); ?>" required>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="<?php echo admin_url('admin.php?page=vacantes-list'); ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#vacante-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_vacante&nonce=' + vacantesAjax.nonce;
        
        $.post(vacantesAjax.ajaxUrl, formData, function(response) {
            if (response.success) {
                alert('Vacante guardada correctamente');
                window.location.href = '<?php echo admin_url('admin.php?page=vacantes-list'); ?>';
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
</script>