<?php
require "php/conexion.php";

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

// Filtrar por número de factura si se ha ingresado
if ($numFactura) {
    $sql .= " AND f.numFactura = $numFactura";
}

// Filtrar por estado si no es "todas"
if ($estado !== 'todas') {
    $sql .= " AND f.estado = '$estado'";
}

$sql .= " ORDER BY f.numFactura DESC";

$result = $conn->query($sql);

// Verificar si hay resultados
if ($result->num_rows > 0) {
    $facturas = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $mensaje = "No se encontraron facturas con los filtros seleccionados.";
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
                <!-- Formulario de búsqueda y filtrado -->
                <form method="GET" action="">
                    <div class="search-box">
                        <input type="text" name="numFactura" placeholder="Buscar factura..." value="<?php echo $numFactura !== null ? $numFactura : ''; ?>">
                        <select name="estado">
                            <option value="todas" <?php echo $estado === 'todas' ? 'selected' : ''; ?>>Todas</option>
                            <option value="Pagada" <?php echo $estado === 'Pagada' ? 'selected' : ''; ?>>Pagadas</option>
                            <option value="Pendiente" <?php echo $estado === 'Pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                        </select>
                        <button type="submit">Buscar</button>
                    </div>
                </form>
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
                                    <label>Nombre del Cliente</label>
                                    <span><?php echo $facturaInfo['NombreCliente']; ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Teléfono</label>
                                    <span><?php echo $facturaInfo['telefono']; ?></span>
                                </div>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Fecha y Hora</label>
                                    <span><?php echo $facturaInfo['fecha']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Tipo de Factura</label>
                                    <span><?php echo $facturaInfo['tipoFactura']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Vendedor</label>
                                    <span><?php echo $facturaInfo['NombreEmpleado']; ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Monto Adeudado</label>
                                    <span class="amount-due">$<?php echo $facturaInfo['balance']; ?></span>
                                </div>
                            </div>
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
// obtener los detalles de la factura mientras haya resultados
                        if ($resultDetalles->num_rows > 0) {
                            echo "<div class='products-section'>";
                            while ($detalle = $resultDetalles->fetch_assoc()) {
                                echo "<div class='product-card'>
                                        <div class='product-header'>
                                            <span class='product-id'>PRD-{$detalle['idProducto']}</span>
                                            <span class='product-name'>{$detalle['Producto']}</span>
                                        </div>
                                        <div class='product-details'>
                                            <div class='detail-item'>
                                                <label>Cantidad</label>
                                                <span>{$detalle['cantidad']}</span>
                                            </div>
                                            <div class='detail-item'>
                                                <label>Precio</label>
                                                <span>\${$detalle['precioVenta']}</span>
                                            </div>
                                            <div class='detail-item'>
                                                <label>Total</label>
                                                <span>\${$detalle['importeProducto']}</span>
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
                                    <span>$<?php echo $facturaInfo['importe']; ?></span>
                                </div>
                                <div class="total-row">
                                     <span>ITBIS Total</span>
                                     <span>$0.00</span> <!--no se cobra itebis-->
                                </div>
                                <div class="total-row">
                                    <span>Descuento</span>
                                    <span class="discount">-$<?php echo $facturaInfo['descuento']; ?></span>
                                </div>
                                <div class="total-row">
                                    <span>Total Ajuste</span>
                                    <span>$<?php echo $facturaInfo['total_ajuste']; ?></span>
                                </div>
                                <div class="total-row final-total">
                                    <span>Total a Pagar</span>
                                    <span>$<?php echo $facturaInfo['total']; ?></span>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-primary">Avance a Cuenta</button>
                                <button class="btn-secondary">
                                    <span class="printer-icon">🖨</span>
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