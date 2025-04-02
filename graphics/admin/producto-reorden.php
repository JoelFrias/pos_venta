<?php

require_once '../../php/conexion.php'; // Conexión a la base de datos

header('Content-Type: application/json'); // Indicamos que el contenido es JSON

$sql = "SELECT
            p.descripcion AS producto,
            p.existencia AS stock,
            p.reorden AS stock_minimo
        FROM
            productos AS p
        WHERE
            p.existencia <= p.reorden
        AND
            p.activo = TRUE
        ORDER BY
            p.existencia ASC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$conn->close();

?>
