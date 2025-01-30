<?php
include 'conexion.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && isset($_GET['precioSeleccionado']) && isset($_GET['cantidad'])) {
    $id = intval($_GET['id']);
    $precioSeleccionado = $_GET['precioSeleccionado'];  // 'precioVenta1' o 'precioVenta2'
    $cantidad = floatval($_GET['cantidad']);
    
    // Construir la consulta dinámica para seleccionar el precio adecuado
    $sql = "SELECT id, descripcion, existencia, $precioSeleccionado AS precio FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['error' => 'Error en la consulta']);
        exit;
    }

    $stmt->bind_param('i', $id);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $descripcion = $row['descripcion'];
        $existencia = $row['existencia'];
        $precio = $row['precio'];

        if ($existencia < $cantidad) {
            echo json_encode(['error' => 'No hay suficiente existencia']);
            exit;
        }

        // Calcular el total (importe)
        $total = $precio * $cantidad;

        // Crear el array con los datos del producto
        $producto = [
            'id' => $id,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'importe' => $total
        ];

        echo json_encode($producto);
        
    } else {
        echo json_encode(['error' => 'Producto no encontrado']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Los datos proporcionados no coinciden con los datos esperados']);
}
?>
