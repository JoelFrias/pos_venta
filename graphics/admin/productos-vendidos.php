<?php

require_once '../../php/conexion.php';

header('Content-Type: application/json');

$sql = "SELECT
            p.descripcion AS producto,
            SUM(d.cantidad) AS total_vendido
        FROM
            facturas_detalles AS d
        JOIN productos AS p
        ON
            d.idProducto = p.id
        JOIN facturas AS f
        ON
            d.numFactura = f.numFactura
        WHERE
            MONTH(f.fecha) = MONTH(CURDATE()) AND YEAR(f.fecha) = YEAR(CURDATE())
        GROUP BY
            p.id,
            p.descripcion
        ORDER BY
            total_vendido
        DESC
        LIMIT 10"; // Obtener los 10 productos más vendidos

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
