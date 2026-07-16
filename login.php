<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: usuarios.php");
    exit();
}

$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;
unset($_SESSION['alert']); // Se borra en cuanto se lee, no importa si se usa o no
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-header">

                        <h4>Iniciar sesión</h4>

                    </div>

                    <div class="card-body">

                        <form action="codelogin.php" method="POST">

                            <div class="mb-3">
                                <label>Correo</label>
                                <input
                                    type="email"
                                    name="username"
                                    class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label>Contraseña</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    required>
                            </div>

                            <button
                                type="submit"
                                name="login"
                                class="btn btn-primary w-100">

                                Entrar

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($alert)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: <?= json_encode($alert['title'] ?? 'Notificación') ?>,
                    <?= !empty($alert['message']) ? 'text: ' . json_encode($alert['message']) . ',' : '' ?>
                    icon: <?= json_encode($alert['icon'] ?? 'info') ?>,
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>