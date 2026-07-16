<?php
session_start();
require 'dbcon.php';

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $con->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $query_run = $stmt->get_result();

    if ($query_run->num_rows > 0) {

        $usuario = $query_run->fetch_assoc();

        // Verificar contraseña
        if (password_verify($password, $usuario['password'])) {

            // Verificar que el usuario esté activo
            if ($usuario['estatus'] == 1) {

                $_SESSION['username'] = $usuario['username'];
                $_SESSION['rol'] = $usuario['rol'];

                header("Location: usuarios.php");
                exit();
            } else {

                $_SESSION['alert'] = [
                    'title' => 'Usuario inactivo',
                    'message' => 'Tu cuenta está deshabilitada.',
                    'icon' => 'warning'
                ];

                header("Location: login.php");
                exit();
            }
        } else {

            $_SESSION['alert'] = [
                'title' => 'Error',
                'message' => 'Contraseña incorrecta.',
                'icon' => 'error'
            ];

            header("Location: login.php");
            exit();
        }
    } else {

        $_SESSION['alert'] = [
            'title' => 'Error',
            'message' => 'El usuario no existe.',
            'icon' => 'error'
        ];

        header("Location: login.php");
        exit();
    }

    $stmt->close();
}