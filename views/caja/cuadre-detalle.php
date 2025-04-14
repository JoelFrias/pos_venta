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
    header('Location: views/auth/login.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: views/auth/login.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

// Configuración de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión a la base de datos
require_once '../../models/conexion.php';

// 1. Validación segura del parámetro numCaja
$numCaja = '';
if (isset($_GET['numCaja'])) {
    // Validar que sea exactamente 5 caracteres alfanuméricos (ej: 00003)
    if (preg_match('/^[a-zA-Z0-9]{5}$/', $_GET['numCaja'])) {
        $numCaja = $_GET['numCaja'];
    } else {
        die("Formato de número de caja inválido");
    }
}

// 2. Función segura para consultas preparadas
function executeSecureQuery($conn, $query, $params, $types = '') {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error en preparación de consulta: " . $conn->error);
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        die("Error en ejecución de consulta: " . $stmt->error);
    }
    
    return $stmt;
}

// 3. Consulta segura para datos de caja
$query_caja = "SELECT cc.*, CONCAT(e.nombre, ' ', e.apellido) AS empleado
               FROM cajascerradas cc
               JOIN empleados e ON cc.idEmpleado = e.id
               WHERE cc.numCaja = ?";
$stmt_caja = executeSecureQuery($conn, $query_caja, [$numCaja], 's');
$result_caja = $stmt_caja->get_result();
$caja = $result_caja->fetch_assoc();
$stmt_caja->close();

// 4. Consultas seguras para ingresos/egresos
$query_ingresos = "SELECT monto, metodo, razon, DATE_FORMAT(fecha, '%d/%m/%Y %l:%i %p') AS fecha 
                   FROM cajaingresos 
                   WHERE numCaja = ? 
                   ORDER BY fecha DESC";
$stmt_ingresos = executeSecureQuery($conn, $query_ingresos, [$numCaja], 's');
$result_ingresos = $stmt_ingresos->get_result();

$query_egresos = "SELECT monto, metodo, razon, DATE_FORMAT(fecha, '%d/%m/%Y %l:%i %p') AS fecha  
                  FROM cajaegresos 
                  WHERE numCaja = ? 
                  ORDER BY fecha DESC";
$stmt_egresos = executeSecureQuery($conn, $query_egresos, [$numCaja], 's');
$result_egresos = $stmt_egresos->get_result();

// 5. Función segura para formatear fechas
function formatDate($dateString) {
    if (empty($dateString)) {
        return 'No registrada';
    }
    
    // Eliminar cualquier posible tag HTML o JavaScript
    $dateString = htmlspecialchars(strip_tags($dateString));
    
    // Verificar si es una fecha inválida de MySQL
    if (strpos($dateString, '0000-00-00') !== false) {
        return 'No registrada';
    }
    
    try {
        $date = new DateTime($dateString);
        $now = new DateTime();
        
        // Verificar si la fecha es futura (posible error)
        if ($date > $now) {
            return 'Fecha inválida';
        }
        
        return $date->format('n/j/Y g:i A'); // Cambiado a formato 4/14/2025 1:09 AM
    } catch (Exception $e) {
        return 'Formato inválido';
    }
}

// Calcular totales
$total_ingresos = 0;
$total_egresos = 0;
$ingresos_data = [];
$egresos_data = [];

while($row = $result_ingresos->fetch_assoc()) {
    $total_ingresos += $row['monto'];
    $ingresos_data[] = $row;
}

while($row = $result_egresos->fetch_assoc()) {
    $total_egresos += $row['monto'];
    $egresos_data[] = $row;
}

// Calcular diferencia
$saldo_inicial = $caja['saldoInicial'] ?? 0;
$saldo_final = $caja['saldoFinal'] ?? 0;
$diferencia = ($saldo_inicial + $total_ingresos) - $total_egresos - $saldo_final;

// Cerrar los statements restantes
$stmt_ingresos->close();
$stmt_egresos->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja #<?= htmlspecialchars($numCaja) ?></title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-blanco.png">
    <link rel="stylesheet" href="../../assets/css/menu.css"> <!-- CSS menu -->
    <link href="../../assets/css/cuadre-detalle.css" rel="stylesheet"> <!-- css de bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"> <!-- Librería de iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Librería de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->
    <style>
        :root {
            --primary-color-hola: #0d6efd;
            --secondary-color-hola: #6c757d;
            --success-color-hola: #28a745;
            --danger-color-hola: #dc3545;
            --dark-color-hola: #2c3e50;
            --light-color-hola: #f8f9fa;
        }
        
        body {
            background-color: #f8f9fa;
            padding-bottom: 2rem;
        }
        
        .page-content .report-header {
            background: var(--dark-color-hola);
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-content .card {
            border-radius: 8px;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .page-content .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .page-content .summary-card {
            border-left: 5px solid var(--primary-color-hola);
        }
        
        .page-content .finance-card {
            border-left: 5px solid var(--success-color-hola);
        }
        
        .page-content .card-header {
            font-weight: 600;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        
        .page-content .table-responsive {
            margin-bottom: 0;
        }
        
        .page-content .table {
            margin-bottom: 0;
        }
        
        .page-content .table th {
            background-color: rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            font-weight: 600;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        
        .page-content .table td, .table th {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        .page-content .table tr:last-child td {
            border-bottom: none;
        }
        
        .page-content .positive {
            color: var(--success-color-hola);
            font-weight: 600;
        }
        
        .page-content .negative {
            color: var(--danger-color-hola);
            font-weight: 600;
        }
        
        .page-content .logo {
            max-height: 50px;
        }
        
        .page-content .diferencia-value {
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }
        
        .page-content .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .page-content .no-data {
            color: var(--secondary-color-hola);
            font-style: italic;
            padding: 1.5rem 0;
        }
        
        .page-content .btn {
            padding: 0.5rem 1.25rem;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .page-content .btn-primary {
            background-color: var(--primary-color-hola);
            border-color: var(--primary-color-hola);
        }
        
        .page-content .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
        }
        
        .page-content .btn-secondary {
            background-color: var(--secondary-color-hola);
            border-color: var(--secondary-color-hola);
        }
        
        .page-content .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
            transform: translateY(-2px);
        }
        
        .page-content .actions-container {
            margin-top: 2rem;
        }
        
        .page-content .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
        }
        
        .page-content .info-label {
            color: var(--secondary-color-hola);
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .page-content .info-section {
            margin-bottom: 1rem;
        }
        
        /* Estilos específicos para impresión */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .page-content .container {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .page-content .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .page-content .report-header {
                background: #343a40 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .page-content .actions-container, 
            .page-content .btn {
                display: none !important;
            }
        }
        
        /* Responsive mejorado */
        @media (max-width: 767px) {
            .page-content .card-header h5 {
                font-size: 1rem;
            }
            
            .page-content .table td, .page-content .table th {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .page-content .diferencia-value {
                font-size: 1.1rem;
            }
            
            .page-content .info-value {
                font-size: 1rem;
            }
            
            .page-content .report-header h2 {
                font-size: 1.5rem;
            }
            
            .page-content .report-header h4 {
                font-size: 1.2rem;
            }
        }
        
        /* Estilos para pantallas muy pequeñas */
        @media (max-width: 575px) {
            .page-content .card-body {
                padding: 1rem;
            }
            
            .page-content .report-header {
                flex-direction: column;
                text-align: center;
            }
            
            .page-content .report-header div.text-end {
                text-align: center !important;
                margin-top: 1rem;
            }
            
            .page-content .table-responsive {
                max-height: 300px;
                overflow-y: auto;
            }
        }
    </style>

</head>
<body>
        
<div class="navegator-nav">

    <!-- Menu-->
    <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <div class="container py-4">
                <div class="card">
                    <div class="card-header report-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Detalles de Cuadre</h2>
                            <small class="text-light">Fecha Actual: <?= date('d/m/Y H:i:s') ?></small>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0">Caja: <?= htmlspecialchars($numCaja) ?></h4>
                            <?php if($caja): ?>
                                <small class="text-light">Empleado: <?= htmlspecialchars($caja['empleado']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        <?php if($caja): ?>
                        <div class="row mb-4 g-3">
                            <div class="col-12 col-md-6">
                                <div class="card h-100 summary-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Información de Caja</h5>
                                    </div>
                                    <div class="card-body p-3 p-md-4">
                                        <div class="row g-3">
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-calendar3 me-1"></i> Fecha Apertura:</p>
                                                    <p class="info-value mb-0"><?= formatDate($caja['fechaApertura']) ?></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-calendar-check me-1"></i> Fecha Cierre:</p>
                                                    <p class="info-value mb-0"><?= formatDate($caja['fechaCierre']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-box-arrow-in-right me-1"></i> Saldo Inicial:</p>
                                                    <p class="info-value mb-0">$<?= number_format($caja['saldoInicial'], 2) ?></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-box-arrow-right me-1"></i> Saldo Final:</p>
                                                    <p class="info-value mb-0">$<?= number_format($caja['saldoFinal'], 2) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="card h-100 finance-card">
                                    <div class="card-header bg-white">
                                        <h5 class="mb-0"><i class="bi bi-calculator me-2"></i> Resumen Financiero</h5>
                                    </div>
                                    <div class="card-body p-3 p-md-4">
                                        <div class="row g-3">
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-graph-up-arrow me-1"></i> Total Ingresos:</p>
                                                    <p class="info-value positive mb-0">+ $<?= number_format($total_ingresos, 2) ?></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <div class="info-section">
                                                    <p class="info-label mb-1"><i class="bi bi-graph-down-arrow me-1"></i> Total Egresos:</p>
                                                    <p class="info-value negative mb-0">- $<?= number_format($total_egresos, 2) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="bg-light p-3 rounded text-center">
                                                    <p class="info-label mb-1"><i class="bi bi-currency-exchange me-1"></i> Diferencia:</p>
                                                    <p class="diferencia-value mb-0 <?= ($diferencia >= 0) ? 'positive bg-success bg-opacity-10' : 'negative bg-danger bg-opacity-10' ?>">
                                                        <?= ($diferencia >= 0) ? '+' : '-' ?> $<?= number_format(abs($diferencia), 2) ?>
                                                        <small class="d-block mt-1">(<?= ($diferencia >= 0) ? 'A favor' : 'En contra' ?>)</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-arrow-down-circle me-2"></i> Ingresos</h5>
                                        <span class="badge bg-success"><?= count($ingresos_data) ?> registros</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 400px;">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Monto</th>
                                                        <th>Método</th>
                                                        <th>Descripción</th>
                                                        <th>Fecha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(count($ingresos_data) > 0): ?>
                                                        <?php foreach($ingresos_data as $ingreso): ?>
                                                        <tr>
                                                            <td class="positive">+ $<?= number_format($ingreso['monto'], 2) ?></td>
                                                            <td><?= htmlspecialchars($ingreso['metodo'] ?? 'No especificado') ?></td>
                                                            <td><?= htmlspecialchars($ingreso['razon'] ?? 'Sin descripción') ?></td>
                                                            <td><?= formatDate($ingreso['fecha']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center no-data">No hay registros de ingresos</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="bi bi-arrow-up-circle me-2"></i> Egresos</h5>
                                        <span class="badge bg-danger"><?= count($egresos_data) ?> registros</span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 400px;">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Monto</th>
                                                        <th>Método</th>
                                                        <th>Descripción</th>
                                                        <th>Fecha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(count($egresos_data) > 0): ?>
                                                        <?php foreach($egresos_data as $egreso): ?>
                                                        <tr>
                                                            <td class="negative">- $<?= number_format($egreso['monto'], 2) ?></td>
                                                            <td><?= htmlspecialchars($egreso['metodo'] ?? 'No especificado') ?></td>
                                                            <td><?= htmlspecialchars($egreso['razon'] ?? 'Sin descripción') ?></td>
                                                            <td><?= formatDate($egreso['fecha']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center no-data">No hay registros de egresos</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="actions-container mt-4 text-center">
                            <button class="btn btn-primary me-2 mb-2 mb-md-0" onclick="window.print()"><i class="bi bi-printer me-2"></i> Imprimir Reporte</button>
                            <a href="javascript:history.back()" class="btn btn-secondary mb-2 mb-md-0"><i class="bi bi-arrow-left me-2"></i> Volver</a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i> Caja no encontrada</h4>
                            <p>No se encontró información para la caja <?= htmlspecialchars($numCaja) ?></p>
                            <hr>
                            <p class="mb-0">Verifica el número de caja e intenta nuevamente.</p>
                        </div>
                        <div class="text-center">
                            <a href="javascript:history.back()" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i> Volver</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA ENCIMA DE ESTO -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>