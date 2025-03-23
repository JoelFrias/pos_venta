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

require "php/conexion.php";

$sql = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $idEmpleado = $_POST['seleccionar-empleado'];

    $sql = "SELECT
                p.id,
                p.descripcion AS producto,
                pt.descripcion AS tipo_producto,
                p.existencia AS existencia,
                ie.cantidad AS existencia_inventario,
                CONCAT('$', p.precioCompra) AS Costo,
                CONCAT(
                    '$',
                    p.precioVenta1,
                    ', $',
                    p.precioVenta2
                ) AS PreciosVentas,
                CASE WHEN ie.cantidad = 0 THEN 'Agotado' WHEN ie.cantidad <= p.reorden THEN 'Casi Agotado' ELSE 'Disponible'
            END AS disponiblidad_inventario
            FROM
                productos AS p
            INNER JOIN inventarioempleados AS ie
            ON
                p.id = ie.idProducto
            LEFT JOIN productos_tipo AS pt
            ON
                p.idTipo = pt.id
            WHERE
                p.activo = TRUE AND
                ie.idEmpleado = ".$idEmpleado;

    // Consultas para estadísticas
    $totalProductos = $conn->query("SELECT COUNT(*) as total FROM inventarioempleados JOIN productos ON inventarioempleados.idProducto = productos.id WHERE productos.activo = TRUE AND inventarioempleados.idEmpleado = ".$idEmpleado)->fetch_assoc()['total'];

    $totalCategorias = $conn->query("SELECT COUNT(DISTINCT idTipo) as total FROM inventarioempleados JOIN productos ON inventarioempleados.idProducto = productos.id WHERE productos.activo = TRUE AND inventarioempleados.idEmpleado = ".$idEmpleado)->fetch_assoc()['total'];

    $casiAgotados = $conn->query("SELECT COUNT(*) as total FROM inventarioempleados JOIN productos ON inventarioempleados.idProducto = productos.id WHERE productos.activo = TRUE AND inventarioempleados.cantidad <= productos.reorden AND inventarioempleados.cantidad > 0 AND inventarioempleados.idEmpleado = ".$idEmpleado)->fetch_assoc()['total'];    

}

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Personal</title>
    <link rel="stylesheet" href="css/menu.css">
    <!-- link de los iconos raro que le puse random -->
    <link href="https://unpkg.com/lucide-static/font/lucide.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/inventario.css">
</head>
<body>
  <!-- Contenedor principal -->
  <div class="container">
        <!-- Botón para mostrar/ocultar el menú en dispositivos móviles -->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Incluir el menú -->
        <?php require 'menu.html' ?>
        <script src="js/sidebar_menu.js"></script>

        <!-- Overlay para dispositivos móviles -->
        <div class="overlay" id="overlay"></div>

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
                            $sql = "SELECT id,CONCAT(id,' ',nombre,' ',apellido) AS nombre FROM empleados WHERE id <> 1 AND activo = TRUE";
                            $resultado = $conn->query($sql);
                            if ($resultado->num_rows > 0) {
                                while ($fila = $resultado->fetch_assoc()) {
                                    if ($_SERVER['REQUEST_METHOD'] == "POST") {
                                        echo "<option value='" . $fila['id'] . "'" . 
                                             ($_POST['seleccionar-empleado'] == $fila['id'] ? " selected" : "") . 
                                             ">" . $fila['nombre'] . "</option>";
                                    } else {
                                        echo "<option value='" . $fila['id'] . "'>" . $fila['nombre'] . "</option>";
                                    }                                
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
                    <h2><?php 
                        if($_SERVER["REQUEST_METHOD"] == "POST"){
                            echo htmlspecialchars($totalProductos, ENT_QUOTES, 'UTF-8');
                        }else{
                            echo "N/A";
                        }
                        ?>
                    </h2>
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
                    <h2><?php 
                        if($_SERVER["REQUEST_METHOD"] == "POST"){
                            echo htmlspecialchars($totalCategorias, ENT_QUOTES, 'UTF-8');
                        }else{
                            echo "N/A";
                        }
                        ?>
                    </h2>
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
                    <h2><?php 
                        if($_SERVER["REQUEST_METHOD"] == "POST"){
                            echo htmlspecialchars($casiAgotados, ENT_QUOTES, 'UTF-8');
                        }else{
                            echo "N/A";
                        }
                        ?>
                    </h2>
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
                        <th>Costo</th>
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
                                    <td>" . htmlspecialchars($row["Costo"], ENT_QUOTES, 'UTF-8') . "</td>
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
    <script>
        ///////////////////////////////BUSQUEDA A PONER TIPO JSON///////////////////////////////////////
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

    <script src="js/menu.js"></script>
    <script src="js/modo_oscuro.js"></script>
    <script src="js/oscuro_recargar.js"></script>
</body>
</html>