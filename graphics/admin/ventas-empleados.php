<?php

require_once '../../php/conexion.php';

header('Content-Type: application/json');

$sql = "SELECT
            CONCAT(e.nombre,' ',e.apellido) AS empleado,
            SUM(f.total_ajuste) AS ventas
        FROM
            facturas AS f
        JOIN empleados AS e
        ON
            f.idEmpleado = e.id
        WHERE
            MONTH(f.fecha) = MONTH(CURDATE()) AND YEAR(f.fecha) = YEAR(CURDATE())
        GROUP BY
            e.id,
            e.nombre
        ORDER BY
            ventas
        DESC";

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
