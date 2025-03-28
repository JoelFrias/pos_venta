<?php

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

require_once 'php/conexion.php';

// Obtener el número de factura y el estado desde el formulario (si existen)
$numFactura = isset($_GET['numFactura']) && !empty($_GET['numFactura']) ? intval($_GET['numFactura']) : null;
$estado = isset($_GET['estado']) ? $_GET['estado'] : 'todas'; // Estado por defecto: todas

// Construir la consulta SQL según los filtros
$sql = "
SELECT 
    f.numFactura, 
    f.tipoFactura, 
    DATE_FORMAT(f.fecha, '%d/%m/%Y %l:%i %p') AS fecha, 
    f.importe, 
    f.descuento, 
    f.total, 
    f.total_ajuste, 
    f.balance, 
    f.estado,
    CONCAT(e.nombre, ' ', e.apellido) AS NombreEmpleado,
    c.id AS idCliente, 
    CONCAT(c.nombre, ' ', c.apellido) AS NombreCliente, 
    c.telefono
FROM facturas AS f
LEFT JOIN empleados AS e ON f.idEmpleado = e.id
LEFT JOIN clientes AS c ON f.idCliente = c.id
WHERE 1=1";

$params = [];
$types = "";

// Filtrar por número de factura si se ha ingresado
if (!empty($numFactura)) {
    $sql .= " AND f.numFactura = ?";
    $params[] = $numFactura;
    $types .= "i"; // 'i' para enteros
}

$sql .= " ORDER BY f.numFactura DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Verificar si hay resultados
if ($result->num_rows > 0) {
    $facturas = $result->fetch_all(MYSQLI_ASSOC);

    // Pasar numeros de factura a moneda
    foreach ($facturas as &$factura) {
        $factura['importe'] = "RD$ " . number_format($factura['importe'], 2, '.', '');
        $factura['descuento'] = "RD$ " . number_format($factura['descuento'], 2, '.', '');
        $factura['total_ajuste'] = "RD$ " . number_format($factura['total_ajuste'], 2, '.', '');
        $factura['total'] = "RD$ " . number_format($factura['total'], 2, '.', '');
        $factura['balance'] = "RD$ " . number_format($factura['balance'], 2, '.', '');
    }



} else {
    $mensaje = "No se encontraró la factura con los criterios de búsqueda.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Sistema de Facturación</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/detalle_factura.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<!--  -->

    <?php include "menu.php"; ?>

    
    <script src="js/sidebar_menu.js"></script>

        <!-- Overlay para dispositivos móviles -->
    <div class="overlay" id="overlay"></div>
    
    <button id="mobileToggle" class="toggle-btn">
        <i class="fas fa-bars"></i>
    </button>



   
<!--  -->
    <div class="container">
        <div class="invoice-container">
            <div class="search-section">
                <h2>Detalle de Factura</h2>
            </div>
            <?php if (isset($mensaje)): ?>
                <!-- Mostrar mensaje si no se encontraron facturas -->
                <p><?php echo $mensaje; ?></p>
            <?php elseif (!empty($facturas)): ?>
                <!-- Mostrar todas las facturas que coinciden con el filtro -->
                <?php foreach ($facturas as $facturaInfo): ?>
                    <div class="invoice-card">
                        <div class="invoice-header">
                            <h1>Factura #<?php echo $facturaInfo['numFactura']; ?></h1>
                            <span class="status <?php echo strtolower($facturaInfo['estado']); ?>">
                                 <?php echo $facturaInfo['estado']; ?>
                            </span>
                        </div>
                        <div class="client-info">
                            <div class="info-column">
                                <div class="info-row">
                                    <label>ID Cliente</label>
                                    <span><?php echo $facturaInfo['idCliente']; ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Nombre del Cliente:</label>
                                    <span><?php echo $facturaInfo['NombreCliente']; ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Teléfono:</label>
                                    <span><?php echo $facturaInfo['telefono']; ?></span>
                                </div>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Fecha y Hora:</label>
                                    <span><?php echo $facturaInfo['fecha']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Tipo de Factura:</label>
                                    <span><?php echo $facturaInfo['tipoFactura']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Vendedor:</label>
                                    <span><?php echo $facturaInfo['NombreEmpleado']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Monto Adeudado:</label>
                                    <span class="amount-due"><?php echo $facturaInfo['balance']; ?></span>
                                </div>
                            </div>
                        </div>
                    
                        <div class="search-section">
                            <h2>Productos Facturados:</h2>
                        </div>

                        <!-- Mostrar detalles de la factura -->
                        <?php
                        // Consulta para obtener los detalles de la factura actual
                        $sqlDetalles = "
                        SELECT 
                            p.id AS idProducto, 
                            p.descripcion AS Producto, 
                            fd.cantidad, 
                            fd.precioVenta, 
                            fd.importe AS importeProducto
                        FROM facturas_detalles AS fd
                        LEFT JOIN productos AS p ON fd.idProducto = p.id
                        WHERE fd.numFactura = {$facturaInfo['numFactura']}";

                        $resultDetalles = $conn->query($sqlDetalles);

                        

                        if ($resultDetalles->num_rows > 0) {
                            echo "<div class='products-section'>";
                            while ($detalle = $resultDetalles->fetch_assoc()) {

                                // Formatear los números a moneda
                                $detalle['importeProducto'] = "RD$ " . number_format($detalle['importeProducto'], 2, '.', '');
                                $detalle['precioVenta'] = "RD$ " . number_format($detalle['precioVenta'], 2, '.', '');
                                $detalle['cantidad'] = number_format($detalle['cantidad'], 0, '.', ''); // Formatear cantidad a número entero

                                echo "<div class='product-card'>
                                        <div class='product-header'>
                                            <span class='product-id'>ID - {$detalle['idProducto']}</span>
                                            <span class='product-name'>{$detalle['Producto']}</span>
                                        </div>
                                        <div class='product-details'>
                                            <div class='detail-item'>
                                                <label>Cantidad</label>
                                                <span>{$detalle['cantidad']}</span>
                                            </div>
                                            <div class='detail-item'>
                                                <label>Precio</label>
                                                <span>{$detalle['precioVenta']}</span>
                                            </div>
                                            <div class='detail-item'>
                                                <label>Total</label>
                                                <span>{$detalle['importeProducto']}</span>
                                            </div>
                                        </div>
                                    </div>";
                            }
                            echo "</div>";
                        }
                        ?>
                        <div class="invoice-summary">
                            <div class="totals">
                                <div class="total-row">
                                    <span>Subtotal</span>
                                    <span><?php echo $facturaInfo['importe']; ?></span>
                                </div>
                                <div class="total-row">
                                     <span>ITBIS Total</span>
                                     <span>RD$ 0.00</span> <!--no se cobra itebis-->
                                </div>
                                <div class="total-row">
                                    <span>Descuento</span>
                                    <span class="discount"><?php echo $facturaInfo['descuento']; ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Total Ajuste</span>
                                    <span><?php echo $facturaInfo['total_ajuste']; ?></span>
                                </div>
                                <div class="total-row final-total">
                                    <span>Total a Pagar</span>
                                    <span><?php echo $facturaInfo['total']; ?></span>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-primary" onclick="navigateTo('cuenta-avance.php?idCliente=<?php echo $facturaInfo['idCliente']; ?>')"><i class="fa-solid fa-money-check-dollar"></i> Avance a cuenta del cliente</button>
                                <button class="btn-secondary">
                                    <span class="printer-icon">🖨️</span>
                                    Reimprimir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/menu.js"></script>
    <script src="js/sidebar_menu.js"></script>
</body>
</html>
<?php
// Cerrar conexión
$conn->close();
?>