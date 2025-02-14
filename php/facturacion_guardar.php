<?php
session_start();
require_once 'conexion.php';

function logDebug($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}
// Validar metodo de entrada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(["success" => false, "error" => "Método no permitido"]));
}

// Verificar autenticación y permisos
if (!isset($_SESSION['username']) || empty($_SESSION['username']) || !isset($_SESSION['idEmpleado']) || empty($_SESSION['idEmpleado'])) {
    die(json_encode(['success' => false, 'error' => 'Acceso no autorizado']));
}

// Obtener datos JSON
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

logDebug("Datos recibidos", $data);

// Verificar si el JSON es válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'JSON inválido',
        'details' => json_last_error_msg()
    ]));
}

header('Content-Type: application/json');

try {
    // Validaciones esenciales
    $requiredFields = ['idCliente', 'tipoFactura', 'formaPago', 'total', 'productos'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception("Campos requeridos faltantes: " . implode(', ', $missingFields));
    }

    // Sanitización y asignación de variables
    $idCliente = (int)$data['idCliente'];
    $tipoFactura = $conn->real_escape_string($data['tipoFactura']);
    $formaPago = $conn->real_escape_string($data['formaPago']);
    $total = (float)$data['total'];
    $productos = $data['productos'];
    $montoPagado = isset($data['montoPagado']) ? (float)$data['montoPagado'] : $total;
    $numeroAutorizacion = $data['numeroAutorizacion'] ?? 'N/A';
    $numeroTarjeta = $data['numeroTarjeta'] ?? 'N/A';
    $banco = isset($data['banco']) ? (int)$data['banco'] : 1;
    $destino = isset($data['destino']) ? (int)$data['destino'] : 0;

    logDebug("Variables procesadas", [
        'idCliente' => $idCliente,
        'tipoFactura' => $tipoFactura,
        'formaPago' => $formaPago,
        'total' => $total,
        'montoPagado' => $montoPagado
    ]);

    $conn->begin_transaction();
    logDebug("Transacción iniciada");

    // 1. Verificar facturas pendientes
    $stmt = $conn->prepare("SELECT COUNT(*) AS pendientes FROM facturas WHERE balance > 0 AND idCliente = ?");
    if (!$stmt) {
        throw new Exception("Error preparando consulta de facturas pendientes: " . $conn->error);
    }
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    logDebug("Facturas pendientes", $result);
    
    if ($result['pendientes'] > 2 && $tipoFactura === 'credito') {
        throw new Exception("Cliente ID $idCliente tiene más de 2 facturas pendientes");
    }

    // 2. Obtener número de factura
    $stmt = $conn->prepare("SELECT num FROM numFactura LIMIT 1 FOR UPDATE");
    if (!$stmt) {
        throw new Exception("Error preparando consulta de número de factura: " . $conn->error);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if (!$fila = $resultado->fetch_assoc()) {
        throw new Exception('Error al obtener el número de factura');
    }

    $numFactura = $_SESSION['idEmpleado'] . $fila['num'];
    $nuevoNumero = str_pad((int)$fila['num'] + 1, strlen($fila['num']), '0', STR_PAD_LEFT);
    logDebug("Número de factura generado", ['numFactura' => $numFactura, 'nuevoNumero' => $nuevoNumero]);
    
    $stmtUpdate = $conn->prepare("UPDATE numFactura SET num = ?");
    if (!$stmtUpdate) {
        throw new Exception("Error preparando actualización de número de factura: " . $conn->error);
    }
    $stmtUpdate->bind_param("s", $nuevoNumero);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error actualizando número de factura: " . $stmtUpdate->error);
    }
    logDebug("Número de factura actualizado");

    // 3. Insertar factura principal
    $balance = ($tipoFactura === 'credito') ? max(0, $total - $montoPagado) : 0;
    $estado = ($balance > 0) ? 'Pendiente' : 'Pagada';

    $query = "INSERT INTO facturas (numFactura, tipoFactura, fecha, importe, descuento, total, total_ajuste, balance, idCliente, idEmpleado, estado) 
              VALUES (?, ?, NOW(), ?, 0, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparando inserción de factura: " . $conn->error);
    }
    $stmt->bind_param('ssddddiis', $numFactura, $tipoFactura, $total, $total, $total, $balance, $idCliente, $_SESSION['idEmpleado'], $estado);
    if (!$stmt->execute()) {
        throw new Exception("Error insertando factura: " . $stmt->error);
    }
    logDebug("Factura principal insertada", [
        'numFactura' => $numFactura,
        'total' => $total,
        'balance' => $balance,
        'estado' => $estado
    ]);

    // 4. Insertar detalles de productos
    $stmt = $conn->prepare("INSERT INTO facturas_detalles (numFactura, idProducto, cantidad, precioCompra, precioVenta, importe, ganancias, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception("Error preparando inserción de detalles: " . $conn->error);
    }

    foreach ($productos as $producto) {
        $ganancia = $producto['venta'] - $producto['precio'];
        $stmt->bind_param('siidddd', $numFactura, $producto['id'], $producto['cantidad'], $producto['precio'], $producto['venta'], $producto['subtotal'], $ganancia);
        if (!$stmt->execute()) {
            throw new Exception("Error insertando detalle de producto {$producto['id']}: " . $stmt->error);
        }
        logDebug("Detalle de producto insertado", $producto);
    }

    // 5. Actualizar inventario
    $stmt = $conn->prepare("UPDATE productos SET existencia = existencia - ? WHERE id = ? AND existencia >= ?");
    if (!$stmt) {
        throw new Exception("Error preparando actualización de inventario: " . $conn->error);
    }
    
    foreach ($productos as $producto) {
        $stmt->bind_param('dii', $producto['cantidad'], $producto['id'], $producto['cantidad']);
        if (!$stmt->execute()) {
            throw new Exception("Error actualizando inventario para producto {$producto['id']}: " . $stmt->error);
        }
        if ($stmt->affected_rows === 0) {
            throw new Exception("Stock insuficiente para producto ID: " . $producto['id']);
        }
        logDebug("Inventario actualizado", $producto);
    }

    // 6. Registrar método de pago
    $stmt = $conn->prepare("INSERT INTO facturas_metodoPago (numFactura, metodo, monto, numAutorizacion, referencia, idBanco, idDestino) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Error preparando inserción de método de pago: " . $conn->error);
    }
    $stmt->bind_param('ssdssii', $numFactura, $formaPago, $montoPagado, $numeroAutorizacion, $numeroTarjeta, $banco, $destino);
    if (!$stmt->execute()) {
        throw new Exception("Error insertando método de pago: " . $stmt->error);
    }
    logDebug("Método de pago registrado");

    // 7. Actualizar balance del cliente
    $stmt = $conn->prepare("UPDATE clientes_cuenta SET balance = (SELECT IFNULL(SUM(balance), 0) FROM facturas WHERE idCliente = ?) WHERE idCliente = ?");
    if (!$stmt) {
        throw new Exception("Error preparando actualización de balance: " . $conn->error);
    }
    $stmt->bind_param('ii', $idCliente, $idCliente);
    if (!$stmt->execute()) {
        throw new Exception("Error actualizando balance del cliente: " . $stmt->error);
    }
    logDebug("Balance del cliente actualizado");

    // 8. Confirmar la transacción
    $conn->commit();
    logDebug("Transacción completada exitosamente");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Factura procesada correctamente',
        'numFactura' => $numFactura,
        'detalles' => [
            'total' => $total,
            'balance' => $balance,
            'estado' => $estado
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    logDebug("ERROR: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'errorCode' => $e->getCode()
    ]);
} finally {
    if ($conn) {
        $conn->close();
    }
    exit;
}