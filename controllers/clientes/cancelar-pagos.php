<?php
// cancelar_pago.php
// Script para cancelar un pago y redistribuir su monto a facturas pendientes

// Función para cancelar un pago
function cancelarPago($idPago) {
    global $conn; // Conexión MySQLi existente
    
    // Respuesta por defecto
    $respuesta = [
        'error' => false,
        'mensaje' => '',
        'detalles' => []
    ];
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // 1. Obtener información del pago a cancelar
        $stmt = $conn->prepare("SELECT monto, id_cliente FROM pagos WHERE id = ?");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception("No se encontró el pago con ID: " . $idPago);
        }
        
        $pago = $resultado->fetch_assoc();
        $montoPago = $pago['monto'];
        $idCliente = $pago['id_cliente'];
        $stmt->close();
        
        // 2. Guardar datos para el registro
        $respuesta['detalles']['monto_cancelado'] = $montoPago;
        $respuesta['detalles']['id_cliente'] = $idCliente;
        
        // 3. Borrar registro del ingreso (tabla ingresos si existe)
        $stmt = $conn->prepare("DELETE FROM ingresos WHERE id_pago = ?");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $stmt->close();
        
        // 4. Obtener facturas pendientes del cliente ordenadas por fecha (de más reciente a más antigua)
        $stmt = $conn->prepare("
            SELECT id, total, saldo_pendiente 
            FROM facturas 
            WHERE id_cliente = ? AND saldo_pendiente > 0 
            ORDER BY fecha DESC
        ");
        $stmt->bind_param("i", $idCliente);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $facturas = [];
        
        while ($fila = $resultado->fetch_assoc()) {
            $facturas[] = $fila;
        }
        $stmt->close();
        
        $montoRestante = $montoPago;
        $facturasActualizadas = [];
        
        // 5. Redistribuir el monto del pago en las facturas pendientes
        foreach ($facturas as $factura) {
            if ($montoRestante <= 0) {
                break;
            }
            
            $idFactura = $factura['id'];
            $saldoPendiente = $factura['saldo_pendiente'];
            
            // Determinar cuánto se aplicará a esta factura
            $montoAplicar = min($montoRestante, $saldoPendiente);
            $nuevoSaldo = $saldoPendiente + $montoAplicar; // Sumamos porque estamos cancelando un pago
            
            // Actualizar la factura
            $stmt = $conn->prepare("
                UPDATE facturas 
                SET saldo_pendiente = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("di", $nuevoSaldo, $idFactura);
            $stmt->execute();
            $stmt->close();
            
            // Registrar detalle de la actualización
            $facturasActualizadas[] = [
                'id_factura' => $idFactura,
                'monto_aplicado' => $montoAplicar,
                'nuevo_saldo' => $nuevoSaldo
            ];
            
            // Restar el monto aplicado
            $montoRestante -= $montoAplicar;
        }
        
        // Verificar si quedó saldo por aplicar
        if ($montoRestante > 0) {
            // Opción: crear un crédito a favor del cliente o manejarlo según la lógica del negocio
            $respuesta['detalles']['saldo_no_aplicado'] = $montoRestante;
        }
        
        $respuesta['detalles']['facturas_actualizadas'] = $facturasActualizadas;
        
        // 6. Borrar el registro del pago
        $stmt = $conn->prepare("DELETE FROM pagos WHERE id = ?");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $stmt->close();
        
        // 7. Borrar historial de pagos relacionado
        $stmt = $conn->prepare("DELETE FROM historial_pagos WHERE id_pago = ?");
        $stmt->bind_param("i", $idPago);
        $stmt->execute();
        $stmt->close();
        
        // Confirmar la transacción
        $conn->commit();
        
        $respuesta['mensaje'] = "Pago cancelado exitosamente";
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        $respuesta['error'] = true;
        $respuesta['mensaje'] = "Error al cancelar el pago: " . $e->getMessage();
    }
    
    return $respuesta;
}

// Procesar la solicitud AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se recibió un ID de pago
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['id_pago'])) {
        $resultado = cancelarPago($data['id_pago']);
        
        // Enviar respuesta como JSON
        header('Content-Type: application/json');
        echo json_encode($resultado);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'mensaje' => 'No se proporcionó el ID del pago'
        ]);
    }
}
?>