<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>Gestión de Direcciones</h1>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($direcciones)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay direcciones registradas</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($direcciones as $direccion): ?>
                    <tr>
                        <td><?php echo esc_html($direccion->nombre); ?></td>
                        <td><?php echo esc_html($direccion->descripcion); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $direccion->activa ? 'success' : 'secondary'; ?>">
                                <?php echo $direccion->activa ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>