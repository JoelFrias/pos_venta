<?php

require_once '../../php/conexion.php';

header('Content-Type: application/json');

$sql = "SELECT
            DAY(f.fecha) AS dia,
            SUM(f.total_ajuste) AS ventas
        FROM
            facturas AS f
        WHERE
            MONTH(f.fecha) = MONTH(CURDATE()) AND YEAR(f.fecha) = YEAR(CURDATE())
        GROUP BY
            dia
        ORDER BY
            dia";

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
