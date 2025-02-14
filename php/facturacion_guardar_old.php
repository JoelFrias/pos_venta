<?php

header("Content-Type: application/json");

/* Verificacion de sesion */

// Iniciar sesión
session_start();

// Configurar el tiempo de caducidad de la sesión
$inactivity_limit = 900; // 15 minutos en segundos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header('Location: login.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: login.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

require 'conexion.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

// Obtener y decodificar los datos JSON enviados
$data = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "error" => "Datos JSON inválidos"]);
    exit;
}

// Verificar y asignar valores predeterminados si no están presentes
$idCliente = $data["idCliente"] ?? 0;
$tipoFactura = $data["tipoFactura"] ?? "";
$formaPago = $data["formaPago"] ?? "";
$numeroTarjeta = $data["numeroTarjeta"] ?? "N/A";
$numeroAutorizacion = $data["numeroAutorizacion"] ?? "N/A";
$banco = $data["banco"] ?? "1";
$destino = $data["destino"] ?? "0";
$total = $data["total"] ?? 0;
$montoPagado = $data["montoPagado"] ?? $total;
$productos = $data["productos"] ?? [];
$balance;
$estado;

// Validar datos esenciales
if (!is_numeric($idCliente) || $idCliente <= 0) {
    echo json_encode(["success" => false, "error" => "El ID del cliente no es válido"]);
    exit;
}

if (!is_numeric($total) || $total < 0) {
    echo json_encode(["success" => false, "error" => "El total de la factura no es válido"]);
    exit;
}

if (!is_numeric($montoPagado) || $montoPagado < 0) {
    echo json_encode(["success" => false, "error" => "El monto pagado no es válido"]);
    exit;
}

if (!in_array($tipoFactura, ["credito", "contado"])) {
    echo json_encode(["success" => false, "error" => "El tipo de factura no es válido"]);
    exit;
}

if (!is_array($productos) || empty($productos)) {
    echo json_encode(["success" => false, "error" => "No se han proporcionado productos válidos"]);
    exit;
}

foreach ($productos as $producto) {
    if (!isset($producto["id"]) || !isset($producto["cantidad"]) || !isset($producto["precio"]) || !isset($producto["venta"]) || !isset($producto["subtotal"])) {
        echo json_encode(["success" => false, "error" => "Estructura de producto inválida"]);
        exit;
    }

    if ($producto["cantidad"] <= 0 || $producto["precio"] < 0 || $producto["venta"] < 0 || $producto["subtotal"] < 0) {
        echo json_encode(["success" => false, "error" => "Datos de producto inválidos"]);
        exit;
    }
}

// Calcular el balance
if ($tipoFactura === "credito" && $montoPagado == 0) {
    $balance = $total; // Si es a crédito y no hay pago, el balance es igual al total
} else {
    $balance = max(0, $total - $montoPagado); // Asegurar que el balance no sea negativo
}

// Evaluar el estado
if ($balance > 0) {
    $estado = "Pendiente";
} else {
    $estado = "Pagada";
}

// Generar número de factura
if (!isset($_SESSION["idEmpleado"]) || !is_numeric($_SESSION["idEmpleado"])) {
    echo json_encode(["success" => false, "error" => "ID de empleado no válido"]);
    exit;
}

$numeroFactura = $_SESSION["idEmpleado"] . numFactura($conn);

if (!$numeroFactura) {
    echo json_encode(["success" => false, "error" => "No se pudo generar el número de factura"]);
    exit;
}

// Verificar si el cliente tiene más de 2 facturas pendientes a crédito
if (FacturasPendientes($conn, $idCliente) && $tipoFactura === "credito") {
    echo json_encode(["success" => false, "error" => "El cliente tiene más de 2 facturas pendientes a crédito. No se puede realizar la factura a crédito."]);
    exit;
}

// Iniciar transacción principal
$conn->begin_transaction();

try {
    guardarFactura($conn, $numeroFactura, $tipoFactura, $total, $balance, $total, $total - $montoPagado, $idCliente, $estado);
    guardarDetallesFactura($conn, $numeroFactura, $productos);
    actualizarInventario($conn, $productos);
    guardarMetodoPago($conn, $numeroFactura, $formaPago, $montoPagado, $numeroAutorizacion, $numeroTarjeta, $banco, $destino);
    actualizarBalanceCliente($conn, $idCliente);
    actualizarNumeroFactura($conn);

    // Confirmar la transacción
    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Factura procesada correctamente"
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al procesar la factura: " . $e->getMessage()); // Registrar el error en el log
    echo json_encode(["success" => false, "error" => "Error al procesar la factura: " . $e->getMessage()]);
    exit;
}

// Funciones auxiliares

/**
 * Verifica si el cliente tiene más de 2 facturas pendientes a crédito.
 */
function FacturasPendientes($conn, $idCliente) {
    $sql = "SELECT COUNT(*) AS facturasPendientes FROM facturas WHERE balance > 0 AND idCliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $data = $resultado->fetch_assoc();

    return $data['facturasPendientes'] >= 2;
}

/**
 * Obtiene elnúmero de factura desde la base de datos.
 */
function numFactura($conn) {
    $sql = "SELECT num FROM numFactura LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt->execute()) {
        throw new Exception("Error al obtener el número de factura: " . $stmt->error);
    }

    $resultado = $stmt->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        return $fila["num"];
    }

    throw new Exception("No se encontró un número de factura válido");
}

/**
 * Actualiza el número de factura en la base de datos.
 */
function actualizarNumeroFactura($conn) {
    $conn->begin_transaction();

    try {
        $ultimoNumero = numFactura($conn);
        $nuevoNumero = str_pad((int)$ultimoNumero + 1, strlen($ultimoNumero), '0', STR_PAD_LEFT);

        // Corregir INSERT por UPDATE
        $queryUpdate = "UPDATE numfactura SET num = ?";
        $stmtUpdate = $conn->prepare($queryUpdate);
        $stmtUpdate->bind_param("s", $nuevoNumero);
        $stmtUpdate->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Error al actualizar el número de factura: " . $e->getMessage());
    }
}

/**
 * Guarda la factura en la base de datos.
 */
function guardarFactura($conn, $numeroFactura, $tipoFactura, $importe, $descuento, $total, $balance, $idCliente, $estado) {
    $sql = "INSERT INTO facturas (numFactura, tipoFactura, fecha, importe, descuento, total, total_ajuste, balance, idCliente, idEmpleado, estado) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdddddiis", $numeroFactura, $tipoFactura, $importe, $descuento, $total, $total, $balance, $idCliente, $_SESSION["idEmpleado"], $estado);

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar la factura: " . $stmt->error);
    }
}

/**
 * Guarda los detalles de la factura en la base de datos.
 */
function guardarDetallesFactura($conn, $numeroFactura, $productos) {
    foreach ($productos as $producto) {
        $ganancias = $producto["venta"] - $producto["precio"];

        $sql = "INSERT INTO factura_detalles (numFactura, idProducto, cantidad, precioCompra, precioVenta, importe, ganancias, fecha) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siddddd", $numeroFactura, $producto["id"], $producto["cantidad"], $producto["precio"], $producto["venta"], $producto["subtotal"], $ganancias);

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar los detalles de la factura: " . $stmt->error);
        }
    }
}

/**
 * Actualiza el inventario después de la venta.
 */
function actualizarInventario($conn, $productos) {
    $conn->begin_transaction();

    try {
        foreach ($productos as $producto) {
            $producto_id = $producto["id"];
            $cantidad = $producto["cantidad"];

            $queryUpdate = "UPDATE inventarioempleados SET cantidad = cantidad - ? WHERE idProducto = ?";
            $stmtUpdate = $conn->prepare($queryUpdate);
            $stmtUpdate->bind_param("di", $cantidad, $producto_id);
            $stmtUpdate->execute();

            $queryUpdate = "UPDATE productos SET existencia = existencia - ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($queryUpdate);
            $stmtUpdate->bind_param("di", $cantidad, $producto_id);
            $stmtUpdate->execute();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Error al actualizar el inventario: " . $e->getMessage());
    }
}

/**
 * Guarda el método de pago utilizado en la factura.
 */
function guardarMetodoPago($conn, $numeroFactura, $formaPago, $montoPagado, $numeroAutorizacion, $numeroTarjeta, $banco, $destino) {
    $sql = "INSERT INTO facturas_metodoPago (numFactura, metodo, monto, numAutorizacion, referencia, idBanco, idDestino) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsdii", $numeroFactura, $formaPago, $montoPagado, $numeroAutorizacion, $numeroTarjeta, $banco, $destino);

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el método de pago: " . $stmt->error);
    }
}

/**
 * Actualiza el balance pendiente del cliente.
 */
function actualizarBalanceCliente($conn, $idCliente) {
    $queryFacturas = "SELECT SUM(balance) AS totalPendiente FROM facturas WHERE idCliente = ?";
    $stmtFacturas = $conn->prepare($queryFacturas);
    $stmtFacturas->bind_param("i", $idCliente);
    $stmtFacturas->execute();
    $resultadoFacturas = $stmtFacturas->get_result();
    $facturaData = $resultadoFacturas->fetch_assoc();

    $totalPendiente = $facturaData['totalPendiente'] ?? 0;

    $queryUpdateCliente = "UPDATE clientes_cuenta SET balance = ? WHERE idCliente = ?";
    $stmtUpdateCliente = $conn->prepare($queryUpdateCliente);
    $stmtUpdateCliente->bind_param("di", $totalPendiente, $idCliente);
    $stmtUpdateCliente->execute();
}