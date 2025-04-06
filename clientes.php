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

// Incluir el archivo de conexión a la base de datos
require 'php/conexion.php';

// Inicializar la variable de búsqueda
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : "";

// Construir la consulta SQL con filtros de búsqueda
$query = "SELECT
            c.id,
            CONCAT(c.nombre, ' ', c.apellido) AS nombreCompleto,
            c.empresa,
            c.tipo_identificacion,
            c.identificacion,
            c.telefono,
            c.notas,
            cc.limite_credito,
            cc.balance,
            CONCAT(
                '#',
                cd.no,
                ', ',
                cd.calle,
                ', ',
                cd.sector,
                ', ',
                cd.ciudad,
                ', (Referencia: ',
                IFNULL(cd.referencia, 'Sin referencia'),
                ')'
            ) AS direccion,
            c.activo
        FROM
            clientes AS c
        LEFT JOIN clientes_cuenta AS cc
        ON
            c.id = cc.idCliente
        LEFT JOIN clientes_direcciones AS cd
        ON
            c.id = cd.idCliente
        WHERE
            1=1
        ";

// Agregar filtro de búsqueda si se proporciona un término de búsqueda
if (!empty($search)) {
    $query .= " AND CONCAT(c.nombre, c.apellido, c.empresa) LIKE '%$search%'";
}

// Limitar la cantidad de resultados a 50
$query .= " LIMIT 50";

// Ejecutar la consulta
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Clientes</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <link rel="stylesheet" href="css/cliente_tabla.css">
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
        
            <main class="main-content">
                <!-- Sección del encabezado -->
                <div class="header-section">
                    <div class="title-container">
                        <h1>Lista de Clientes</h1>
                        <!-- Botón para agregar un nuevo cliente -->

                        <?php if ($_SESSION['idPuesto'] <= 2): ?>
                            <a href="clientes-nuevo.php" class="btn btn-new">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14m-7-7h14"></path>
                                </svg>
                                <span>Nuevo</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Sección de búsqueda -->
                    <div class="search-section">
                        <form method="GET" action="clientes.php" class="search-form">
                            <div class="search-input-container">
                                <div class="search-input-wrapper">
                                    <!-- Icono de búsqueda -->
                                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <path d="m21 21-4.3-4.3"></path>
                                    </svg>
                                    <!-- Campo de búsqueda -->
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                        placeholder="Buscar por nombre o identificación..."
                                        autocomplete="off"
                                    >
                                </div>
                                <!-- Botón de búsqueda -->
                                <button type="submit" class="btn btn-primary">
                                    Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-wrapper">
                    <div class="swipe-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 5l7 7-7 7"></path>
                            <path d="M3 5l7 7-7 7"></path>
                        </svg>
                        <span>Desliza</span>
                    </div>
                    
                    <!-- Sección de la tabla -->
                    <div class="table-section">
                        <div class="table-container">
                            <!-- Tabla de clientes -->
                            <table class="client-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Empresa</th>
                                        <th>Tipo ID</th>
                                        <th>Identificación</th>
                                        <th>Teléfono</th>
                                        <th>Notas</th>
                                        <th>Límite Crédito</th>
                                        <th>Balance</th>
                                        <th>Dirección</th>
                                        <th>Estado</th>
                                        <?php 
                                            // Verificar si el usuario tiene permisos de administrador
                                            if ($_SESSION['idPuesto'] <= 2) {
                                                echo '<th>Acciones</th>';
                                            }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): 
                                        
                                        // pasar numeros a formato de moneda
                                        $row['limite_credito'] = number_format($row['limite_credito'], 2, '.', ',');
                                        $row['balance'] = number_format($row['balance'], 2, '.', ',');
                                        
                                    ?>
                                        
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombreCompleto']); ?></td>
                                        <td><?php echo htmlspecialchars($row['empresa']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_identificacion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['identificacion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($row['notas']); ?></td>
                                        <td><?php echo htmlspecialchars("RD$ " . $row['limite_credito']); ?></td>
                                        <td><?php echo htmlspecialchars("RD$ " . $row['balance']); ?></td>
                                        <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                                        <td>
                                            <!-- Estado del cliente -->
                                            <span class="status <?php echo $row['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <?php 
                                            // Verificar si el usuario tiene permisos de administrador
                                            if ($_SESSION['idPuesto'] <= 2):
                                        ?>
                                        <td>
                                            <!-- Botón para actualizar el cliente -->
                                            <a href="clientes-actualizar.php?id=<?php echo urlencode($row['id']); ?>" class="btn btn-update">
                                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 2v6h-6M3 22v-6h6"></path>
                                                    <path d="M21 8c0 9.941-8.059 18-18 18"></path>
                                                    <path d="M3 16c0-9.941 8.059-18 18-18"></path>
                                                </svg>
                                                <span>Actualizar</span>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tabla móvil -->
                <div class="mobile-table">
                    
                    <?php 
                        // Reiniciar el puntero del resultado para reutilizarlo
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()): 

                        // pasar numeros a formato de moneda
                        $row['limite_credito'] = number_format($row['limite_credito'], 2, '.', ',');
                        $row['balance'] = number_format($row['balance'], 2, '.', ',');
                    ?>
                    <div class="mobile-record">
                        <div class="mobile-record-header">
                            <div class="mobile-header-info">
                                <h3><?php echo htmlspecialchars($row['nombreCompleto']); ?></h3>
                                <p class="mobile-subtitle"><?php echo htmlspecialchars($row['empresa']); ?></p>
                            </div>
                            <!-- Estado del cliente -->
                            <span class="status <?php echo $row['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                        <div class="mobile-record-content">
                            <div class="mobile-grid">
                                <!-- Información del cliente -->
                                <div class="mobile-info-item">
                                    <div class="mobile-label">ID:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['id']); ?></div>
                                </div>
                                <div class="mobile-info-item">
                                    <div class="mobile-label">Tipo ID:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['tipo_identificacion']); ?></div>
                                </div>
                                <div class="mobile-info-item">
                                    <div class="mobile-label">Identificación:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['identificacion']); ?></div>
                                </div>
                                <div class="mobile-info-item">
                                    <div class="mobile-label">Teléfono:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['telefono']); ?></div>
                                </div>
                                <div class="mobile-info-item">
                                    <div class="mobile-label">Límite Crédito:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars("RD$ " . $row['limite_credito']); ?></div>
                                </div>
                                <div class="mobile-info-item">
                                    <div class="mobile-label">Balance:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars("RD$ " . $row['balance']); ?></div>
                                </div>
                                <div class="mobile-info-item notes-field">
                                    <div class="mobile-label">Notas:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['notas']); ?></div>
                                </div>
                                <div class="mobile-info-item address-field">
                                    <div class="mobile-label">Dirección:</div>
                                    <div class="mobile-value"><?php echo htmlspecialchars($row['direccion']); ?></div>
                                </div>
                                <div class="mobile-actions">
                                    <!-- Botón para actualizar el cliente -->
                                    <a href="clientes-actualizar.php?id=<?php echo urlencode($row['id']); ?>" class="btn btn-update">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 2v6h-6M3 22v-6h6"></path>
                                            <path d="M21 8c0 9.941-8.059 18-18 18"></path>
                                            <path d="M3 16c0-9.941 8.059-18 18-18"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </main>

        <!-- TODO EL CONTENIDO DE LA PAGINA ENCIMA DE ESTA LINEA -->
        </div>
    </div>

    <!-- Scripts adicionales -->
    <script src="js/deslizar.js"></script>

</body>
</html>