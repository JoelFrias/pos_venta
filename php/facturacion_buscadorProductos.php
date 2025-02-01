<?php

require 'conexion.php';

// Obtener y limpiar el parámetro
$campoProducto = isset($_POST['campoProducto']) ? '%' . $conn->real_escape_string($_POST['campoProducto']) . '%' : '%';

// Consulta preparada
$sqlProducto = "SELECT 
                    id, descripcion, existencia 
                FROM 
                    productos
                WHERE
                    (id LIKE ? OR descripcion LIKE ?)
                    AND activo = TRUE 
                ORDER BY
                    id ASC
                LIMIT 4";

$stmt = $conn->prepare($sqlProducto);
$stmt->bind_param('ss', $campoProducto, $campoProducto);
$stmt->execute();
$resultsProducto = $stmt->get_result();

$htmlProducto = '';

if ($resultsProducto->num_rows > 0) {
    while ($rowProducto = $resultsProducto->fetch_assoc()) {
        $htmlProducto .= '<tr>';
        $htmlProducto .= '<td>' . $rowProducto['id'] . '</td>';
        $htmlProducto .= '<td>' . $rowProducto['descripcion'] . '</td>';
        $htmlProducto .= '<td>' . $rowProducto['existencia'] . '</td>';
        $htmlProducto .= '<td><button onclick="selectProducto(' . $rowProducto['id'] . ')">Agregar</button></td>';
        $htmlProducto .= '</tr>';
    }
} else {
    $htmlProducto .= '<tr>';
    $htmlProducto .= '<td colspan="4">No se encontraron resultados.</td>';
    $htmlProducto .= '</tr>';
}

echo json_encode($htmlProducto, JSON_UNESCAPED_UNICODE);

$stmt->close();

?>
