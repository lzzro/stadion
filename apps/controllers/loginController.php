<?php
# =====================================================================
# Controlador: inicio de sesion
# Proyecto SGDM - Stadion (Agon) - Lucas Martiarena
# ---------------------------------------------------------------------
# Recibe por POST el formulario de "iniciar sesion" de
# public/login.html, busca el usuario por correo y comprueba la clave
# con verificarClave(), que usa password_verify().
#
# NO DADO EN CLASE: las sesiones ($_SESSION). Se usan igual, de la
# forma mas simple posible, porque sin ellas "iniciar sesion" no
# significaria nada: la pagina siguiente no tendria como saber quien
# entro. Solo se guarda el id del usuario y su nombre para saludarlo;
# ni la clave ni el hash pasan por la sesion.
#
# El mensaje de error es el mismo cuando el correo no existe y cuando
# la clave no coincide. Es a proposito: si fueran distintos, cualquiera
# podria averiguar que correos tienen cuenta probando de a uno.
# =====================================================================

session_start();

require_once '../config/database.php';
require_once '../models/Usuario.php';
require_once '../models/UsuarioRepositorio.php';

$titulo  = 'Inicio de sesion';
$mensaje = '';
$errores = array();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errores[] = 'El inicio de sesion llega desde el formulario de la pagina de acceso.';
} else {

    $correo = isset($_POST['correo'])   ? trim($_POST['correo']) : '';
    $clave  = isset($_POST['password']) ? $_POST['password']     : '';

    if (empty($correo) || empty($clave)) {
        $errores[] = 'El correo y la contrasena son obligatorios.';
    }

    if (empty($errores)) {
        $conexion = conectarBD();
        if ($conexion === null) {
            $errores[] = $error_bd;
        } else {
            $repositorio = new UsuarioRepositorio($conexion);
            $usuario = $repositorio->buscarPorCorreo($correo);

            if ($usuario === null || !$usuario->verificarClave($clave)) {
                $errores[] = 'El correo o la contrasena no coinciden.';
            } elseif (!$usuario->estaActivo()) {
                $errores[] = 'Esa cuenta esta dada de baja.';
            } else {
                $_SESSION['id_usuario'] = $usuario->getIdUsuario();
                $_SESSION['nombre']     = $usuario->getNombre();
                $mensaje = 'La sesion queda abierta a nombre de '
                         . $usuario->getNombreCompleto() . '.';
            }
            $conexion->close();
        }
    }
}

include('../index.php');
