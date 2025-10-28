<div class="vacante-card">
    <div class="vacante-header">
        <h3 class="vacante-titulo"><?php echo esc_html($vacante->titulo); ?></h3>
        <span class="vacante-codigo"><?php echo esc_html($vacante->codigo); ?></span>
    </div>
    
    <div class="vacante-meta">
        <span class="vacante-direccion">
            <i class="icon-building"></i>
            <?php echo esc_html($vacante->direccion_nombre); ?>
        </span>
        <span class="vacante-tipo-contrato">
            <i class="icon-contract"></i>
            <?php echo esc_html($vacante->tipo_contrato_nombre); ?>
        </span>
        <span class="vacante-salario">
            <i class="icon-money"></i>
            Q. <?php echo number_format($vacante->salario, 2); ?>
        </span>
    </div>
    
    <div class="vacante-descripcion">
        <?php echo wp_trim_words($vacante->descripcion, 30, '...'); ?>
    </div>
    
    <div class="vacante-fechas">
        <small>
            Publicado: <?php echo date('d/m/Y', strtotime($vacante->fecha_publicacion)); ?>
            | Cierra: <?php echo date('d/m/Y', strtotime($vacante->fecha_cierre)); ?>
        </small>
    </div>
    
    <div class="vacante-acciones">
        <a href="#" class="btn btn-primary ver-detalle" data-vacante-id="<?php echo $vacante->id; ?>">
            Ver Detalles
        </a>
        <a href="#" class="btn btn-success aplicar-vacante" data-vacante-id="<?php echo $vacante->id; ?>">
            Aplicar Ahora
        </a>
    </div>
</div>