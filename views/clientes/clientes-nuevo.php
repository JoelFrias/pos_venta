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

// Incluir el archivo de conexión a la base de datos
require '../../models/conexion.php';

// Inicializar variables para almacenar los datos del formulario
$nombre = $apellido = $empresa = $tipo_identificacion = $identificacion = $telefono = $notas = "";
$limite_credito = $balance = $no = $calle = $sector = $ciudad = $referencia = "";

// Validar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $nombre = isset($_POST['nombre']) ? htmlspecialchars(trim($_POST['nombre'])) : "";
    $apellido = isset($_POST['apellido']) ? htmlspecialchars(trim($_POST['apellido'])) : "";
    $empresa = isset($_POST['empresa']) ? htmlspecialchars(trim($_POST['empresa'])) : "";
    $tipo_identificacion = isset($_POST['tipo_identificacion']) ? htmlspecialchars(trim($_POST['tipo_identificacion'])) : "";
    $identificacion = isset($_POST['identificacion']) ? htmlspecialchars(trim($_POST['identificacion'])) : "";
    $telefono = isset($_POST['telefono']) ? htmlspecialchars(trim($_POST['telefono'])) : "";
    $notas = isset($_POST['notas']) ? htmlspecialchars(trim($_POST['notas'])) : "";
    $limite_credito = isset($_POST['limite_credito']) ? floatval($_POST['limite_credito']) : 0.0;
    $no = isset($_POST['no']) ? htmlspecialchars(trim($_POST['no'])) : "";
    $calle = isset($_POST['calle']) ? htmlspecialchars(trim($_POST['calle'])) : "";
    $sector = isset($_POST['sector']) ? htmlspecialchars(trim($_POST['sector'])) : "";
    $ciudad = isset($_POST['ciudad']) ? htmlspecialchars(trim($_POST['ciudad'])) : "";
    $referencia = isset($_POST['referencia']) ? htmlspecialchars(trim($_POST['referencia'])) : "";

    // Validar campos obligatorios (se pueden agregar más validaciones si es necesario)
    if (empty($nombre) || empty($apellido) || empty($identificacion)) {
        echo "Los campos Nombre, Apellido e Identificación son obligatorios.";
        exit;
    }

    // Manejo de errores con consultas preparadas
    try {
        // Iniciar una transacción para asegurar la integridad de los datos
        $conn->begin_transaction();

        // Insertar en la tabla 'clientes'
        $stmt_cliente = $conn->prepare("INSERT INTO clientes (nombre, apellido, empresa, tipo_identificacion, identificacion, telefono, notas, fechaRegistro, activo) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), TRUE)");
        $stmt_cliente->bind_param("sssssss", $nombre, $apellido, $empresa, $tipo_identificacion, $identificacion, $telefono, $notas);
        $stmt_cliente->execute();

        // Obtener el ID del cliente recién insertado
        $cliente_id = $conn->insert_id;

        // Insertar en la tabla 'clientes_cuenta'
        $stmt_cuenta = $conn->prepare("INSERT INTO clientes_cuenta (idCliente, limite_credito, balance) VALUES (?, ?, ?)");
        $stmt_cuenta->bind_param("idd", $cliente_id, $limite_credito, $limite_credito);
        $stmt_cuenta->execute();

        // Insertar en la tabla 'clientes_direcciones'
        $stmt_direccion = $conn->prepare("INSERT INTO clientes_direcciones (idCliente, no, calle, sector, ciudad, referencia) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_direccion->bind_param("isssss", $cliente_id, $no, $calle, $sector, $ciudad, $referencia);
        $stmt_direccion->execute();

        /**
         *  2. Auditoria de acciones de usuario
         */

        require_once '../../models/auditorias.php';
        $usuario_id = $_SESSION['idEmpleado'];
        $accion = 'Nuevo cliente';
        $detalle = 'ID del cliente: ' . $cliente_id;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
        registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);

        // Confirmar la transacción
        $conn->commit();

        // Almacenar mensaje de éxito en sesión y redirigir
        $_SESSION['status'] = 'success';
        header("Location: ../../views/clientes/clientes-nuevo.php");
        exit;

    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        $conn->rollback();
        $_SESSION['errors'][] = "Error al registrar cliente: " . $e->getMessage();
        header("Location: ../../views/clientes/clientes-nuevo.php");
        exit;
    } finally {
        // Cerrar las declaraciones preparadas
        if (isset($stmt_cliente)) $stmt_cliente->close();
        if (isset($stmt_cuenta)) $stmt_cuenta->close();
        if (isset($stmt_direccion)) $stmt_direccion->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Nuevo Cliente</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-blanco.png">
    <link rel="stylesheet" href="../../assets/css/actualizar_cliente.css">
    <link rel="stylesheet" href="../../assets/css/menu.css"> <!-- CSS menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Importación de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->
    <style>
        .btn-volver {
        background-color: #f5f5f5;
        border: 1px solid #ccc;
        color: #333;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s, box-shadow 0.2s;
        }

        .btn-volver:hover {
        background-color: #e0e0e0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-volver:active {
        background-color: #d5d5d5;
        }
    </style>
</head>
<body>

    <div class="navegator-nav">

        <!-- Menu-->
        <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->
        
            <!-- Contenedor del formulario -->
            <div class="form-container">
                <h1 class="form-title">Registro de Cliente</h1>
                <form class="registration-form" action="" method="POST">
                    <!-- Sección de Datos del Cliente -->
                    <fieldset>
                        <legend>Datos del Cliente</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre:</label>
                                <input type="text" id="nombre" name="nombre" autocomplete="off" placeholder="Ingrese el nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellido:</label>
                                <input type="text" id="apellido" name="apellido" autocomplete="off" placeholder="Ingrese el apellido" required>
                            </div>
                            <div class="form-group">
                                <label for="empresa">Empresa:</label>
                                <input type="text" id="empresa" name="empresa" autocomplete="off" placeholder="Ingrese la empresa" required>
                            </div>
                            <div class="form-group">
                                <label for="tipo_identificacion">Tipo de Identificación:</label>
                                <select id="tipo_identificacion" name="tipo_identificacion" required>
                                    <option value="" disabled selected>Seleccionar</option>
                                    <option value="cedula">Cédula</option>
                                    <option value="rnc">RNC</option>
                                    <option value="pasaporte">Pasaporte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="identificacion">Número de Identificación:</label>
                                <input type="text" id="identificacion" name="identificacion" autocomplete="off" placeholder="Ingrese la identificación" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono:</label>
                                <input type="text" id="telefono" name="telefono" autocomplete="off" placeholder="000-000-0000" maxlength="12" minlength="12" required>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="notas">Notas:</label>
                            <textarea id="notas" name="notas" placeholder="Notas del cliente" required></textarea>
                        </div>
                    </fieldset>

                    <!-- Sección de Datos de la Cuenta -->
                    <fieldset>
                        <legend>Datos de la Cuenta</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="limite_credito">Límite de Crédito:</label>
                                <input type="number" id="limite_credito" name="limite_credito" min="0" step="0.01" autocomplete="off" placeholder="Ingrese un límite de crédito" required>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección de Dirección -->
                    <fieldset>
                        <legend>Dirección</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="no">Número:</label>
                                <input type="text" id="no" name="no" autocomplete="off" placeholder="Ingrese el número de local" required>
                            </div>
                            <div class="form-group">
                                <label for="calle">Calle:</label>
                                <input type="text" id="calle" name="calle" autocomplete="off" placeholder="Ingrese la calle" required>
                            </div>
                            <div class="form-group">
                                <label for="sector">Sector:</label>
                                <input type="text" id="sector" name="sector" autocomplete="off" placeholder="Ingrese el sector" required>
                            </div>
                            <div class="form-group">
                                <label for="ciudad">Ciudad:</label>
                                <input type="text" id="ciudad" name="ciudad" autocomplete="off" placeholder="Ingrese la ciudad" required>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="referencia">Referencia:</label>
                            <textarea id="referencia" name="referencia" placeholder="Indique referencias del local (Ej: Al lado de la farmacia)" required></textarea>
                        </div>
                    </fieldset>

                    <!-- Botón para enviar el formulario -->
                    <button class="btn-volver" onclick="history.back()">← Volver atrás</button>
                    <button type="submit" class="btn-submit">Registrar Cliente</button>
                </form>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR POR ENCIMA DE ESTA LINEA -->
        </div>
    </div>

    <!-- Mostrar mensajes de éxito o error -->
    <?php
        if (isset($_SESSION['status']) && $_SESSION['status'] === 'success') {
            echo "
                <script>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'El cliente ha sido registrado exitosamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(function() {
                        window.location.href = '../../views/clientes/clientes-nuevo.php'; 
                    });
                </script>
            ";
            unset($_SESSION['status']); // Limpiar el estado después de mostrar el mensaje
        }
        if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo "
                    <script>
                        Swal.fire({
                            title: '¡Error!',
                            text: '$error',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    </script>
                ";
            }
            unset($_SESSION['errors']); // Limpiar los errores después de mostrarlos
        }
    ?>

    <!-- Script para formatear el número de teléfono -->
    <script>
        const telefonoInput = document.getElementById('telefono');
        telefonoInput.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9]/g, '');  // Eliminar cualquier carácter que no sea número

            // Agregar el primer guion después de los tres primeros números
            if (value.length > 3 && value.charAt(3) !== '-') {
                value = value.slice(0, 3) + '-' + value.slice(3);
            }

            // Agregar el segundo guion después de los seis primeros números (3+3)
            if (value.length > 6 && value.charAt(6) !== '-') {
                value = value.slice(0, 7) + '-' + value.slice(7);
            }

            // Asignar el valor al campo de entrada
            this.value = value;
        });
    </script>

</body>
</html>