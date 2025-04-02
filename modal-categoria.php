<?php

/* Verificacion de sesion */

// Iniciar sesión
session_start();

// Configurar el tiempo de caducidad de la sesión
$inactivity_limit = 900; // 15 minutos en segundos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header('Location: login.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: login.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

require 'php/conexion.php';

// Verificar si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y limpiar la descripción del formulario
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    // Validar que la descripción no esté vacía
    if (empty($descripcion)) {
        $_SESSION['error'] = "La descripción no puede estar vacía.";
        header("Location: productos-nuevo.php"); // Redirigir de vuelta al formulario
        exit;
    }

    // Preparar la consulta para insertar en la base de datos
    $sql = "INSERT INTO productos_tipo (descripcion) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $descripcion);

    // Ejecutar la consulta y verificar el resultado
    if ($stmt->execute()) {
        $_SESSION['success'] = "Categoría registrada correctamente.";
    } else {
        $_SESSION['error'] = "Error al registrar la categoría: " . $stmt->error;
    }

    // Cerrar la conexión y la declaración preparada
    $stmt->close();
    $conn->close();

    // Redirigir de vuelta al formulario
    header("Location: productos-nuevo.php");
    exit;
}
?>