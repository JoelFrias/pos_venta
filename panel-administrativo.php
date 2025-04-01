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

    require_once 'php/conexion.php';

    /* Fin de verificacion de sesion */

    // Verificar si el usuario tiene permisos de administrador
    if ($_SESSION['idPuesto'] > 2) {
        echo "<script>
                Swal.fire({
                        icon: 'error',
                        title: 'Acceso Prohibido',
                        text: 'Usted no cuenta con permisos de administrador para entrar a esta pagina.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = './';
                    });
            </script>";
        exit();
    }
                            
    /**
     * 
     *  IMPORTANT:
     *  AQUI DEBE DE IR LA VALIDACION DE PERMISOS DE USUARIO
     *  PARA ACCEDER A LA PAGINA, DE LO CONTRARIO REDIRIGIR A LA PAGINA DE LOGIN
     * 
     */

    // Tabla Bancos
    $stmtb = $conn->prepare("SELECT id AS idBank, nombreBanco AS namebanks FROM bancos WHERE id <> 1");
    $stmtb->execute();
    $resultsb = $stmtb->get_result();

    // Tabla Destinos
    $stmtd = $conn->prepare("SELECT id AS idDestination, descripcion AS namedestinations FROM destinocuentas WHERE id <> 1");
    $stmtd->execute();
    $resultsd = $stmtd->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <title>Panel Administrativo</title>
    <link rel="stylesheet" href="css/prueba-css.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
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
            max-width: 600px;
            z-index: 1000;
            max-height: 90vh;
            overflow-y: auto;
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

        /* Estilos para formularios */
        h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }

        h4 {
            margin: 20px 0 15px;
            color: #2c3e50;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #cancel-edit-bank:hover, #cancel-edit-destination:hover {
            background-color: #c0392b;
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
    </style>
</head>
<body>

    <div class="navegator-nav">

        <?php include 'prueba-menu.php'; ?>

        <div class="page-content">

            <div id="buttons">
                <div id="div-banks">
                    <button id="manager-banks">Administrar Bancos</button>
                </div>
                <div id="div-destinations">
                    <button id="manager-destinations">Administrar Destinos</button>
                </div>
                <div id="div-users">
                    <button id="manager-users">Administrar Usuarios</button>
                </div>
                <div id="div-employees">
                    <button id="manager-employees" onclick="redirectEmployee()">Administrar Empleados</button>
                </div>
                <div id="div-cashiers">
                    <button id="manager-cashiers">Cuadres de Caja</button>
                </div>
            </div>

            <!-- Modal para bancos -->
            <div id="modal-banks" style="display: none;">

                <span class="close-modal-banks">&times;</span>

                <h3>Agregar Banco</h3>
                <label for="bank-name">Nombre del Banco:</label>
                <input type="text" id="bank-name" name="bank-name">
                <button type="submit">Agregar</button>

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
                                                    <button class='delete-bank'><i class=\"fa-solid fa-trash\"></i></button>
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
                <input type="text" id="edit-bank-name" name="edit-bank-name">
                <button id="update-edit-bank">Actualizar</button>
                <button id="cancel-edit-bank">Cancelar</button>

            </div>

            <!-- Modal para destinos -->
            <div id="modal-destinations" style="display: none;">
                
                <span class="close-modal-destinations">&times;</span>

                <h3>Agregar Destino</h3>
                <label for="destination-name">Nombre del Destino:</label>
                <input type="text" id="destination-name" name="destination-name">
                <button type="submit">Agregar</button>

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
                                                    <button class='delete-destination'><i class=\"fa-solid fa-trash\"></i></button>
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
                <input type="text" id="edit-destination-name" name="edit-destination-name">
                <button id="update-edit-destination">Actualizar</button>
                <button id="cancel-edit-destination">Cancelar</button>

            </div>

        </div>
    </div>

    <!-- Overlay para móviles -->
    <div class="overlay" id="overlay"></div>

    <script>

        function redirectEmployee() {
            window.location.href = "empleados.php";
        }
        
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
                removeOverlay();
            });
            
            // Cerrar modal de editar destino
            closeEditDestinations.addEventListener('click', function() {
                editDestinations.style.display = 'none';
                removeOverlay();
            });
            
            // Cancelar edición de banco
            cancelEditBank.addEventListener('click', function() {
                editBanks.style.display = 'none';
                removeOverlay();
            });
            
            // Cancelar edición de destino
            cancelEditDestination.addEventListener('click', function() {
                editDestinations.style.display = 'none';
                removeOverlay();
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
                    createOverlay();
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
                    createOverlay();
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
    
</body>
</html>