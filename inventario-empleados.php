<?php
/* Verificacion de sesion */
session_start();

// Configurar el tiempo de caducidad de la sesión
$inactivity_limit = 900; // 15 minutos en segundos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset();
    session_destroy();
    header("Location: login.php?session_expired=session_expired");
    exit();
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

require "php/conexion.php";

// Inicializar variables
$result = false;
$totalProductos = $totalCategorias = $casiAgotados = "N/A";
$idEmpleado = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['seleccionar-empleado'])) {
    $idEmpleado = intval($_POST['seleccionar-empleado']);

    if ($_SESSION['idPuesto'] > 2){
        $idEmpleado = intval($_SESSION['idPuesto']);
    }
    
    // Usar consultas preparadas para evitar inyección SQL
    // Consulta principal
    $stmt = $conn->prepare("SELECT
        p.id,
        p.descripcion AS producto,
        pt.descripcion AS tipo_producto,
        p.existencia AS existencia,
        ie.cantidad AS existencia_inventario,
        p.precioCompra AS costo,
        p.precioVenta1,
        p.precioVenta2,
        CASE 
            WHEN ie.cantidad = 0 THEN 'Agotado' 
            WHEN ie.cantidad <= p.reorden THEN 'Casi Agotado' 
            ELSE 'Disponible'
        END AS disponiblidad_inventario
        FROM productos AS p
        INNER JOIN inventarioempleados AS ie ON p.id = ie.idProducto
        LEFT JOIN productos_tipo AS pt ON p.idTipo = pt.id
        WHERE p.activo = TRUE AND ie.idEmpleado = ?
        ORDER BY p.descripcion ASC");
    
    $stmt->bind_param("i", $idEmpleado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Consultas para estadísticas - usando consultas preparadas
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM inventarioempleados 
                                JOIN productos ON inventarioempleados.idProducto = productos.id 
                                WHERE productos.activo = TRUE AND inventarioempleados.idEmpleado = ?");
    $stmtTotal->bind_param("i", $idEmpleado);
    $stmtTotal->execute();
    $totalProductos = $stmtTotal->get_result()->fetch_assoc()['total'];
    
    $stmtCat = $conn->prepare("SELECT COUNT(DISTINCT idTipo) as total FROM inventarioempleados 
                              JOIN productos ON inventarioempleados.idProducto = productos.id 
                              WHERE productos.activo = TRUE AND inventarioempleados.idEmpleado = ?");
    $stmtCat->bind_param("i", $idEmpleado);
    $stmtCat->execute();
    $totalCategorias = $stmtCat->get_result()->fetch_assoc()['total'];
    
    $stmtAgot = $conn->prepare("SELECT COUNT(*) as total FROM inventarioempleados 
                               JOIN productos ON inventarioempleados.idProducto = productos.id 
                               WHERE productos.activo = TRUE AND inventarioempleados.cantidad <= productos.reorden 
                               AND inventarioempleados.cantidad > 0 AND inventarioempleados.idEmpleado = ?");
    $stmtAgot->bind_param("i", $idEmpleado);
    $stmtAgot->execute();
    $casiAgotados = $stmtAgot->get_result()->fetch_assoc()['total'];
}

// Consulta para el dropdown de empleados
if (isset($_SESSION['idPuesto']) && $_SESSION['idPuesto'] > 2) {
    $stmtEmp = $conn->prepare("SELECT id, CONCAT(id,' ',nombre,' ',apellido) AS nombre 
                             FROM empleados WHERE activo = TRUE AND id = ?");
    $stmtEmp->bind_param("i", $_SESSION['idEmpleado']);
} else {
    $stmtEmp = $conn->prepare("SELECT id, CONCAT(id,' ',nombre,' ',apellido) AS nombre 
                             FROM empleados WHERE activo = TRUE");
}
$stmtEmp->execute();
$resultEmpleados = $stmtEmp->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Inventario Personal</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <link rel="stylesheet" href="css/inventario.css">
    <link rel="stylesheet" href="css/menu.css"> <!-- CSS menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Importación de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->
</head>
<body>

    <div class="navegator-nav">

        <!-- Menu-->
        <?php include 'menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <div class="general-container">
                <div class="header">
                    <h1>Inventario Personal de Productos</h1>
                    <div class="header">
                        <form action="" method="post" class="employee-selector-form">
                            <span class="employee-selector-label">Selecciona el Empleado:</span>
                            <div class="employee-selector-controls">
                                <div class="select-container">
                                    <select name="seleccionar-empleado" id="seleccionar-empleado" class="employee-select">
                                        <option disabled selected>---</option>
                                        <?php
                                        if ($resultEmpleados && $resultEmpleados->num_rows > 0) {
                                            while ($fila = $resultEmpleados->fetch_assoc()) {
                                                $selected = ($_SERVER['REQUEST_METHOD'] == "POST" && 
                                                            isset($_POST['seleccionar-empleado']) && 
                                                            $idEmpleado == $fila['id']) ? " selected" : "";
                                                echo "<option value='" . $fila['id'] . "'" . $selected . ">" . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . "</option>";
                                            }
                                        } else {
                                            echo "<option value='' disabled>No hay opciones</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="employee-submit-button">Buscar</button>
                            </div>
                        </form>
                    </div>
                    <div class="search-container">
                        <i class="lucide-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar productos...">
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="icon-container orange">
                                <i class="lucide-package"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <p>Total Productos</p>
                            <h2><?php echo htmlspecialchars($totalProductos, ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                        <div class="stat-footer"></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="icon-container green">
                                <i class="lucide-list"></i>
                            </div>
                        </div>
                        <div class="stat-info">
                            <p>Total Categorías</p>
                            <h2><?php echo htmlspecialchars($totalCategorias, ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                        <div class="stat-footer"></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="icon-container blue">
                                <i class="lucide-alert-triangle"></i>
                            </div>
                            <button class="filter-button"><i class="lucide-filter"></i></button>
                        </div>
                        <div class="stat-info">
                            <p>Casi Agotados</p>
                            <h2><?php echo htmlspecialchars($casiAgotados, ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Vista de escritorio -->
                <div class="table-card desktop-view">
                    <table id="inventarioTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Tipo de Producto</th>
                                <th>Existencia</th>
                                <th>Precios de Venta</th>
                                <th>Disponibilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["producto"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["tipo_producto"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["existencia_inventario"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>$" . htmlspecialchars($row["precioVenta1"], ENT_QUOTES, 'UTF-8') . ", $" . 
                                            htmlspecialchars($row["precioVenta2"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td><span class='status " . htmlspecialchars(strtolower(str_replace(' ', '-', $row["disponiblidad_inventario"])), ENT_QUOTES, 'UTF-8') . "'>" . 
                                            htmlspecialchars($row["disponiblidad_inventario"], ENT_QUOTES, 'UTF-8') . "</span></td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No se encontraron resultados</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil -->
                <div class="mobile-view">
                    <?php
                    if ($result && $result->num_rows > 0) {
                        mysqli_data_seek($result, 0); // Reset pointer to start
                        while ($row = $result->fetch_assoc()) {
                            $productName = htmlspecialchars($row["producto"], ENT_QUOTES, 'UTF-8');
                            $productNameUpper = htmlspecialchars(strtoupper($row["producto"]), ENT_QUOTES, 'UTF-8');
                            $statusClass = htmlspecialchars(strtolower(str_replace(' ', '-', $row["disponiblidad_inventario"])), ENT_QUOTES, 'UTF-8');
                            
                            echo <<<HTML
                            <div class="mobile-card" data-product="{$productNameUpper}">
                                <div class="mobile-card-header">
                                    <div class="mobile-card-title-section">
                                        <h3 class="mobile-card-title">{$productName}</h3>
                                        <p class="mobile-card-subtitle">{$row["tipo_producto"]}</p>
                                    </div>
                                    <span class="status {$statusClass}">{$row["disponiblidad_inventario"]}</span>
                                </div>
                                <div class="mobile-card-content">
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">ID:</span>
                                        <span class="mobile-card-value">{$row["id"]}</span>
                                    </div>
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">Existencia:</span>
                                        <span class="mobile-card-value">{$row["existencia_inventario"]}</span>
                                    </div>
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">Precio Venta:</span>
                                        <span class="mobile-card-value">\${$row["precioVenta1"]}, \${$row["precioVenta2"]}</span>
                                    </div>
                                </div>
                            </div>
                    HTML;
                        }
                    }
                    ?>
                </div>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA ENCIMA DE ESTA LINEA -->
        </div>
    </div>
    
    <script>
        // Esperar a que se cargue el DOM completamente
        document.addEventListener('DOMContentLoaded', function() {
            // Función de búsqueda optimizada
            const searchInput = document.getElementById('searchInput');
            const inventarioTable = document.getElementById('inventarioTable');
            const mobileCards = document.querySelectorAll('.mobile-card');
            
            searchInput.addEventListener('input', function() {
                const filter = this.value.toUpperCase();
                
                // Búsqueda en la tabla de escritorio
                if (inventarioTable) {
                    const trs = inventarioTable.querySelectorAll('tbody tr');
                    
                    trs.forEach(tr => {
                        const productCell = tr.querySelector('td:nth-child(2)');
                        if (productCell) {
                            const txtValue = productCell.textContent || productCell.innerText;
                            tr.style.display = txtValue.toUpperCase().includes(filter) ? '' : 'none';
                        }
                    });
                }
                
                // Búsqueda en las tarjetas móviles
                mobileCards.forEach(card => {
                    const productName = card.getAttribute('data-product');
                    card.style.display = productName.includes(filter) ? '' : 'none';
                });
            });
            
            // Manejador del overlay y menú móvil
            const mobileToggle = document.getElementById('mobileToggle');
            const overlay = document.getElementById('overlay');
            
            if (mobileToggle && overlay) {
                mobileToggle.addEventListener('click', toggleMenu);
                overlay.addEventListener('click', closeMenu);
            }
            
            function toggleMenu() {
                document.body.classList.toggle('menu-open');
            }
            
            function closeMenu() {
                document.body.classList.remove('menu-open');
            }
        });
    </script>

</body>
</html>