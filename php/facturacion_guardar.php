<?php
session_start();

// Incluir archivo de conexión a base de datos
require 'conexion.php';

// Verificar si se reciben los datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtenemos los datos del formulario e inicializamos las variables
    $numFactura = numFactura($conn);
    $tipoFactura = htmlspecialchars($_POST['tipoFactura']);
    $importe = htmlspecialchars($_POST['importe']);
    $descuento = htmlspecialchars($_POST['descuento']);
    $total = htmlspecialchars($_POST['total']);
    $totalAjuste = htmlspecialchars($_POST['totalAjuste']);
    $balance = htmlspecialchars($_POST['balance']);
    $idCliente = htmlspecialchars($_POST['idCliente']);
    $idEmpleado = ""; // Debes obtener este valor de alguna manera
    $ganancias = htmlspecialchars($_POST['ganancias']);
    $estado = ""; // Definir el estado de la factura

    try {
        // Iniciamos la transacción
        $conn->begin_transaction();

        // Guardamos la factura
        $sql_factura = "INSERT INTO factura (numFactura, tipoFactura, fecha, importe, descuento, total, totalAjuste, balance, idCliente, idEmpleado, ganancias, estado) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_factura = $conn->prepare($sql_factura);
        $stmt_factura->bind_param("sssssssssss", $numFactura, $tipoFactura, $importe, $descuento, $total, $totalAjuste, $balance, $idCliente, $idEmpleado, $ganancias, $estado);

        // Ejecutamos la consulta
        $stmt_factura->execute();

        // Confirmamos la transacción
        $conn->commit();

        echo "Factura guardada correctamente.";

    } catch (Exception $e) {
        // Si hay un error, revertimos la transacción
        $conn->rollback();
        echo "Error al guardar la factura: " . $e->getMessage();
    }
}

// Función para obtener el número de factura
function numFactura($conn) {
    $sql = "SELECT num FROM numfactura LIMIT 1";
    $results = $conn->query($sql);

    if ($results->num_rows > 0) {
        $row = $results->fetch_assoc();
        return $row['num'];
    } else {
        throw new Exception("Error al obtener el número de factura");
    }
}

// Funcion para calcular las ganancias de factura
function calcularGanancias($conn) {
    
}
?>