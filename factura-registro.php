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

$sql = "SELECT
            f.numFactura AS numf,
            f.tipoFactura AS tipof,
            DATE_FORMAT(f.fecha, '%d/%m/%Y %l:%i %p') AS fechaf,
            f.total_ajuste AS totalf,
            CONCAT(c.nombre, ' ', c.apellido) AS nombrec,
            f.balance AS balancef,
            CONCAT(e.nombre, ' ', e.apellido) AS nombree,
            f.estado AS estadof
        FROM
            facturas AS f
        JOIN clientes AS c ON c.id = f.idCliente
        JOIN empleados AS e ON e.id = f.idEmpleado
        WHERE 1=1";

$params = [];
$types = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['tipo'])) {
        $sql .= " AND f.tipoFactura = ?";
        $params[] = $_POST['tipo'];
        $types .= "s";
    }
    if (!empty($_POST['estado'])) {
        $sql .= " AND f.estado = ?";
        $params[] = $_POST['estado'];
        $types .= "s";
    }
    if (!empty($_POST['desde'])) {
        $sql .= " AND f.fecha >= ?";
        $params[] = $_POST['desde'];
        $types .= "s";
    }
    if (!empty($_POST['hasta'])) {
        $sql .= " AND f.fecha <= ?";
        $params[] = $_POST['hasta'];
        $types .= "s";
    }
    if (!empty($_POST['buscador'])) {
        $sql .= " AND (f.numFactura LIKE ? OR CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR CONCAT(e.nombre, ' ', e.apellido) LIKE ?)";
        $searchTerm = "%" . $_POST['buscador'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
}

$sql .= " GROUP BY f.numFactura ORDER BY f.fecha DESC LIMIT 30";

$stmt = $conn->prepare($sql);
if (!empty($params)) {$stmt->bind_param($types, ...$params);}
$stmt->execute();
$results = $stmt->get_result();

$stmt1 = $conn->prepare($sql);
if (!empty($params)) {$stmt1->bind_param($types, ...$params);}
$stmt1->execute();
$results1 = $stmt1->get_result();

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Facturas</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <link rel="stylesheet" href="css/menu.css"> <!-- CSS menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Importación de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->

    <style>
        :root {
            --primary-blue: #4285f4;
            --hover-blue: #2b7de9;
            --background:rgb(252, 252, 252);
            --card-bg::rgb(252, 252, 252);
            --border: #e0e4ec;
            --text-secondary: #718096;
            --success: #48bb78;
            --warning: #ed8936;
            --shadow: 0 2px 4px rgba(0,0,0,0.08);
            --radius: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--background);
         
        }

        .contenedor {
            max-width: 1400px;
            margin: 0 auto;
        }

        .cabeza {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cabeza h1 {
            font-size: 24px;
            font-weight: 600;
            margin-top: 45px;
            margin-left: 10px; /* Agrega margen a la izquierda */
           
   
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        input, select {
            padding: 0.625rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            background: var(--card-bg);
            transition: all 0.2s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .search-input {
            flex: 1;
            position: relative;
        }

        .search-input i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-input input {
            width: 100%;
            padding-left: 2.5rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--hover-blue);
        }

        .btn-secondary {
            background: var(--card-bg);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--background);
        }

        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: block;
        }

        .mobile-cards {
            display: none;
            gap: 1rem;
            margin-top: 1rem;
        }

        .mobile-cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .invoice-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow);
        }

        .invoice-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .invoice-number {
            font-weight: 600;
        }

        .invoice-card-body {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .invoice-detail {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 0.875rem;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--background);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }

        tr:hover {
            background: var(--background);
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-paid {
            background: #e6ffec;
            color: var(--success);
        }

        .status-pending {
            background: #fff5e6;
            color: var(--warning);
        }

        .status-cancel {
            background:rgb(252, 206, 206);
            color: rgb(252, 85, 85);
        }

        .note {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            /* .cabeza {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            } */

            .filters {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .search-bar {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                display: none;
            }

            .mobile-cards {
                display: block;
            }

            .invoice-detail {
                padding: 0.75rem;
                background: var(--background);
                border-radius: var(--radius);
                border: none;
            }
        }

        @media (min-width: 390px) and (max-width: 768px) {
            .filters {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .mobile-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Estilo específico para el botón en tarjetas móviles */
        @media (max-width: 768px) {
            .invoice-card-body > div:last-child {
                display: flex;
                justify-content: flex-end;
                grid-column: 1 / -1; /* Ocupa todo el ancho disponible */
                margin-top: 4px;
            }
            
            .invoice-card-body > div:last-child .btn {
                width: auto; /* Ancho automático en lugar de 100% */
            }
        }

        /* Ajuste para pantallas muy pequeñas */
        @media (max-width: 480px) {
            .invoice-card-body > div:last-child {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>

    <div class="navegator-nav">

        <!-- Menu-->
        <?php include 'menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <!-- Contenedor principal -->
            <div class="contenedor">
                <div class="cabeza">
                    <h1>Registro de Facturas</h1>
                </div>
        
                <form action="" method="post">
                    <div class="card">
                        <div class="filters">
                            <div class="filter-group">
                                <label>Desde</label>
                                <input type="date" name="desde" value="<?php echo isset($_POST['desde']) ? $_POST['desde'] : ''; ?>">
                            </div>
                            <div class="filter-group">
                                <label>Hasta</label>
                                <input type="date" name="hasta" value="<?php echo isset($_POST['hasta']) ? $_POST['hasta'] : ''; ?>">
                            </div>
                            <div class="filter-group">
                                <label>Tipo de Factura</label>
                                <select name="tipo" id="tipo">
                                    <option value="" disabled selected>Seleccionar</option>
                                    <option value="credito" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'credito') ? 'selected' : ''; ?>>Crédito</option>
                                    <option value="contado" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'contado') ? 'selected' : ''; ?>>Contado</option>
                                </select>

                            </div>
                            <div class="filter-group">
                                <label>Estado de Factura</label>
                                <select name="estado" id="estado">
                                    <option value="" disabled selected>Seleccionar</option>
                                    <option value="Pagada" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Pagada') ? 'selected' : ''; ?>>Pagada</option>
                                    <option value="Pendiente" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="Cancelada" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>
                        </div>

                        <div class="search-bar">
                            <div class="search-input">
                                <i class="fas fa-search"></i>
                                <input type="text" id="buscador" name="buscador" value="<?php echo isset($_POST['buscador']) ? $_POST['buscador'] : ''; ?>" placeholder="Buscar factura por número, cliente o vendedor">
                            </div>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                                Buscar
                            </button>
                            <button class="btn btn-secondary" type="reset" onclick="window.location.href='factura-registro.php'">
                                <i class="fas fa-redo"></i>
                                Limpiar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Desktop Table View -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Factura</th>
                                <th>Tipo</th>
                                <th>Fecha y Hora</th>
                                <th>Total</th>
                                <th>Cliente</th>
                                <th>Balance</th>
                                <th>Vendedor</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                                if ($results->num_rows > 0) {
                                    while ($row = $results->fetch_assoc()) {
                                        // FORMATO DE MONEDA
                                        $totalf = number_format($row['totalf'], 2, '.', ',');
                                        $balancef = number_format($row['balancef'], 2, '.', ',');

                                        // Determinar la clase CSS del estado
                                        $estadoClass = "";
                                        if ($row['estadof'] == "Pagada") {
                                            $estadoClass = "paid";
                                        } elseif ($row['estadof'] == "Pendiente") {
                                            $estadoClass = "pending";
                                        } elseif ($row['estadof'] == "Cancelada") {
                                            $estadoClass = "cancel";
                                        }

                                        echo "
                                            <tr>
                                                <td>{$row['numf']}</td>
                                                <td>{$row['tipof']}</td>
                                                <td>{$row['fechaf']}</td>
                                                <td>RD$ {$totalf}</td>
                                                <td>{$row['nombrec']}</td>
                                                <td>RD$ {$balancef}</td>
                                                <td>{$row['nombree']}</td>
                                                <td><span class='status status-{$estadoClass}'>{$row['estadof']}</span></td>
                                                <td><button class='btn btn-secondary' onclick=\"window.location.href='factura-detalle.php?numFactura={$row['numf']}'\">Ver Detalles</button></td>
                                            </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr>
                                            <td colspan='9'>No se encontraron resultados.</td>
                                        </tr>";
                                }
                            ?>

                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View -->
                <!-- Mobile Cards View -->
                <div class="mobile-cards">
                    <div class="mobile-cards-grid">
                        <?php
                        if ($results1->num_rows > 0) {
                            while ($row1 = $results1->fetch_assoc()) {
                                // FORMATO DE MONEDA
                                $totalf1 = number_format($row1['totalf'], 2, '.', ',');
                                $balancef1 = number_format($row1['balancef'], 2, '.', ',');

                                // Determinar la clase CSS del estado
                                $estadoClass1 = "";
                                if ($row1['estadof'] == "Pagada") {
                                    $estadoClass1 = "paid";
                                } elseif ($row1['estadof'] == "Pendiente") {
                                    $estadoClass1 = "pending";
                                } elseif ($row1['estadof'] == "Cancelada") {
                                    $estadoClass1 = "cancel";
                                }
                        ?>
                                <!-- Factura individual -->
                                <div class="invoice-card">
                                    <div class="invoice-card-header">
                                        <span class="invoice-number">No. <?php echo $row1['numf']; ?></span>
                                        <span class="status status-<?php echo $estadoClass1; ?>"><?php echo $row1['estadof']; ?></span>
                                    </div>
                                    <div class="invoice-card-body">
                                        <div class="invoice-detail">
                                            <span class="detail-label">Cliente</span>
                                            <span class="detail-value"><?php echo $row1['nombrec']; ?></span>
                                        </div>
                                        <div class="invoice-detail">
                                            <span class="detail-label">Tipo</span>
                                            <span class="detail-value"><?php echo $row1['tipof']; ?></span>
                                        </div>
                                        <div class="invoice-detail">
                                            <span class="detail-label">Fecha y Hora</span>
                                            <span class="detail-value"><?php echo $row1['fechaf']; ?></span>
                                        </div>
                                        <div class="invoice-detail">
                                            <span class="detail-label">Total</span>
                                            <span class="detail-value">RD$ <?php echo $totalf1; ?></span>
                                        </div>
                                        <div class="invoice-detail">
                                            <span class="detail-label">Balance</span>
                                            <span class="detail-value">RD$ <?php echo $balancef1; ?></span>
                                        </div>
                                        <div class="invoice-detail">
                                            <span class="detail-label">Cajero</span>
                                            <span class="detail-value"><?php echo $row1['nombree']; ?></span>
                                        </div>
                                        <div class="">
                                            <button class='btn btn-secondary' onclick="window.location.href='factura-detalle.php?numFactura=<?php echo $row1['numf']; ?>'">Ver Detalles</button>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "<p class=\"note\">No se encontraron resultados.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA ARRIBA DE ESTA LINEA -->
        </div>
    </div>

</body>
</html>