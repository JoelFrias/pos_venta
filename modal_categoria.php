<?php
// Iniciar sesión (opcional, si se necesita manejar sesiones)
session_start();
require 'php/conexion.php';

// Verificar si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y limpiar la descripción del formulario
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    // Validar que la descripción no esté vacía
    if (empty($descripcion)) {
        $_SESSION['error'] = "La descripción no puede estar vacía.";
        header("Location: productos_nuevo.php"); // Redirigir de vuelta al formulario
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
    header("Location: productos_nuevo.php");
    exit;
}
?>