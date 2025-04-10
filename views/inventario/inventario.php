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
    header('Location: ../../views/auth/login.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: ../../views/auth/login.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

require "../../models/conexion.php";

$sql = "SELECT
            p.id,
            p.descripcion AS producto,
            pt.descripcion AS tipo_producto,
            p.existencia AS existencia,
            i.existencia AS existencia_inventario,
            CONCAT('$',p.precioCompra) AS Costo,
            CONCAT('$',p.precioVenta1, ', $',p.precioVenta2) AS PreciosVentas,
            CASE
                WHEN i.existencia = 0 THEN 'Agotado'
                WHEN i.existencia <= p.reorden THEN 'Casi Agotado'
                ELSE 'Disponible'
            END AS disponiblidad_inventario
        FROM
            productos AS p
        INNER JOIN inventario AS i
            ON p.id = i.idProducto
        LEFT JOIN productos_tipo AS pt
            ON p.idTipo = pt.id
        WHERE
            p.activo = TRUE
        ORDER BY p.descripcion ASC";

$result = $conn->query($sql);
// aca manejas el limine que quiere que cuente si meno a 5 etc
// Consultas para estadísticas
$totalProductos = $conn->query("SELECT COUNT(*) as total FROM inventario")->fetch_assoc()['total'];

$totalCategorias = $conn->query("SELECT COUNT(DISTINCT idTipo) as total FROM inventario JOIN productos ON inventario.idProducto = productos.id WHERE productos.activo = TRUE")->fetch_assoc()['total'];

$casiAgotados = $conn->query("SELECT COUNT(*) as total FROM inventario JOIN productos ON inventario.idProducto = productos.id WHERE inventario.existencia <= productos.reorden AND inventario.existencia > 0 AND productos.activo = TRUE")->fetch_assoc()['total'];

$noDisponibles = $conn->query("SELECT COUNT(*) as total FROM inventario JOIN productos ON inventario.idProducto = productos.id WHERE inventario.existencia = 0 AND productos.activo = TRUE")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Inventario</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-blanco.png">
    <link rel="stylesheet" href="../../assets/css/inventario.css">
    <link rel="stylesheet" href="../../assets/css/menu.css"> <!-- CSS menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Importación de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->
</head>
<body>
 
    <div class="navegator-nav">

        <!-- Menu-->
        <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <div class="general-container">
                <div class="header">
                    <h1>Almacén Principal de Productos</h1>
                    <div class="search-container">
                        <i class="lucide-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar productos...">
                        <!--<button id="searchButton" class="search-button">Buscar</button>-->
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
                        <div class="stat-footer">
                        </div>
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
                        <div class="stat-footer">
                        </div>
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
                        <!--
                        <div class="stat-footer">
                            <button class="view-more-button">Ver más</button>
                        </div>
                        -->
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="icon-container red">
                                <i class="lucide-x-circle"></i>
                            </div>
                            <button class="filter-button"><i class="lucide-filter"></i></button>
                        </div>
                        <div class="stat-info">
                            <p>Agostados</p>
                            <h2><?php echo htmlspecialchars($noDisponibles, ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                        <!--
                        <div class="stat-footer">
                            <button class="view-more-button">Ver más</button>
                        </div>
                        -->
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
                                <th>Existencia en Inventario</th>
                                <th>Precios de Venta</th>
                                <th>Disponibilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["producto"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["tipo_producto"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["existencia"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["existencia_inventario"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td>" . htmlspecialchars($row["PreciosVentas"], ENT_QUOTES, 'UTF-8') . "</td>
                                            <td><span class='status " . htmlspecialchars($row["disponiblidad_inventario"], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row["disponiblidad_inventario"], ENT_QUOTES, 'UTF-8') . "</span></td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No se encontraron resultados</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil -->
                <div class="mobile-view">
                    <?php
                    if ($result->num_rows > 0) {
                        $result->data_seek(0); // Reset pointer to start
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="mobile-card" data-product="' . htmlspecialchars(strtoupper($row["producto"]), ENT_QUOTES, 'UTF-8') . '">
                                <div class="mobile-card-header">
                                    <div class="mobile-card-title-section">
                                        <h3 class="mobile-card-title">' . htmlspecialchars($row["producto"], ENT_QUOTES, 'UTF-8') . '</h3>
                                        <p class="mobile-card-subtitle">' . htmlspecialchars($row["tipo_producto"], ENT_QUOTES, 'UTF-8') . '</p>
                                    </div>
                                    <span class="status ' . htmlspecialchars($row["disponiblidad_inventario"], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row["disponiblidad_inventario"], ENT_QUOTES, 'UTF-8') . '</span>
                                </div>
                                <div class="mobile-card-content">
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">ID:</span>
                                        <span class="mobile-card-value">' . htmlspecialchars($row["id"], ENT_QUOTES, 'UTF-8') . '</span>
                                    </div>
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">Existencia:</span>
                                        <span class="mobile-card-value">' . htmlspecialchars($row["existencia"], ENT_QUOTES, 'UTF-8') . '</span>
                                    </div>
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">Precio Compra:</span>
                                        <span class="mobile-card-value">' . htmlspecialchars($row["Costo"], ENT_QUOTES, 'UTF-8') . '</span>
                                    </div>
                                    <div class="mobile-card-item">
                                        <span class="mobile-card-label">Precio Venta:</span>
                                        <span class="mobile-card-value">' . htmlspecialchars($row["PreciosVentas"], ENT_QUOTES, 'UTF-8') . '</span>
                                    </div>
                                </div>
                            </div>';
                        }
                    }
                    ?>
                </div>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA ENCIMA DE ESTA LINEA -->
        </div>
    </div>

    <script>
        // Función de búsqueda que funciona tanto para la tabla como para las tarjetas móviles
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toUpperCase();
            
            // Búsqueda en la tabla de escritorio
            const table = document.getElementById('inventarioTable');
            const trs = table.getElementsByTagName('tr');
            
            for (let i = 0; i < trs.length; i++) {
                const td = trs[i].getElementsByTagName('td')[1]; // Columna del nombre del producto para obtener el nombre a buscar
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    trs[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }
            
            // Búsqueda en las tarjetas móviles
            const cards = document.querySelectorAll('.mobile-card');
            cards.forEach(card => {
                const productName = card.getAttribute('data-product');
                card.style.display = productName.indexOf(filter) > -1 ? '' : 'none';
            });
        });
        
    </script>

</body>
</html>