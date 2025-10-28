<style>
/* FORZAR estilos de tabla minimalista */
table.table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 20px 0 !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    background-color: white !important;
    border: 1px solid #e9ecef !important;
}

table.table th {
    background-color: #f8f9fa !important;
    border: 1px solid #e9ecef !important;
    padding: 8px 12px !important;
    text-align: center !important;
    font-weight: 400 !important;
    color: #6c757d !important;
    font-size: 13px !important;
}

table.table td {
    border: 1px solid #e9ecef !important;
    padding: 8px 12px !important;
    text-align: center !important;
    vertical-align: middle !important;
    font-size: 13px !important;
    color: #495057 !important;
    background-color: white !important;
}

table.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02) !important;
}

table.table tbody tr:hover td {
    background-color: rgba(0,0,0,0.02) !important;
}

h4 {
    color: #333;
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 20px;
    font-weight: normal;
}

/* Cargar FontAwesome si no está cargado */
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

.vacantes-lista-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.vacantes-filtros {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.vacantes-filtros-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: end;
}

.filtro-grupo {
    display: flex;
    flex-direction: column;
    min-width: 200px;
}

.filtro-grupo label {
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.filtro-grupo input,
.filtro-grupo select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

#btn-buscar-vacantes {
    padding: 8px 20px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

#btn-buscar-vacantes:hover {
    background: #005a87;
}

.no-vacantes {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    color: #666;
}

@media (max-width: 768px) {
    .vacantes-filtros-form {
        flex-direction: column;
    }
    
    .filtro-grupo {
        min-width: 100%;
    }
    
    .table {
        font-size: 14px;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
    }
}
</style>

<div class="vacantes-lista-container">
    <div class="vacantes-filtros">
        <form class="vacantes-filtros-form">
            <div class="filtro-grupo">
                <label for="buscar-termino">Buscar:</label>
                <input type="text" id="buscar-termino" placeholder="Título, descripción...">
            </div>
            
            <div class="filtro-grupo">
                <label for="filtro-direccion">Dirección:</label>
                <select id="filtro-direccion">
                    <option value="">Todas las direcciones</option>
                    <?php
                    $direccion_model = new Direccion_Model();
                    $direcciones = $direccion_model->get_all();
                    foreach ($direcciones as $direccion) {
                        echo '<option value="' . $direccion->id . '">' . esc_html($direccion->nombre) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="filtro-grupo">
                <label for="filtro-tipo-contrato">Tipo de Contrato:</label>
                <select id="filtro-tipo-contrato">
                    <option value="">Todos los tipos</option>
                    <?php
                    // Para tipos de contrato usamos valores fijos ya que están en la misma tabla
                    $tipos_contrato = array(
                        'permanente' => 'Permanente',
                        'temporal' => 'Temporal',
                        'contrato' => 'Por Contrato',
                        'consultoria' => 'Consultoría'
                    );
                    foreach ($tipos_contrato as $valor => $nombre) {
                        echo '<option value="' . $valor . '">' . esc_html($nombre) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <button type="button" id="btn-buscar-vacantes">Buscar</button>
        </form>
    </div>

    <p>En esta sección se presentan las vacantes laborales disponibles en el Ministerio de Finanzas Públicas, conforme a los lineamientos establecidos por la Dirección de Recursos Humanos.</p>

    <div class="vacantes-resultados" id="vacantes-resultados">
        <?php if (empty($vacantes)): ?>
            <div class="no-vacantes">
                <p>No hay vacantes disponibles en este momento.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Proponente</th>
                        <th>Propuesta</th>
                        <th>Institución receptora</th>
                        <th>Respuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $contador = 1;
                    foreach ($vacantes as $vacante): 
                    ?>
                    <tr>
                        <td><?php echo str_pad($contador, 2, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo esc_html($vacante->titulo); ?></td>
                        <td>
                            <a href="#" class="aplicar-vacante" data-vacante-id="<?php echo $vacante->id; ?>" target="_blank">
                                <i class="fa fa-download" style="color: #007cba; font-size: 18px;"></i>
                            </a>
                        </td>
                        <td><?php echo esc_html($vacante->direccion_nombre ?? 'Ministerio de Finanzas Públicas'); ?></td>
                        <td>
                            <a href="#" class="aplicar-vacante" data-vacante-id="<?php echo $vacante->id; ?>" target="_blank">
                                <i class="fa fa-file-text" style="color: #007cba; font-size: 18px;"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                    $contador++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="vacantes-paginacion">
        <!-- Paginación se agregará aquí -->
    </div>
</div>

<!-- Modal para detalles de vacante -->
<div id="modal-detalle-vacante" class="vacantes-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div id="modal-detalle-content">
            <!-- Contenido se carga dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal para aplicar -->
<div id="modal-aplicar-vacante" class="vacantes-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div id="modal-aplicar-content">
            <!-- Contenido se carga dinámicamente -->
        </div>
    </div>
</div>