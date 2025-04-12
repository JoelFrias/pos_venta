<?php
require_once '../../models/conexion.php';
session_start();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['username'])) {
        header('Location: ../../views/auth/login.php');
        exit();
    }

    $id_producto = $_POST['id_producto'];
    $cantidad_a_devolver = $_POST['cantidad'];

    if (!is_numeric($cantidad_a_devolver) || $cantidad_a_devolver <= 0) {
        $mensaje = 'La cantidad no es válida.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT cantidad FROM inventario_personal WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                throw new Exception('Producto no encontrado en inventario personal.');
            }

            $row = $result->fetch_assoc();
            $cantidad_actual_personal = $row['cantidad'];

            if ($cantidad_a_devolver > $cantidad_actual_personal) {
                throw new Exception('Cantidad a devolver mayor a la disponible.');
            }

            $nueva_cantidad_personal = $cantidad_actual_personal - $cantidad_a_devolver;
            $stmt = $conn->prepare("UPDATE inventario_personal SET cantidad = ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $nueva_cantidad_personal, $id_producto);
            $stmt->execute();

            $stmt = $conn->prepare("SELECT cantidad FROM inventario_general WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                throw new Exception('Producto no encontrado en inventario general.');
            }

            $row = $result->fetch_assoc();
            $cantidad_actual_general = $row['cantidad'];
            $nueva_cantidad_general = $cantidad_actual_general + $cantidad_a_devolver;

            $stmt = $conn->prepare("UPDATE inventario_general SET cantidad = ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $nueva_cantidad_general, $id_producto);
            $stmt->execute();

            $conn->commit();
            $mensaje = 'Producto devuelto exitosamente.';
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolver Producto</title>
</head>
<body>
    <div class="card">
        <h2>Devolver Producto</h2>
        <?php if ($mensaje): ?>
            <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="id_producto">ID del Producto</label>
            <input type="number" name="id_producto" id="id_producto" required>

            <label for="cantidad">Cantidad a devolver</label>
            <input type="number" name="cantidad" id="cantidad" required>

            <button type="submit">Devolver</button>
        </form>
    </div>
</body>
</html>
