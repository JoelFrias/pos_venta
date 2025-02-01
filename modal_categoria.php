<?php
// Habilitar manejo de sesiones (opcional, si necesitas manejar sesiones)
session_start();
require 'php/conexion.php';

// Obtener los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    // Validar que el campo no esté vacío
    if (empty($descripcion)) {
        $_SESSION['error'] = "La descripción no puede estar vacía.";
        header("Location: productos_nuevo.php"); // Redirigir de vuelta al formulario
        exit;
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO productos_tipo (descripcion) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $descripcion);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Categoría registrada correctamente.";
    } else {
        $_SESSION['error'] = "Error al registrar la categoría: " . $stmt->error;
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();

    // Redirigir de vuelta al formulario
    header("Location: productos_nuevo.php");
    exit;
}
?>