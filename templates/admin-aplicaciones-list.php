<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>Gestión de Aplicaciones</h1>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Candidato</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Vacante</th>
                        <th>Fecha Aplicación</th>
                        <th>Estado</th>
                        <th>CV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aplicaciones)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay aplicaciones registradas</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($aplicaciones as $aplicacion): ?>
                    <tr>
                        <td><?php echo esc_html($aplicacion->nombre_completo); ?></td>
                        <td><?php echo esc_html($aplicacion->email); ?></td>
                        <td><?php echo esc_html($aplicacion->telefono); ?></td>
                        <td>
                            <strong><?php echo esc_html($aplicacion->vacante_titulo); ?></strong><br>
                            <small class="text-muted"><?php echo esc_html($aplicacion->vacante_codigo); ?></small>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($aplicacion->fecha_aplicacion)); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $aplicacion->estado === 'Pendiente' ? 'warning' : 
                                    ($aplicacion->estado === 'Aprobado' ? 'success' : 'danger'); 
                            ?>">
                                <?php echo esc_html($aplicacion->estado); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($aplicacion->cv_url): ?>
                            <a href="<?php echo esc_url($aplicacion->cv_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                Ver CV
                            </a>
                            <?php else: ?>
                            <span class="text-muted">Sin CV</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>