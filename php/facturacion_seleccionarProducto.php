<?php

include 'conexion.php';

header('Content-Type: application/json'); // Asegura que la respuesta sea JSON

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sqlP = "SELECT id,descripcion,precioVenta1,precioVenta2 FROM productos WHERE id = ?";
    $stmtP = $conn->prepare($sqlP);

    if (!$stmtP) {
        echo json_encode(['error' => 'Error en la preparación de la consulta']);
        exit;
    }

    $stmtP->bind_param('i', $id);
    $stmtP->execute();
    $resultP = $stmtP->get_result();

    if ($rowP = $resultP->fetch_assoc()) {
        echo json_encode($rowP, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => 'No se encontró el producto']);
    }

    $stmtP->close();
} else {
    echo json_encode(['error' => 'ID no proporcionado']);
}

?>
