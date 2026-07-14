<?php
session_start();
require 'dbcon.php';

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM usuarios WHERE username='$username' LIMIT 1";
    $query_run = mysqli_query($conn, $query);

    if (mysqli_num_rows($query_run) > 0) {

        $usuario = mysqli_fetch_assoc($query_run);

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

}