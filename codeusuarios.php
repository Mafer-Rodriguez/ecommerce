<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'dbcon.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (isset($_POST['delete'])) {

    $registro_id = $_POST['delete'];

    $stmt = $con->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $registro_id);
    $query_run = $stmt->execute();
    $stmt->close();

    if ($query_run) {
        $_SESSION['alert'] = [
            'message' => 'Usuario eliminado exitosamente',
            'title' => 'USUARIO ELIMINADO',
            'icon' => 'success'
        ];
    } else {
        $_SESSION['alert'] = [
            'message' => 'Notifica a soporte',
            'title' => 'ERROR AL ELIMINAR',
            'icon' => 'error'
        ];
    }
    header("Location: usuarios.php");
    exit(0);
}

if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $apellidopaterno = $_POST['apellidopaterno'];
    $apellidomaterno = $_POST['apellidomaterno'];
    $username = $_POST['username'];
    $password = $_POST['password']; // Puede venir vacío si no se quiere cambiar
    $rol = $_POST['rol'];
    $estatus = $_POST['estatus'];

    if (!empty($password)) {
        // Se actualiza también la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $con->prepare("
            UPDATE usuarios SET
                nombre = ?,
                apellidopaterno = ?,
                apellidomaterno = ?,
                username = ?,
                rol = ?,
                estatus = ?,
                password = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssi",
            $nombre,
            $apellidopaterno,
            $apellidomaterno,
            $username,
            $rol,
            $estatus,
            $hashed_password,
            $id
        );
    } else {
        // No se toca la contraseña
        $stmt = $con->prepare("
            UPDATE usuarios SET
                nombre = ?,
                apellidopaterno = ?,
                apellidomaterno = ?,
                username = ?,
                rol = ?,
                estatus = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssssi",
            $nombre,
            $apellidopaterno,
            $apellidomaterno,
            $username,
            $rol,
            $estatus,
            $id
        );
    }

    $query_run = $stmt->execute();
    $stmt->close();

    if ($query_run) {
        $_SESSION['alert'] = [
            'message' => 'Usuario editado exitosamente',
            'title' => 'USUARIO EDITADO',
            'icon' => 'success'
        ];
    } else {
        $_SESSION['alert'] = [
            'message' => 'Notifica a soporte',
            'title' => 'ERROR AL EDITAR',
            'icon' => 'error'
        ];
    }
    header("Location: usuarios.php");
    exit;
}


if (isset($_POST['save'])) {

    $nombre = $_POST['nombre'];
    $apellidopaterno = $_POST['apellidopaterno'];
    $apellidomaterno = $_POST['apellidomaterno'];
    $email = $_POST['username'];
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $estatus = "1";

    // Verificar el rol y asignar el nombre correspondiente
    if ($rol == 1) {
        $rol_nombre = "Administrador";
    } elseif ($rol == 2) {
        $rol_nombre = "Colaborador";
    } else {
        $rol_nombre = "Otro"; // Por si acaso el rol no es 1 ni 2
    }

    // Verificar si el correo ya existe
    $stmtCheck = $con->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows > 0) {
        $stmtCheck->close();

        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Este correo ya está registrado',
            'icon' => 'error'
        ];
        header("Location: usuarios.php");
        exit(0);
    }
    $stmtCheck->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmtInsert = $con->prepare("
        INSERT INTO usuarios (nombre, apellidopaterno, apellidomaterno, username, password, rol, estatus)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->bind_param(
        "sssssss",
        $nombre,
        $apellidopaterno,
        $apellidomaterno,
        $email,
        $hashed_password,
        $rol,
        $estatus
    );
    $query_run = $stmtInsert->execute();
    $stmtInsert->close();

    if ($query_run) {

        // Configuracion SMTP (leídas desde variables de entorno, NUNCA hardcodeadas)
        $host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $port = $_ENV['SMTP_PORT'] ?? 587;
        $smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $security = PHPMailer::ENCRYPTION_STARTTLS;

        if (empty($smtpUsername) || empty($smtpPassword)) {
            error_log('Las variables SMTP_USERNAME / SMTP_PASSWORD no se cargaron desde el .env. Verifica que el archivo .env exista en la raíz del proyecto.');
        }

        // Crear instancia PHPMailer
        $mail = new PHPMailer(true);

        // Configurar SMTP
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $security;

        // Configurar correo
        $mail->setFrom($smtpUsername, 'Ecommerce UTMA');
        $mail->addAddress($email);
        $mail->Subject = 'NUEVO USUARIO';
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        // Cuerpo del mensaje (SIN incluir la contraseña por seguridad)
        $cuerpo = '
            <html>
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: system-ui;text-align: justify;background-color: #e7e7e7;">
                <div style="max-width:500px;margin: 0 auto;">
                    <img style="width: 100%;background-color: #1e375c;" src="#" alt="Cintillo superior">
                <div style="padding: 0px 30px;padding-top: 35px;">
                    <p>Estimado/a ' . htmlspecialchars($nombre) . '</p>
                    <p>Tu cuenta para gestionar el catálogo de productos y servicios de Mi Empresa se creó exitosamente.</p>
                    <p>Por seguridad, establece tu contraseña desde el enlace que te compartirá el administrador, y no la compartas con nadie.</p>

                    <div style="padding: 3px 20px;background-color:#efefef;color:#000000;border-radius: 3px;margin: 50px 0px;text-align:left;">
                    <p style="margin-bottom: 0px;"><b>Conoce los detalles de tu cuenta:</b></p>
                    <div style="display: flex; flex-direction: column; margin: 0 auto;">
                        <div style="display: flex; flex-wrap: wrap;">
                            <p style="margin-right: 5px;margin-bottom: 0px;"><b>Nombre:</b></p>
                            <p style="flex: 2;margin-bottom: 0px;">' . htmlspecialchars($nombre . ' ' . $apellidopaterno . ' ' . $apellidomaterno) . '</p>
                        </div>
                    </div>

                    <p><b>Correo:</b> ' . htmlspecialchars($email) . '</p>
                    <p><b>Rol:</b> ' . htmlspecialchars($rol_nombre) . '</p>
                    </div>

                    <p style="text-align: center;margin-top:80px;margin-bottom:0px;">Atentamente</p>
                    <p style="text-align: center;margin-top:0px;margin-bottom:50px;"><b>Equipo administrativo</b></p>
                </div>
                <div style="background-color: #af3335;color: #ffffff;padding: 15px 15px;font-size: 10px;text-align: center;padding-bottom: 15px;margin-bottom: 25px;">
                    <p>Este correo es enviado de manera automática por nuestro sistema de respuesta rápida.</p>
                </div>
                </div>
            </body>

            </html>';

        $mail->Body = $cuerpo;

        $correoEnviado = false;

        try {
            $correoEnviado = $mail->send();
        } catch (Exception $e) {
            error_log('Error al enviar el correo: ' . $mail->ErrorInfo);
        }

        if ($correoEnviado) {
            $_SESSION['alert'] = [
                'title' => 'SOLICITUD EXITOSA',
                'message' => 'Revisa tu correo electrónico',
                'icon' => 'success'
            ];
        } else {
            $_SESSION['alert'] = [
                'title' => 'ERROR',
                'message' => 'El usuario se creó pero el correo no pudo enviarse',
                'icon' => 'warning'
            ];
        }

        header("Location: usuarios.php");
        exit(0);
    } else {
        $_SESSION['alert'] = [
            'title' => 'ERROR',
            'message' => 'Notifica a soporte',
            'icon' => 'error'
        ];
        header("Location: usuarios.php");
        exit(0);
    }
}