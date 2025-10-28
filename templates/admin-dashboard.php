<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>Dashboard - Vacantes MINFIN</h1>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Vacantes</h5>
                    <h2><?php echo $stats['total_vacantes']; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Vacantes Activas</h5>
                    <h2><?php echo $stats['vacantes_activas']; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Aplicaciones</h5>
                    <h2><?php echo $stats['total_aplicaciones']; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Aplicaciones Pendientes</h5>
                    <h2><?php echo $stats['aplicaciones_pendientes']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo admin_url('admin.php?page=vacantes-list&action=add'); ?>" class="btn btn-primary">
                        Nueva Vacante
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=aplicaciones-list'); ?>" class="btn btn-info">
                        Ver Aplicaciones
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=direcciones-list'); ?>" class="btn btn-secondary">
                        Gestionar Direcciones
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>