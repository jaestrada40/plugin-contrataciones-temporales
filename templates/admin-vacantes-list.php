<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>
        Gestión de Vacantes
        <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=add'); ?>" class="btn btn-primary">
            Nueva Vacante
        </a>
    </h1>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Dirección</th>
                        <th>Tipo Contrato</th>
                        <th>Fecha Límite</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vacantes)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay vacantes registradas</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($vacantes as $vacante): ?>
                    <tr>
                        <td><?php echo esc_html($vacante->codigo); ?></td>
                        <td><?php echo esc_html($vacante->titulo); ?></td>
                        <td><?php echo esc_html($vacante->direccion_nombre); ?></td>
                        <td><?php echo esc_html($vacante->tipo_contrato_nombre); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($vacante->fecha_limite)); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $vacante->estado === 'Activa' ? 'success' : 'secondary'; ?>">
                                <?php echo esc_html($vacante->estado); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=edit&id=' . $vacante->id); ?>" 
                               class="btn btn-sm btn-outline-primary">Editar</a>
                            <button onclick="deleteVacante(<?php echo $vacante->id; ?>)" 
                                    class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteVacante(id) {
    if (confirm('¿Está seguro de eliminar esta vacante?')) {
        jQuery.post(vacantesAjax.ajaxUrl, {
            action: 'delete_vacante',
            vacante_id: id,
            nonce: vacantesAjax.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        });
    }
}
</script>