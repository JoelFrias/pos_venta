<?php

    /* Verificacion de sesion */

    // Iniciar sesión
    session_start();

    // Configurar el tiempo de caducidad de la sesión
    $inactivity_limit = 9000; // 15 minutos en segundos

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

    require_once '../../models/conexion.php';

    /* Fin de verificacion de sesion */

    // Tabla Bancos
    $stmtb = $conn->prepare("SELECT id AS idBank, nombreBanco AS namebanks FROM bancos WHERE id <> 1 AND enable = 1");
    $stmtb->execute();
    $resultsb = $stmtb->get_result();

    // Tabla Destinos
    $stmtd = $conn->prepare("SELECT id AS idDestination, descripcion AS namedestinations FROM destinocuentas WHERE id <> 1 AND enable = 1");
    $stmtd->execute();
    $resultsd = $stmtd->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Panel Administrativo</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-blanco.png">
    <link rel="stylesheet" href="../../assets/css/menu.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Libreria de alertas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Libreria de graficos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        .tittle h1 {
            text-align: center;
            margin: 5px 0 30px;
            color: #2c3e50;
            font-size: 32px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
            letter-spacing: 1px;
            position: relative;
        }

        .tittle h1:after {
            content: '';
            position: absolute;
            width: 30%;
            height: 3px;
            background-color: #e74c3c;
            bottom: -3px;
            left: 35%;
        }

        .conteiner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Estilos para botones principales */
        #buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }

        #buttons div {
            flex: 1;
            min-width: 200px;
        }

        #buttons button {
            width: 100%;
            padding: 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        #buttons button:hover {
            background-color: #34495e;
            transform: translateY(-2px);
        }

        #buttons button:active {
            transform: translateY(0);
        }

        /* Estilos para el contenedor de filtros */
        #filters {
            display: flex;
            justify-content: left;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        /* Estilos para el select */
        #filters select {
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
            color: #333;
            transition: border-color 0.3s ease;
            font-weight: 600;
        }

        #filters select:focus {
            border-color: #3498db;
            outline: none;
        }

        /* Estilos para el botón de aplicar */
        #btn-filters {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        #btn-filters:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        #btn-filters:active {
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            #filters {
                flex-direction: column;
                gap: 10px;
            }

            #filters select, #btn-filters {
                width: 100%;
            }
        }

        /* Estilos para modales */
        #modal-banks, #modal-destinations, #edit-banks, #edit-destinations {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            z-index: 1000;
            max-height: 90vh;
            overflow-y: auto;
        }
        #modal-banks, #modal-destinations {
            max-width: 600px;
        }

        #edit-banks, #edit-destinations {
            max-width: 400px;
        }

        /* Fondo oscuro para modales */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Estilos para los botones de cerrar */
        .close-modal-banks, .close-modal-destinations, .close-edit-banks, .close-edit-destinations {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal-banks:hover, .close-modal-destinations:hover, .close-edit-banks:hover, .close-edit-destinations:hover {
            color: #e74c3c;
            background-color: #f7f7f7;
        }

        /* Estilos mejorados para los formularios de destinos y bancos */
        #new-destination, #new-bank {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        #new-destination:hover, #new-bank:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        #new-destination label, #new-bank label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }

        #new-destination input[type="text"], #new-bank input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        #new-destination input[type="text"]:focus, #new-bank input[type="text"]:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.15);
            outline: none;
        }

        #new-destination button[type="submit"], #new-bank button[type="submit"] {
            width: 100%;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            text-align: center;
        }

        #new-destination button[type="submit"]:hover, #new-bank button[type="submit"]:hover {
            background-color: #2ecc71;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #new-destination button[type="submit"]:active, #new-bank button[type="submit"]:active {
            transform: translateY(0);
        }

        /* Responsive para formularios */
        @media (max-width: 768px) {
            #new-destination, #new-bank {
                padding: 15px;
            }
            
            #new-destination input[type="text"], #new-bank input[type="text"],
            #new-destination button[type="submit"], #new-bank button[type="submit"] {
                padding: 10px 12px;
            }
        }

        /* Estilos para tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        table button {
            padding: 5px 10px;
            background-color: transparent;
            border: none;
            cursor: pointer;
            margin-right: 5px;
        }

        table button i.fa-trash {
            color: #e74c3c;
        }

        table button i.fa-pen-to-square {
            color: #3498db;
        }

        table button:hover i {
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            #buttons {
                flex-direction: column;
            }
            
            #buttons div {
                width: 100%;
            }
            
            table th, table td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            #modal-banks, #modal-destinations, #edit-banks, #edit-destinations {
                width: 95%;
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .conteiner {
                padding: 10px;
            }
            
            table {
                font-size: 12px;
            }
            
            table th, table td {
                padding: 6px 8px;
            }
            
            h3 {
                font-size: 18px;
            }
        }

        /* Estilos mejorados para los botones de acción en tablas */
        table button.delete-bank, 
        table button.edit-bank,
        table button.delete-destination, 
        table button.edit-destination {
            padding: 8px 12px;
            background-color: transparent;
            border: none;
            cursor: pointer;
            margin-right: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        /* Añadir un ligero fondo al hacer hover */
        table button.delete-bank:hover, 
        table button.delete-destination:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }

        table button.edit-bank:hover,
        table button.edit-destination:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        /* Estilos para los iconos */
        table button i.fa-trash {
            color: #e74c3c;
            font-size: 16px;
        }

        table button i.fa-pen-to-square {
            color: #3498db;
            font-size: 16px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button[type="submit"], #update-edit-bank, #update-edit-destination {
            padding: 10px 15px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 47%;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        button[type="submit"]:hover, #update-edit-bank:hover, #update-edit-destination:hover {
            background-color: #2ecc71;
        }

        #cancel-edit-bank, #cancel-edit-destination {
            padding: 10px 15px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            width: 47%;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #cancel-edit-bank:hover, #cancel-edit-destination:hover {
            background-color: #c0392b;
        }

        /* Estilos específicos para móviles */
        @media (max-width: 480px) {
            /* Hacer que los botones sean más grandes y tengan mejor espacio entre ellos */
            table button.delete-bank, 
            table button.edit-bank,
            table button.delete-destination, 
            table button.edit-destination {
                padding: 10px 12px;
                margin: 3px;
                display: inline-block;
                min-width: 40px;
                text-align: center;
            }
            
            /* Aumentar tamaño de iconos en móvil */
            table button i.fa-trash,
            table button i.fa-pen-to-square {
                font-size: 18px;
            }
            
            /* Ajustar el contenedor de los botones */
            table td:last-child {
                display: flex;
                justify-content: flex-start;
                align-items: center;
                padding: 10px 8px;
                flex-wrap: nowrap;
            }
        }

        /* Para pantallas muy pequeñas */
        @media (max-width: 360px) {
            table button.delete-bank, 
            table button.edit-bank,
            table button.delete-destination, 
            table button.edit-destination {
                min-width: 36px;
                padding: 8px 10px;
            }
        }

        /* Estilos para el dashboard de gráficos */
        
        #dashboard h2 {
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                margin-bottom: 15px;
            }
        }

        /* Estilos para el menú móvil */
        .mobile-menu-toggle {
            display: none;
            width: 100%;
            padding: 15px;
            color: rgb(55, 63, 71);
            border: 3px solid rgb(55, 63, 71);
            border-radius: 10px;
            cursor: pointer;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:focus {
            background-color: rgb(55, 63, 71);
            color: white;
        }

        /* Ocultar botones en móvil y preparar animación */
        @media (max-width: 768px) {
            #buttons {
                display: flex;
                flex-direction: column;
                gap: 0;
            }
            
            #buttons > div {
                max-height: 0;
                overflow: hidden;
                opacity: 0;
                transition: max-height 0.4s ease-out, opacity 0.3s ease;
                min-width: 100%;
            }
            
            #buttons.active > div {
                max-height: 100px;
                opacity: 1;
                margin-bottom: 10px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            /* Asegurar que los botones ocupen todo el ancho */
            #buttons button {
                width: 100%;
            }
        }

        /* Estilos para escritorio */
        @media (min-width: 769px) {
            #buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 30px;
                justify-content: center;
            }
            
            #buttons > div {
                flex: 1;
                min-width: 200px;
            }
            
            .mobile-menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Autenticacion de usuario -->
    <?php
        if ($_SESSION['idPuesto'] > 2) {
            echo "<script>
                    Swal.fire({
                            icon: 'error',
                            title: 'Acceso Prohibido',
                            text: 'Usted no cuenta con permisos de administrador para entrar a esta pagina.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.href = '../../index.php';
                        });
                </script>";
            exit();
        }
    ?>

    <div class="navegator-nav">

        <!-- Menu-->
        <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">

            <!-- TODO EL CONTENIDO DE LA PAGINA VA AQUI DEBAJO -->

            <div class="tittle">
                <h1>Panel Administrativo</h1>
            </div>

            <!-- Desplegar menú móvil -->
            <div>
                <button class="mobile-menu-toggle">Menú Administrativo  ▼</button>
            </div>

            <!-- Botones principales -->
            <div id="buttons">
                <div id="div-banks">
                    <button id="manager-banks">Administrar Bancos</button>
                </div>
                <div id="div-destinations">
                    <button id="manager-destinations">Administrar Destinos</button>
                </div>
                <div id="div-users">
                    <button id="manager-users" onclick="redirectUsers()">Administrar Usuarios</button>
                </div>
                <div id="div-employees">
                    <button id="manager-employees" onclick="redirectEmployee()">Administrar Empleados</button>
                </div>
                <div id="div-cashiers">
                    <button id="manager-cashiers">Cuadres de Caja</button>
                </div>
                <div id="div-transactions-inventory">
                    <button id="transactions-inventory" onclick="inventario_transaccion()">Trasancciones de Inventario</button>
                </div>
                <div id="div-inventory">
                    <button id="manager-inventory" onclick="gestion_inventario()">Administrar Inventario</button>
                </div>
            </div>

            <!-- Dashboard de estadisticas -->
            <div id="dashboard">
                <h2>Dashboard de Estadísticas Administrativas</h2>

                <div id="filters">
                    <label for="months">Periodo:</label>
                    <select name="months" id="months">
                        <option value="current" <?php echo (isset($_GET['periodo']) && $_GET['periodo'] == 'current') ? 'selected' : ''; ?>>Mes Actual</option>
                        <option value="previous" <?php echo (isset($_GET['periodo']) && $_GET['periodo'] == 'previous') ? 'selected' : ''; ?>>Mes Anterior</option>

                    </select>

                    <button id="btn-filters" name="btn-filters" onclick="recargar()"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
                
                <div class="dashboard-grid">
                    <!-- Gráfico 1: Ventas Totales -->
                    <div class="chart-container">
                        <div class="chart-title">Ventas Totales</div>
                        <div class="chart-wrapper">
                            <canvas id="ventas-totales"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico 2: Ganancias Totales -->
                    <div class="chart-container">
                        <div class="chart-title">Ganancias Totales</div>
                        <div class="chart-wrapper">
                            <canvas id="ganancias-totales"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico 3: Ventas por Empleado -->
                    <div class="chart-container">
                        <div class="chart-title">Ventas por Empleado</div>
                        <div class="chart-wrapper">
                            <canvas id="ventas-empleados"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico 4: Número de Ventas por Empleado -->
                    <div class="chart-container">
                        <div class="chart-title">Número de Ventas por Empleado</div>
                        <div class="chart-wrapper">
                            <canvas id="num-ventas-empleado"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico 5: Productos más Vendidos -->
                    <div class="chart-container">
                        <div class="chart-title">Productos más Vendidos</div>
                        <div class="chart-wrapper">
                            <canvas id="productos-vendidos"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico 6: Productos en Reorden -->
                    <div class="chart-container">
                        <div class="chart-title">Productos en Punto de Reorden</div>
                        <div class="chart-wrapper">
                            <canvas id="productos-reorden"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para bancos -->
            <div id="modal-banks" style="display: none;">

                <span class="close-modal-banks">&times;</span>
                
                <h3>Bancos</h3>

                <div id="new-bank">
                    <label for="bank-name">Agregar Nuevo Banco:</label>
                    <input type="text" id="bank-name" name="bank-name" autocomplete="off">
                    <button type="submit" onclick="addBank()">Agregar</button>
                </div>

                <div id="bank-list">

                    <h4>Lista de Bancos</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre del Banco</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="bank-table-body">
                            <?php
                                if($resultsb->num_rows > 0){
                                    while ($rowb = $resultsb->fetch_assoc()) {
                                        echo "
                                            <tr data-id='{$rowb['idBank']}' data-name='{$rowb['namebanks']}'>
                                                <td>{$rowb['namebanks']}</td>
                                                <td>
                                                    <button class='delete-bank' onclick=\"deleteBank({$rowb['idBank']})\"><i class=\"fa-solid fa-trash\"></i></button>
                                                    <button class='edit-bank'><i class=\"fa-regular fa-pen-to-square\"></i></button>
                                                </td>
                                            </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No se encontraron resultados.</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
            
            <!-- Modal para editar bancos -->
            <div id="edit-banks" style="display: none;">
                
                <span class="close-edit-banks">&times;</span>

                <h3>Editar Banco</h3>
                <label for="edit-bank-name">Nombre del Banco:</label>
                <input type="hidden" id="edit-bank-id" name="edit-bank-id"> <!-- ID oculto para el banco -->
                <input type="text" id="edit-bank-name" name="edit-bank-name"  autocomplete="off">
                <button id="update-edit-bank" onclick="updateBank()">Actualizar</button>
                <button id="cancel-edit-bank">Cancelar</button>

            </div>

            <!-- Modal para destinos -->
            <div id="modal-destinations" style="display: none;">
                
                <span class="close-modal-destinations">&times;</span>

                <h3>Destinos</h3>

                <div id="new-destination">
                    <label for="destination-name">Agregar Nuevo Destino:</label>
                    <input type="text" id="destination-name" name="destination-name" autocomplete="off">
                    <button type="submit" onclick="addDestination()">Agregar</button>
                </div>
                
                <div id="destination-list">

                    <h4>Lista de Destinos</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre del Destino</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="destination-table-body">
                            <?php
                                if($resultsd->num_rows > 0){
                                    while ($rowd = $resultsd->fetch_assoc()) {
                                        echo "
                                            <tr data-id='{$rowd['idDestination']}' data-name='{$rowd['namedestinations']}'>
                                                <td>{$rowd['namedestinations']}</td>
                                                <td>
                                                    <button class='delete-destination' onclick=\"deleteDestination({$rowd['idDestination']})\"><i class=\"fa-solid fa-trash\"></i></button>
                                                    <button class='edit-destination'><i class=\"fa-regular fa-pen-to-square\"></i></button>
                                                </td>
                                            </tr>
                                        ";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>No se encontraron resultados.</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>

            <!-- Modal para editar destinos -->
            <div id="edit-destinations" style="display: none;">

                <span class="close-edit-destinations">&times;</span>

                <h3>Editar Destino</h3>
                <label for="edit-destination-name">Nombre del Destino:</label>
                <input type="hidden" id="edit-destination-id" name="edit-destination-id"> <!-- ID oculto para el destino -->
                <input type="text" id="edit-destination-name" name="edit-destination-name" autocomplete="off" autocomplete="off">
                <button id="update-edit-destination" onclick="updateDestination()">Actualizar</button>
                <button id="cancel-edit-destination">Cancelar</button>

            </div>

            <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR POR ENCIMA DE ESTA LINEA -->
        </div>
    </div>
    
    <!-- Script para manipular los bancos y destinos -->
    <script>

        function deleteBank(id){

            const datos = {
                idBank: id
            };

            // console.log("Enviando datos:", datos);
            // return;

            fetch("../../controllers/admin/admin-delete-bank.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => response.text())
            .then(text => {
                // console.log("Respuesta completa del servidor:", text);
                try {
                    let data = JSON.parse(text);
                    if (data.success) {

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Banco eliminado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Eliminar la fila de la tabla
                        const row = document.querySelector(`tr[data-id='${id}']`);
                        if (row) {
                            row.remove();
                        }

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al borrar el banco:", data.error);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                    console.error("Error: Respuesta no es JSON válido:", text);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error de red o servidor:", error);
            });
        }

        function updateBank(){

            const datos = {
                idBank: document.getElementById('edit-bank-id').value,
                nombre: document.getElementById('edit-bank-name').value
            };

            // console.log("Enviando datos:", datos);
            // return;

            fetch("../../controllers/admin/admin-update-bank.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => response.text())
            .then(text => {
                // console.log("Respuesta completa del servidor:", text);
                try {
                    let data = JSON.parse(text);
                    if (data.success) {

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Banco actualizado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Actualizar la fila de la tabla
                        const row = document.querySelector(`tr[data-id='${datos.idBank}']`);
                        if (row) {
                            row.dataset.name = datos.nombre;
                            row.querySelector('td').textContent = datos.nombre;
                        }

                        // Cerrar el modal de edición
                        const editModal = document.getElementById('edit-banks');
                        editModal.style.display = 'none';
                        const overlay = document.querySelector('.modal-overlay');
                        if (overlay) {
                            overlay.remove();
                        }

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al actualizar el banco:", data.error);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                    console.error("Error: Respuesta no es JSON válido:", text);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error de red o servidor:", error);
            });
        }

        function deleteDestination(id){
            const datos = {
                idDestination: id
            };

            fetch("../../controllers/admin/admin-delete-destination.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => response.text())
            .then(text => {
                try {
                    let data = JSON.parse(text);
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Destino eliminado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Eliminar la fila de la tabla - FIXED SELECTOR
                        const row = document.querySelector(`#destination-table-body tr[data-id='${id}']`);
                        if (row) {
                            row.remove();
                        } else {
                            console.log("No se encontró la fila a eliminar con id:", id);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al borrar el destino:", data.error);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                    console.error("Error: Respuesta no es JSON válido:", text);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error de red o servidor:", error);
            });
        }

        function updateDestination(){
            const idDestino = document.getElementById('edit-destination-id').value;
            const nombreDestino = document.getElementById('edit-destination-name').value;
            
            const datos = {
                idDestino: idDestino,
                nombre: nombreDestino
            };

            fetch("../../controllers/admin/admin-update-destination.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => response.text())
            .then(text => {
                try {
                    let data = JSON.parse(text);
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Destino actualizado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Actualizar la fila de la tabla - FIXED SELECTOR
                        const row = document.querySelector(`#destination-table-body tr[data-id='${idDestino}']`);
                        if (row) {
                            row.dataset.name = nombreDestino;
                            row.querySelector('td').textContent = nombreDestino;
                        } else {
                            console.log("No se encontró la fila a actualizar con id:", idDestino);
                        }

                        // Cerrar el modal de edición
                        const editModal = document.getElementById('edit-destinations');
                        editModal.style.display = 'none';
                        const overlay = document.querySelector('.modal-overlay');
                        if (overlay) {
                            overlay.remove();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al actualizar destino:", data.error);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                    console.error("Error: Respuesta no es JSON válido:", text);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error de red o servidor:", error);
            });
        }

    </script>

    <!-- Script para manipular las funciones de redireccionamiento -->
    <script>

        idPuesto = <?php echo $_SESSION['idPuesto']; ?>;

        function redirectEmployee() {
            
            if (idPuesto > 2) {
                Swal.fire({
                    title: 'Acceso bloqueado',
                    text: 'No tienes permiso para realizar esta acción.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                window.location.href = "../../views/empleados/empleados.php";
            }
        }

        function redirectUsers() {
            if (idPuesto > 2) {
                Swal.fire({
                    title: 'Acceso bloqueado',
                    text: 'No tienes permiso para realizar esta acción.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                window.location.href = "../../views/gestion/usuarios-editar.php";
            }
        }
        
        function inventario_transaccion() {
            if (idPuesto > 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso bloqueado',
                    text: 'No tienes permiso para realizar esta acción.'
                });
            } else {
                window.location.href = "../../views/inventario/inventario-transaccion.php";
            }
        }

        function gestion_inventario() {
            if (idPuesto > 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso bloqueado',
                    text: 'No tienes permiso para realizar esta acción.'
                });
            } else {
                navigateTo('../../views/inventario/inventario-gestion.php');
            }
        }

        // Manejar menu administrativo en movil
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const buttonsContainer = document.getElementById('buttons');
            
            menuToggle.addEventListener('click', function() {
                buttonsContainer.classList.toggle('active');
                
                // Cambiar el ícono del botón
                if (buttonsContainer.classList.contains('active')) {
                    this.innerHTML = 'Menú Administrativo ▲';
                } else {
                    this.innerHTML = 'Menú Administrativo ▼';
                }
            });
            
            // Cerrar el menú cuando se hace clic en un botón (solo móvil)
            if (window.innerWidth <= 768) {
                document.querySelectorAll('#buttons > div:not(:first-child) button').forEach(button => {
                    button.addEventListener('click', function() {
                        buttonsContainer.classList.remove('active');
                        menuToggle.innerHTML = 'Menú Administrativo  ▼';
                    });
                });
            }
            
            // Manejar redimensionamiento de la ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    buttonsContainer.classList.remove('active');
                    menuToggle.innerHTML = 'Menú Administrativo  ▼';
                }
            });
        });

    </script>

    <!-- Script para graficos -->
    <script>

        function recargar() {
            window.location.href = "?periodo="+document.getElementById("months").value;
        }

        function cargarGraficos() {

            let periodo = "<?php echo isset($_GET['periodo']) ? $_GET['periodo'] : 'current'; ?>";

            fetch(`../../assets/graphics/admin/ventas-totales.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                const dias = data.map(item => item.dia);
                const ventas = data.map(item => item.ventas);

                const ctx = document.getElementById('ventas-totales').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: dias,
                        datasets: [{
                            label: 'Ventas Totales ($)',
                            data: ventas,
                            backgroundColor: 'rgba(54, 150, 214, 0.92)',
                            borderColor: 'rgba(62, 101, 127, 0.92)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));

            // Gráfico para ganancias totales
            fetch(`../../assets/graphics/admin/ganancias-totales.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                const dias = data.map(item => item.dia);
                const ganancias = data.map(item => item.ganancias);

                const ctx = document.getElementById('ganancias-totales').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dias,
                        datasets: [{
                            label: 'Ganancias Totales ($)',
                            data: ganancias,
                            backgroundColor: 'rgba(46, 204, 113, 0.2)',
                            borderColor: 'rgba(39, 174, 96, 1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));

            // Gráfico para ventas por empleado
            fetch(`../../assets/graphics/admin/ventas-empleados.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                const empleados = data.map(item => item.empleado);
                const ventas = data.map(item => item.ventas);

                const ctx = document.getElementById('ventas-empleados').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: empleados,
                        datasets: [{
                            label: 'Ventas Totales ($)',
                            data: ventas,
                            backgroundColor: 'rgba(155, 89, 182, 0.7)',
                            borderColor: 'rgba(142, 68, 173, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));

            // Número de ventas por empleado
            fetch(`../../assets/graphics/admin/numero-ventas-e.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                const empleados = data.map(item => item.empleado);
                const ventas = data.map(item => item.numero_ventas);

                const ctx = document.getElementById('num-ventas-empleado').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: empleados,
                        datasets: [{
                            label: 'Número de Ventas',
                            data: ventas,
                            backgroundColor: [
                                'rgba(255, 183, 77, 0.7)',  // Naranja suave pero vibrante
                                'rgba(129, 199, 132, 0.7)', // Verde menta elegante
                                'rgba(100, 181, 246, 0.7)', // Azul cielo armónico
                                'rgba(244, 143, 177, 0.7)', // Rosa coral sutil
                                'rgba(77, 182, 172, 0.7)',  // Verde azulado moderno
                                'rgba(171, 71, 188, 0.7)'   // Morado pastel sofisticado
                            ],
                            borderColor: [
                                'rgba(255, 183, 77, 1)',
                                'rgba(129, 199, 132, 1)',
                                'rgba(100, 181, 246, 1)',
                                'rgba(244, 143, 177, 1)',
                                'rgba(77, 182, 172, 1)',
                                'rgba(171, 71, 188, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right'
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));

            // Gráfico de productos más vendidos
            fetch(`../../assets/graphics/admin/productos-vendidos.php?periodo=${periodo}`)
            .then(response => response.json())
            .then(data => {
                const productos = data.map(item => item.producto);
                const ventas = data.map(item => item.total_vendido);

                const ctx = document.getElementById('productos-vendidos').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: productos,
                        datasets: [{
                            label: 'Cantidad Vendida',
                            data: ventas,
                            backgroundColor: 'rgba(230, 126, 34, 0.7)',
                            borderColor: 'rgba(211, 84, 0, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));

            // Gráfico para productos en reorden
            fetch('../../assets/graphics/admin/producto-reorden.php')
            .then(response => response.json())
            .then(data => {
                const productos = data.map(item => item.producto);
                const stock = data.map(item => item.stock);
                const stockMinimo = data.map(item => item.stock_minimo);

                const ctx = document.getElementById('productos-reorden').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: productos,
                        datasets: [{
                            label: 'Stock Actual',
                            data: stock,
                            backgroundColor: 'rgba(231, 76, 60, 0.7)',
                            borderColor: 'rgba(192, 57, 43, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Stock Mínimo',
                            data: stockMinimo,
                            backgroundColor: 'rgba(241, 196, 15, 0.7)',
                            borderColor: 'rgba(243, 156, 18, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error cargando los datos:', error));
        }

        cargarGraficos(); // Cargar graficos al entrar a la pagina

    </script>

    <!-- Script para manipular los modales -->
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos de los modales
            const modalBanks = document.getElementById('modal-banks');
            const modalDestinations = document.getElementById('modal-destinations');
            const editBanks = document.getElementById('edit-banks');
            const editDestinations = document.getElementById('edit-destinations');
            
            // Botones para abrir modales
            const btnManagerBanks = document.getElementById('manager-banks');
            const btnManagerDestinations = document.getElementById('manager-destinations');
            
            // Elementos para cerrar modales
            const closeModalBanks = document.querySelector('.close-modal-banks');
            const closeModalDestinations = document.querySelector('.close-modal-destinations');
            const closeEditBanks = document.querySelector('.close-edit-banks');
            const closeEditDestinations = document.querySelector('.close-edit-destinations');
            
            // Botones de cancelar edición
            const cancelEditBank = document.getElementById('cancel-edit-bank');
            const cancelEditDestination = document.getElementById('cancel-edit-destination');
            
            // Función para crear el overlay
            function createOverlay() {
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                document.body.appendChild(overlay);
                return overlay;
            }
            
            // Función para remover el overlay
            function removeOverlay() {
                const overlay = document.querySelector('.modal-overlay');
                if (overlay) {
                    overlay.remove();
                }
            }
            
            // Abrir modal de bancos
            btnManagerBanks.addEventListener('click', function() {
                createOverlay();
                modalBanks.style.display = 'block';
            });
            
            // Abrir modal de destinos
            btnManagerDestinations.addEventListener('click', function() {
                createOverlay();
                modalDestinations.style.display = 'block';
            });
            
            // Cerrar modal de bancos
            closeModalBanks.addEventListener('click', function() {
                modalBanks.style.display = 'none';
                removeOverlay();
            });
            
            // Cerrar modal de destinos
            closeModalDestinations.addEventListener('click', function() {
                modalDestinations.style.display = 'none';
                removeOverlay();
            });
            
            // Cerrar modal de editar banco
            closeEditBanks.addEventListener('click', function() {
                editBanks.style.display = 'none';
            });
            
            // Cerrar modal de editar destino
            closeEditDestinations.addEventListener('click', function() {
                editDestinations.style.display = 'none';
            });
            
            // Cancelar edición de banco
            cancelEditBank.addEventListener('click', function() {
                editBanks.style.display = 'none';
            });
            
            // Cancelar edición de destino
            cancelEditDestination.addEventListener('click', function() {
                editDestinations.style.display = 'none';
            });
            
            // Delegación de eventos para editar bancos
            document.getElementById('bank-table-body').addEventListener('click', function(e) {
                if (e.target.closest('.edit-bank')) {
                    const row = e.target.closest('tr');
                    const bankId = row.dataset.id;
                    const bankName = row.dataset.name;
                    
                    // Establecer datos en el formulario de edición
                    document.getElementById('edit-bank-id').value = bankId;
                    document.getElementById('edit-bank-name').value = bankName;
                    
                    // Mostrar modal de edición
                    editBanks.style.display = 'block';
                }
            });
            
            // Delegación de eventos para editar destinos
            document.getElementById('destination-table-body').addEventListener('click', function(e) {
                if (e.target.closest('.edit-destination')) {
                    const row = e.target.closest('tr');
                    const destId = row.dataset.id;
                    const destName = row.dataset.name;
                    
                    // Establecer datos en el formulario de edición
                    document.getElementById('edit-destination-id').value = destId;
                    document.getElementById('edit-destination-name').value = destName;
                    
                    // Mostrar modal de edición
                    editDestinations.style.display = 'block';
                }
            });
            
            // Cerrar modales cuando se hace clic en el overlay
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-overlay')) {
                    modalBanks.style.display = 'none';
                    modalDestinations.style.display = 'none';
                    editBanks.style.display = 'none';
                    editDestinations.style.display = 'none';
                    removeOverlay();
                }
            });
        });
    </script>

    <!-- Script para agregar bancos y destinos -->
    <script>
        function addBank() {
            const bankNameInput = document.getElementById('bank-name');
            
            const datos = {
                nameBank: bankNameInput.value.trim()
            };
            
            // Validación básica
            if (!datos.nameBank) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo vacío',
                    text: 'Por favor ingrese un nombre para el banco',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            fetch("../../controllers/admin/admin-new-bank.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
            })
            .then(text => {
                try {
                    console.log("Respuesta del servidor:", text); // Para depuración
                    let data = JSON.parse(text);
                    
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.message || 'Banco agregado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Agregar la nueva fila a la tabla
                        const newRow = `<tr data-id="${data.data.id}" data-name="${datos.nameBank}">
                            <td>${datos.nameBank}</td>
                            <td>
                                <button class="delete-bank" onclick="deleteBank(${data.data.id})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <button class="edit-bank">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>`;
                        
                        document.getElementById('bank-table-body').insertAdjacentHTML('beforeend', newRow);

                        // Limpiar el campo de entrada
                        bankNameInput.value = '';

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Ocurrió un error al agregar el banco.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al agregar el banco:", data.error);
                    }
                } catch (error) {
                    console.error("Error al procesar respuesta JSON:", error);
                    console.error("Texto recibido:", text);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error("Error de red o servidor:", error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
            });
        }

        function addDestination() {
            const destinationNameInput = document.getElementById('destination-name');
            
            const datos = {
                nameDestination: destinationNameInput.value.trim()
            };
            
            // Validación básica
            if (!datos.nameDestination) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo vacío',
                    text: 'Por favor ingrese un nombre para el destino',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            fetch("../../controllers/admin/admin-new-destination.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
            })
            .then(text => {
                try {
                    console.log("Respuesta del servidor:", text); // Para depuración
                    let data = JSON.parse(text);
                    
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.message || 'Destino agregado correctamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });

                        // Recargar la tabla de destinos
                        const newRow = `<tr data-id="${data.data.id}" data-name="${datos.nameDestination}">
                                            <td>${datos.nameDestination}</td>
                                            <td>
                                                <button class="delete-destination" onclick="deleteDestination(${data.data.id})">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                <button class="edit-destination">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                            </td>
                                        </tr>`;
                        
                        document.getElementById('destination-table-body').insertAdjacentHTML('beforeend', newRow);

                        // Limpiar el campo de entrada
                        destinationNameInput.value = '';

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Ocurrió un error al agregar el destino.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.log("Error al agregar el destino:", data.error);
                    }
                } catch (error) {
                    console.error("Error al procesar respuesta JSON:", error);
                    console.error("Texto recibido:", text);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error("Error de red o servidor:", error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor. Por favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    </script>
    
</body>
</html>