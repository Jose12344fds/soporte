<?php
$page_title = 'Inicio';
require_once('includes/load.php');
require_once('config/app.php');

if (!$session->isUserLoggedIn(true)) { 
    redirect('index.php', false);
}

$user = current_user();
include_once('layouts/header.php'); 
?>

        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-home mr-2"></i>Inicio</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php echo display_msg($msg); ?>
                
                <!-- Welcome Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="jumbotron text-center bg-light">
                            <h1 class="display-4">
                                <i class="fas fa-user-circle text-primary"></i>
                                Bienvenido, <?php echo htmlspecialchars($user['name']); ?>
                            </h1>
                            <p class="lead">Sistema de Soporte TI - TAMA</p>
                            <hr class="my-4">
                            <p>Seleccione una opción del menú lateral para comenzar.</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<?php include_once('layouts/footer.php'); ?>


