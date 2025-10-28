<div class="vacante-detalle-modal">
    <div class="vacante-header">
        <h2><?php echo esc_html($vacante->titulo); ?></h2>
        <div class="vacante-meta-grid">
            <div class="meta-item">
                <strong>Código:</strong>
                <span><?php echo esc_html($vacante->codigo); ?></span>
            </div>
            <div class="meta-item">
                <strong>Dirección:</strong>
                <span><?php echo esc_html($vacante->direccion_nombre); ?></span>
            </div>
            <div class="meta-item">
                <strong>Tipo de Contrato:</strong>
                <span><?php echo esc_html($vacante->tipo_contrato_nombre); ?></span>
            </div>
            <div class="meta-item">
                <strong>Salario:</strong>
                <span>Q. <?php echo number_format($vacante->salario, 2); ?></span>
            </div>
            <div class="meta-item">
                <strong>Fecha de Publicación:</strong>
                <span><?php echo date('d/m/Y', strtotime($vacante->fecha_publicacion)); ?></span>
            </div>
            <div class="meta-item">
                <strong>Fecha de Cierre:</strong>
                <span><?php echo date('d/m/Y', strtotime($vacante->fecha_cierre)); ?></span>
            </div>
        </div>
    </div>

    <div class="vacante-content">
        <div class="content-section">
            <h3>Descripción del Puesto</h3>
            <div class="content-text">
                <?php echo wpautop($vacante->descripcion); ?>
            </div>
        </div>

        <?php if ($vacante->requisitos): ?>
        <div class="content-section">
            <h3>Requisitos</h3>
            <div class="content-text">
                <?php echo wpautop($vacante->requisitos); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($vacante->beneficios): ?>
        <div class="content-section">
            <h3>Beneficios</h3>
            <div class="content-text">
                <?php echo wpautop($vacante->beneficios); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($vacante->bases_pdf): ?>
        <div class="content-section">
            <h3>Documentos</h3>
            <div class="documentos-list">
                <a href="<?php echo esc_url($vacante->bases_pdf); ?>" target="_blank" class="btn btn-outline">
                    <i class="icon-pdf"></i>
                    Descargar Bases del Concurso (PDF)
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="vacante-actions">
        <button type="button" class="btn btn-success aplicar-desde-detalle" data-vacante-id="<?php echo $vacante->id; ?>">
            <i class="icon-apply"></i>
            Aplicar a esta Vacante
        </button>
        
        <div class="tiempo-restante">
            <?php
            $fecha_cierre = new DateTime($vacante->fecha_cierre);
            $hoy = new DateTime();
            $diff = $hoy->diff($fecha_cierre);
            
            if ($fecha_cierre > $hoy) {
                if ($diff->days > 0) {
                    echo '<span class="tiempo-disponible">Quedan ' . $diff->days . ' días para aplicar</span>';
                } else {
                    echo '<span class="tiempo-urgente">¡Último día para aplicar!</span>';
                }
            } else {
                echo '<span class="tiempo-vencido">Convocatoria cerrada</span>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.vacante-detalle-modal {
    max-width: 100%;
}

.vacante-header h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
}

.vacante-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.meta-item {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid #007cba;
}

.meta-item strong {
    display: block;
    color: #495057;
    font-size: 0.9em;
    margin-bottom: 4px;
}

.meta-item span {
    color: #2c3e50;
    font-weight: 600;
}

.content-section {
    margin-bottom: 25px;
}

.content-section h3 {
    color: #495057;
    margin-bottom: 12px;
    font-size: 1.1em;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.content-text {
    line-height: 1.6;
    color: #495057;
}

.documentos-list {
    margin-top: 10px;
}

.btn-outline {
    background: transparent;
    border: 2px solid #007cba;
    color: #007cba;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-outline:hover {
    background: #007cba;
    color: white;
}

.vacante-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.aplicar-desde-detalle {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tiempo-restante {
    font-size: 0.9em;
}

.tiempo-disponible {
    color: #28a745;
    font-weight: 600;
}

.tiempo-urgente {
    color: #ffc107;
    font-weight: 600;
}

.tiempo-vencido {
    color: #dc3545;
    font-weight: 600;
}

@media (max-width: 768px) {
    .vacante-meta-grid {
        grid-template-columns: 1fr;
    }
    
    .vacante-actions {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.aplicar-desde-detalle').on('click', function() {
        var vacanteId = $(this).data('vacante-id');
        $('#modal-detalle-vacante').hide();
        
        // Mostrar formulario de aplicación
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
            }
        });
    });
});
</script>