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

/* Fin de verificacion de sesion */

require 'php/conexion.php';

// Inicializar variables de búsqueda
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : "";

// Construir la consulta SQL con filtros de búsqueda
$query = "SELECT
            p.id AS idProducto,
            p.descripcion AS descripcion,
            pt.descripcion AS tipo,
            p.existencia,
            p.idTipo,
            p.precioCompra,
            p.precioVenta1,
            p.precioCompra,
            p.precioVenta2,
            p.reorden,
            p.activo
        FROM
            productos AS p
        LEFT JOIN productos_tipo AS pt
        ON
            p.idTipo = pt.id
        WHERE
            1 = 1
        ";

// Añadir condición de búsqueda si se proporciona un término de búsqueda
if (!empty($search)) {
    $query .= " AND p.descripcion LIKE '%$search%'";
}

$query .= " LIMIT 50"; // Limitar la cantidad de resultados a 50

// Ejecutar la consulta
$result = $conn->query($query);

// Verificar si la consulta fue exitosa
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
?>

<!-- Obtener y mandar el id al modal con uso de javascript de autorelleno -->
<?php
// Obtener los tipos de producto
$query_tipos = "SELECT id, descripcion FROM productos_tipo";
$result_tipos = $conn->query($query_tipos);

if (!$result_tipos) {
    die("Error en la consulta de tipos de producto: " . $conn->error);
}

$tipos_producto = [];
while ($row_tipo = $result_tipos->fetch_assoc()) {
    $tipos_producto[] = $row_tipo;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Productos</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cliente_tabla.css">         <!--------tabla de cliente--------->
    <link rel="stylesheet" href="css/producto_modal.css">      <!------actualizar modal de producto-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Contenedor principal -->
    <div class="container">
        <!-- Botón de menú móvil -->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <!-------------------------->
        <!-- Requerimiento de Menú -->
        <?php require 'menu.html' ?>
        <script src="js/sidebar_menu.js"></script>
        <!--------------------------->
        <!-- Overlay para móviles -->
        <div class="overlay" id="overlay"></div>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Sección del encabezado -->
            <div class="header-section">
                <div class="title-container">
                    <h1>Lista de Productos</h1>
                    <a href="productos_nuevo.php" class="btn btn-new ">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14m-7-7h14"></path>
                        </svg>
                        <span>Nuevo</span>
                    </a>
                </div>
                
                <!-- Sección de búsqueda -->
                <div class="search-section">
                    <form method="GET" action="" class="search-form">
                        <div class="search-input-container">
                            <div class="search-input-wrapper">
                                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                    placeholder="Buscar por descripción..."
                                    autocomplete="off"
                                >
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <!-- boton de buscar -->
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="table-section">
                <div class="table-container">
                    <table class="client-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Existencia</th>
                                <th>Precio Compra</th>
                                <th>Precio Venta 1</th>
                                <th>Precio Venta 2</th>
                                <th>Reorden</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['idProducto']); ?></td>
                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                <td><?php echo htmlspecialchars($row['existencia']); ?></td>
                                <td><?php echo htmlspecialchars($row['precioCompra']); ?></td>
                                <td><?php echo htmlspecialchars($row['precioVenta1']); ?></td>
                                <td><?php echo htmlspecialchars($row['precioVenta2']); ?></td>
                                <td><?php echo htmlspecialchars($row['reorden']); ?></td>
                                <td>
                                    <span class="status <?php echo $row['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-update" 
                                            data-id="<?php echo $row['idProducto']; ?>" 
                                            data-descripcion="<?php echo $row['descripcion']; ?>"
                                            data-tipo="<?php echo $row['idTipo']; ?>" 
                                            data-existencia="<?php echo $row['existencia']; ?>"
                                            data-preciocompra="<?php echo $row['precioCompra']; ?>"
                                            data-precioventa1="<?php echo $row['precioVenta1']; ?>"
                                            data-precioventa2="<?php echo $row['precioVenta2']; ?>"
                                            data-reorden="<?php echo $row['reorden']; ?>"
                                            data-activo="<?php echo $row['activo']; ?>"
                                            onclick="mostrarModal(this)">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 2v6h-6M3 22v-6h6"></path>
                                            <path d="M21 8c0 9.941-8.059 18-18 18"></path>
                                            <path d="M3 16c0-9.941 8.059-18 18-18"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mobile-table">
                <?php 
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()): 
                ?>
                <div class="mobile-record">
                    <div class="mobile-record-header">
                        <div class="mobile-header-info">
                            <h3><?php echo htmlspecialchars($row['descripcion']); ?></h3>
                            <p class="mobile-subtitle"><?php echo htmlspecialchars($row['tipo']); ?></p>
                        </div>
                        <span class="status <?php echo $row['activo'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                    <div class="mobile-record-content">
                        <div class="mobile-grid">
                            <div class="mobile-info-item">
                                <div class="mobile-label">ID:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['idProducto']); ?></div>
                            </div>
                            <div class="mobile-info-item">
                                <div class="mobile-label">Existencia:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['existencia']); ?></div>
                            </div>
                            <div class="mobile-info-item">
                                <div class="mobile-label">Precio Compra:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['precioCompra']); ?></div>
                            </div>
                            <div class="mobile-info-item">
                                <div class="mobile-label">Precio Venta 1:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['precioVenta1']); ?></div>
                            </div>
                            <div class="mobile-info-item">
                                <div class="mobile-label">Precio Venta 2:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['precioVenta2']); ?></div>
                            </div>
                            <div class="mobile-info-item">
                                <div class="mobile-label">Reorden:</div>
                                <div class="mobile-value"><?php echo htmlspecialchars($row['reorden']); ?></div>
                            </div>
                            <div class="mobile-actions">
                                <button class="btn btn-update" 
                                        data-id="<?php echo $row['idProducto']; ?>" 
                                        data-descripcion="<?php echo $row['descripcion']; ?>"
                                        data-tipo="<?php echo $row['idTipo']; ?>" 
                                        data-existencia="<?php echo $row['existencia']; ?>"
                                        data-preciocompra="<?php echo $row['precioCompra']; ?>"
                                        data-precioventa1="<?php echo $row['precioVenta1']; ?>"
                                        data-precioventa2="<?php echo $row['precioVenta2']; ?>"
                                        data-reorden="<?php echo $row['reorden']; ?>"
                                        data-activo="<?php echo $row['activo']; ?>"
                                        onclick="mostrarModal(this)">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 2v6h-6M3 22v-6h6"></path>
                                        <path d="M21 8c0 9.941-8.059 18-18 18"></path>
                                        <path d="M3 16c0-9.941 8.059-18 18-18"></path>
                                    </svg>
                                    <span>Editar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <!-- referencia al html donde esta el modal -->
            <?php include 'producto_actualizar.php'; ?>

            <!-- manejo de mensajes  -->
            <?php
            // Mostrar mensajes de éxito
            if (isset($_SESSION['status']) && $_SESSION['status'] === 'success') {
                echo "
                    <script>
                        Swal.fire({
                            title: '¡Éxito!',
                            text: '{$_SESSION['message']}',
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(function() {
                            window.location.href = 'productos.php'; 
                        });
                    </script>
                ";
                unset($_SESSION['status'], $_SESSION['message']); // Limpiar el estado después de mostrar el mensaje
            }

            // Mostrar mensajes de error
            if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
                $errors = json_encode($_SESSION['errors']); // Convertir el array de errores a JSON
                echo "
                    <script>
                        Swal.fire({
                            title: '¡Error!',
                            html: `{$errors}`.split(',').join('<br>'), // Mostrar errores en líneas separadas
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    </script>
                ";
                unset($_SESSION['errors']); // Limpiar los errores después de mostrarlos
            }
            ?>

            <?php require 'productos_actualizar.php'; ?>

            <!-- Scripts -->
            <script>
                function mostrarModal(button) {
                    if (!button) return;  // Evita ejecutar si no hay un botón específico

                    // Obtener datos del producto desde los atributos data-*
                    const idProducto = button.getAttribute("data-id");
                    const descripcion = button.getAttribute("data-descripcion");
                    const precioCompra = button.getAttribute("data-preciocompra");
                    const precioVenta1 = button.getAttribute("data-precioventa1");
                    const precioVenta2 = button.getAttribute("data-precioventa2");
                    const reorden = button.getAttribute("data-reorden");
                    const activo = button.getAttribute("data-activo");

                    // Asignar valores a los campos del formulario
                    if (idProducto) document.getElementById("idProducto").value = idProducto;
                    if (descripcion) document.getElementById("descripcion").value = descripcion;
                    if (precioCompra) document.getElementById("precioCompra").value = precioCompra;
                    if (precioVenta1) document.getElementById("precioVenta1").value = precioVenta1;
                    if (precioVenta2) document.getElementById("precioVenta2").value = precioVenta2;
                    if (reorden) document.getElementById("reorden").value = reorden;
                    if (activo) document.getElementById("activo").value = activo;

                    // Establecer el valor seleccionado del tipo de producto
                    const tipoActual = button.getAttribute("data-tipo");  // Obtener el idTipo
                    const selectTipo = document.getElementById("tipo");
                    selectTipo.value = tipoActual;  // Establecer el valor seleccionado

                    // Mostrar el modal
                    document.getElementById("modalActualizar").style.display = "flex";
                    console.log("Tipo de producto (idTipo):", tipoActual);  // Verificar el valor del tipo
                }

                function cerrarModal() {
                    document.getElementById("modalActualizar").style.display = "none";
                }

                // Cerrar el modal si el usuario hace clic fuera de él
                window.onclick = function(event) {
                    let modal = document.getElementById("modalActualizar");
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                };
            </script>
            <script src="js/menu.js"></script>
            <script src="js/modo_oscuro.js"></script>
            <script src="js/oscuro_recargar.js"></script>
        </main>
    </div>
</body>
</html>