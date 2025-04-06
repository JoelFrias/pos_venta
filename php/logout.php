<?php
session_start();

/**
 *  2. Auditoria de acciones de usuario
 */

require_once 'conexion.php';
require_once 'auditorias.php';
$usuario_id = $_SESSION['idEmpleado'];
$accion = 'Cierre de sesión';
$detalle = 'El usuario ' . $_SESSION['username'] . ' ha cerrado sesión.';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);


session_unset();
session_destroy();

header("Location: ../login.php");
exit();
?>
