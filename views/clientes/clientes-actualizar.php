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
    header('Location: ../../views/auth/login.php.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: ../../views/auth/login.php.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

// Incluir el archivo de conexión a la base de datos
require '../../models/conexion.php';

// Obtener el ID del cliente desde la URL y validarlo
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta SQL para obtener los datos del cliente, su cuenta y dirección
$query = "SELECT c.id, c.nombre, c.apellido, c.empresa, c.tipo_identificacion, c.identificacion, c.telefono, c.notas, cc.limite_credito, cd.no, cd.calle, cd.sector, cd.ciudad, cd.referencia, c.activo 
          FROM clientes c
          JOIN clientes_cuenta cc ON c.id = cc.idCliente
          JOIN clientes_direcciones cd ON c.id = cd.idCliente
          WHERE c.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

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

    // Obtener el estado de inactividad
    $inactividad = isset($_POST['inactividad']) ? $_POST['inactividad'] === 'TRUE' : TRUE;

    // Actualizar los datos del cliente en la base de datos
    // Actualizar los datos del cliente en la base de datos
    try {
        // Iniciar una transacción para asegurar la integridad de los datos
        $conn->begin_transaction();

        // Actualizar la tabla 'clientes'
        $stmt_cliente = $conn->prepare("UPDATE clientes SET nombre = ?, apellido = ?, empresa = ?, tipo_identificacion = ?, identificacion = ?, telefono = ?, notas = ?, activo = ? WHERE id = ?");
        $stmt_cliente->bind_param("ssssssssi", $nombre, $apellido, $empresa, $tipo_identificacion, $identificacion, $telefono, $notas, $inactividad, $cliente_id);
        $stmt_cliente->execute();

        /* FORMULA PARA CALCULAR EL NUEVO BALANCE */

        // Obtener el balance total de las facturas del cliente
        $query_balance_facturas = "SELECT SUM(f.balance) AS total_balance FROM facturas AS f WHERE idCliente = ?";
        $stmt_balance = $conn->prepare($query_balance_facturas);
        $stmt_balance->bind_param("i", $cliente_id);
        $stmt_balance->execute();
        $result_balance = $stmt_balance->get_result();
        $row_balance = $result_balance->fetch_assoc();

        // Calcular el nuevo balance
        $total_balance_facturas = $row_balance['total_balance'] ?? 0; // Si no hay facturas, el balance es 0
        $nuevo_balance = $limite_credito - $total_balance_facturas;

        /* FIN DE LA FORMULA */

        // Actualizar la tabla 'clientes_cuenta' con el nuevo balance
        $stmt_cuenta = $conn->prepare("UPDATE clientes_cuenta SET limite_credito = ?, balance = ? WHERE idCliente = ?");
        $stmt_cuenta->bind_param("ddi", $limite_credito, $nuevo_balance, $cliente_id);
        $stmt_cuenta->execute();

        // Actualizar la tabla 'clientes_direcciones'
        $stmt_direccion = $conn->prepare("UPDATE clientes_direcciones SET no = ?, calle = ?, sector = ?, ciudad = ?, referencia = ? WHERE idCliente = ?");
        $stmt_direccion->bind_param("sssssi", $no, $calle, $sector, $ciudad, $referencia, $cliente_id);
        $stmt_direccion->execute();

        /**
         *  2. Auditoria de acciones de usuario
         */

        require_once '../../models/auditorias.php';
        $usuario_id = $_SESSION['idEmpleado'];
        $accion = 'Actualizar cliente';
        $detalle = 'Cliente actualizado: 
            Nombre: ' . $nombre . ' - 
            Apellido: ' . $apellido . ' - 
            ID: ' . $cliente_id . ' - 
            Empresa: ' . $empresa . ' - 
            Tipo Identificacion: ' . $tipo_identificacion . ' - 
            Identificacion: ' . $identificacion . ' - 
            Telefono: ' . $telefono . ' - 
            Notas: ' . $notas . ' - 
            Limite Credito: ' . $limite_credito . ' - 
            DIRECCION: ' . $no . ' - ' . $calle . ' - ' . $sector . ' - ' . $ciudad . ' - ' . $referencia . ' - 
            Inactividad: ' . ($inactividad ? 'Activo' : 'Inactivo');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
        registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);

        // Confirmar la transacción
        $conn->commit();

        // Establecer mensaje de éxito y redirigir
        $_SESSION['status'] = 'update_success';
        header("Location: ../../views/clientes/clientes-actualizar.php?id=$cliente_id");
        exit;

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        // Almacenar el error en la sesión
        $_SESSION['errors'][] = "Error al actualizar cliente: " . $e->getMessage();
        header("Location: ../../views/clientes/clientes-actualizar.php?id=$cliente_id");
        exit;
    } finally {
        // Cerrar las declaraciones preparadas
        if (isset($stmt_cliente)) $stmt_cliente->close();
        if (isset($stmt_balance)) $stmt_balance->close();
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
    <title>Actualizar Cliente</title>
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
<?php 
// Mostrar mensaje de éxito si existe
if (isset($_SESSION['status']) && $_SESSION['status'] === 'update_success') {
    echo "
        <script>
            Swal.fire({
                title: '¡Éxito!',
                text: 'El cliente ha sido actualizado exitosamente.',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        </script>
    ";
    unset($_SESSION['status']); // Limpiar el estado después de mostrar el mensaje
}

// Mostrar errores si existen
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

    <div class="navegator-nav">

        <!-- Menu-->
        <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <!-- Contenedor del formulario -->
            <div class="form-container">
                <h1 class="form-title">Actualizar Datos</h1>
                <form class="registration-form" action="" method="POST">
                    <!-- Sección de Datos Personales -->
                    <fieldset>
                        <legend>Datos Personales</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre:</label>
                                <input type="text" id="nombre" name="nombre" minlength="1" value="<?php echo $cliente['nombre']; ?>" placeholder="Ingrese el nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellido:</label>
                                <input type="text" id="apellido" name="apellido" minlength="1" value="<?php echo $cliente['apellido']; ?>" placeholder="Ingrese el apellido" required>
                            </div>
                            <div class="form-group">
                                <label for="empresa">Empresa:</label>
                                <input type="text" id="empresa" name="empresa" minlength="1" value="<?php echo $cliente['empresa']; ?>" placeholder="Ingrese la empresa" required>
                            </div>
                            <div class="form-group">
                                <label for="tipo_identificacion">Tipo Identificación:</label>
                                <select id="tipo_identificacion" name="tipo_identificacion" required>
                                    <option value="cedula" <?php echo $cliente['tipo_identificacion'] === 'cedula' ? 'selected' : ''; ?>>Cédula</option>
                                    <option value="rnc" <?php echo $cliente['tipo_identificacion'] === 'rnc' ? 'selected' : ''; ?>>RNC</option>
                                    <option value="pasaporte" <?php echo $cliente['tipo_identificacion'] === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="identificacion">Identificación:</label>
                                <input type="text" id="identificacion" name="identificacion" min="0" value="<?php echo $cliente['identificacion']; ?>" placeholder="Ingrese la identificación" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono:</label>
                                <input type="text" id="telefono" name="telefono" minlength="12" maxlength="12" value="<?php echo $cliente['telefono']; ?>" placeholder="000-000-0000" required>
                            </div>
                            <div class="form-group">
                                <label for="notas">Notas:</label>
                                <textarea id="notas" name="notas" minlength="1" placeholder="Indique notas del cliente"><?php echo $cliente['notas']; ?></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección de Crédito -->
                    <fieldset>
                        <legend>Crédito</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="limite_credito">Límite Crédito:</label>
                                <input type="number" id="limite_credito" name="limite_credito" min="0" value="<?php echo $cliente['limite_credito']; ?>" step="0.01" placeholder="Ingrese el límite de crédito" required>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección de Dirección -->
                    <fieldset>
                        <legend>Dirección</legend>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="no">No:</label>
                                <input type="text" id="no" name="no" minlength="1" value="<?php echo $cliente['no']; ?>" placeholder="Ingrese el número de casa" required>
                            </div>
                            <div class="form-group">
                                <label for="calle">Calle:</label>
                                <input type="text" id="calle" name="calle" minlength="1" value="<?php echo $cliente['calle']; ?>" placeholder="Ingrese la calle" required>
                            </div>
                            <div class="form-group">
                                <label for="sector">Sector:</label>
                                <input type="text" id="sector" name="sector" minlength="1" value="<?php echo $cliente['sector']; ?>" placeholder="Ingrese el sector" required>
                            </div>
                            <div class="form-group">
                                <label for="ciudad">Ciudad:</label>
                                <input type="text" id="ciudad" name="ciudad" minlength="1" value="<?php echo $cliente['ciudad']; ?>" placeholder="Ingrese la ciudad" required>
                            </div>
                            <div class="form-group">
                                <label for="referencia">Referencia:</label>
                                <textarea id="referencia" name="referencia" minlength="1" placeholder="Indique referencia de direccion (Ej: Al lado de una farmacia)" required><?php echo $cliente['referencia']; ?></textarea>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sección de Estado -->
                    <fieldset>
                        <legend>Estado</legend>
                        <div class="form-group">
                            <label for="inactividad">Estado:</label>
                            <select id="inactividad" name="inactividad" required>
                                <option value="TRUE" <?php echo $cliente['activo'] ? 'selected' : ''; ?>>Activo</option>
                                <option value="FALSE" <?php echo !$cliente['activo'] ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </fieldset>

                    <!-- Botón para enviar el formulario -->
                    <button class="btn-volver" onclick="history.back()">← Volver atrás</button>
                    <button class="btn-submit" type="submit">Actualizar</button>
                </form>
            </div>

        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR POR ENCIMA DE ESTA LINEA -->
        </div>
    </div>

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