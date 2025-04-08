<?php
// cancelar_factura.php - Procesa la cancelación de factura
session_start();
require_once('conexion.php');

// Verificar si hay una sesión de usuario activa
if (!isset($_SESSION['idEmpleado'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión de usuario']);
    exit;
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener los datos del formulario
$numFactura = isset($_POST['numFactura']) ? intval($_POST['numFactura']) : 0;
$motivo = isset($_POST['motivo']) ? $conn->real_escape_string($_POST['motivo']) : '';
$idEmpleado = $_SESSION['idEmpleado'];

// Validaciones iniciales
if (empty($numFactura) || empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si la factura existe y no está ya cancelada
    $sql = "SELECT
                f.estado AS estado,
                f.total AS total
            FROM
                facturas AS f
            WHERE
                f.numFactura = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $numFactura);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('La factura no existe');
    }
    
    $factura = $result->fetch_assoc();
    
    if ($factura['estado'] === 'Cancelada') {
        throw new Exception('Esta factura ya ha sido cancelada');
    }

    $sqlCaja = "SELECT
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 
                            FROM cajasabiertas ca 
                            WHERE ca.numCaja = fm.noCaja
                        ) THEN 1
                        ELSE 0
                    END AS caja_abierta,
                    fm.metodo,
                    fm.noCaja
                FROM
                    facturas_metodopago AS fm
                WHERE
                    fm.numFactura = ?;";
    
    $stmtCaja = $conn->prepare($sqlCaja);
    $stmtCaja->bind_param("i", $numFactura);
    $stmtCaja->execute();
    $resultCaja = $stmtCaja->get_result();
    
    if ($resultCaja->num_rows === 0) {
        throw new Exception('Factura no encontrada en metodo de pago');
    }
    $cajaFactura = $resultCaja->fetch_assoc();
    
    // Verificar si la caja con la que se cobró sigue activa
    $cajaActiva = ($cajaFactura['caja_abierta'] == 1);
    
    // Registrar la cancelación en la tabla facturas_cancelaciones
    $sql = "INSERT INTO facturas_cancelaciones (numFactura, motivo, fecha, idEmpleado) 
            VALUES (?, ?, NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $numFactura, $motivo, $idEmpleado);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al registrar la cancelación');
    }
    
    // Cambiar el estado de la factura a "Cancelada"
    $sql = "UPDATE facturas SET estado = 'Cancelada' WHERE numFactura = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $numFactura);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el estado de la factura');
    }
    
    // Si la caja sigue activa y el metodo fue efectivo, registrar un egreso
    if ($cajaActiva && $cajaFactura['metodo'] == 'efectivo') {
        $monto = $factura['total'];
        $concepto = "Devolución por cancelación de factura #" . $numFactura;
        
        $sql = "INSERT INTO cajaegresos (metodo, monto, idEmpleado, numCaja, razon, fecha) 
                VALUES ('efectivo', ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("diss", $monto, $idEmpleado, $cajaFactura['numCaja'], $concepto);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar el egreso en caja');
        }
    }
    
    // Confirmar los cambios
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Factura cancelada correctamente',
        'caja_activa' => $cajaActiva
    ]);
    
} catch (Exception $e) {
    // Revertir los cambios en caso de error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Cerrar conexión
$conn->close();
?>