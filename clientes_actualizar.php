<?php
// Iniciar la sesión para manejar mensajes de estado y errores
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    // Redirigir a la página de inicio de sesión con un mensaje de error
    header('Location: login.php?session_expired=session_expired');
    exit(); // Detener la ejecución del script
}

// Incluir el archivo de conexión a la base de datos
require 'php/conexion.php';

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

        // Confirmar la transacción
        $conn->commit();

        // Establecer mensaje de éxito y redirigir
        $_SESSION['status'] = 'update_success';
        header("Location: clientes_actualizar.php?id=$cliente_id");
        exit;

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        // Almacenar el error en la sesión
        $_SESSION['errors'][] = "Error al actualizar cliente: " . $e->getMessage();
        header("Location: clientes_actualizar.php?id=$cliente_id");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Cliente</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/modo_oscuro.css">
    <link rel="stylesheet" href="css/actualizar_cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            }).then(function() {
                window.location.href = 'index.php'; 
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

<div class="container">
    <!-- Botón para mostrar/ocultar el menú en dispositivos móviles -->
    <button id="mobileToggle" class="toggle-btn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Incluir el menú -->
    <?php require 'menu.html' ?>

    <!-- Script para navegar entre páginas y mostrar/ocultar el menú -->
    <script>
        function navigateTo(page) {
            window.location.href = page; // Cambia la URL en la misma pestaña
        }

        function toggleNav() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active'); // Añade o quita la clase active para mostrar/ocultar el menú
        }
    </script>

    <!-- Overlay para dispositivos móviles -->
    <div class="overlay" id="overlay"></div>

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
                        <input type="text" id="nombre" name="nombre" value="<?php echo $cliente['nombre']; ?>" placeholder="Ingrese el nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" value="<?php echo $cliente['apellido']; ?>" placeholder="Ingrese el apellido" required>
                    </div>
                    <div class="form-group">
                        <label for="empresa">Empresa:</label>
                        <input type="text" id="empresa" name="empresa" value="<?php echo $cliente['empresa']; ?>" placeholder="Ingrese la empresa">
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
                        <input type="text" id="identificacion" name="identificacion" value="<?php echo $cliente['identificacion']; ?>" placeholder="Ingrese la identificación" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo $cliente['telefono']; ?>" placeholder="000-000-0000" required>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas:</label>
                        <textarea id="notas" name="notas" placeholder="Indique notas del cliente"><?php echo $cliente['notas']; ?></textarea>
                    </div>
                </div>
            </fieldset>

            <!-- Sección de Crédito -->
            <fieldset>
                <legend>Crédito</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="limite_credito">Límite Crédito:</label>
                        <input type="number" id="limite_credito" name="limite_credito" value="<?php echo $cliente['limite_credito']; ?>" step="0.01" placeholder="Ingrese el límite de crédito" required>
                    </div>
                </div>
            </fieldset>

            <!-- Sección de Dirección -->
            <fieldset>
                <legend>Dirección</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="no">No:</label>
                        <input type="text" id="no" name="no" value="<?php echo $cliente['no']; ?>" placeholder="Ingrese el número de casa" required>
                    </div>
                    <div class="form-group">
                        <label for="calle">Calle:</label>
                        <input type="text" id="calle" name="calle" value="<?php echo $cliente['calle']; ?>" placeholder="Ingrese la calle" required>
                    </div>
                    <div class="form-group">
                        <label for="sector">Sector:</label>
                        <input type="text" id="sector" name="sector" value="<?php echo $cliente['sector']; ?>" placeholder="Ingrese el sector" required>
                    </div>
                    <div class="form-group">
                        <label for="ciudad">Ciudad:</label>
                        <input type="text" id="ciudad" name="ciudad" value="<?php echo $cliente['ciudad']; ?>" placeholder="Ingrese la ciudad" required>
                    </div>
                    <div class="form-group">
                        <label for="referencia">Referencia:</label>
                        <textarea id="referencia" name="referencia" placeholder="Indique referencia de direccion (Ej: Al lado de una farmacia)" required><?php echo $cliente['referencia']; ?></textarea>
                    </div>
                </div>
            </fieldset>

            <!-- Sección de Estado -->
            <fieldset>
                <legend>Estado</legend>
                <div class="form-group">
                    <label for="inactividad">Estado:</label>
                    <select id="inactividad" name="inactividad">
                        <option value="TRUE" <?php echo $cliente['activo'] ? 'selected' : ''; ?>>Activo</option>
                        <option value="FALSE" <?php echo !$cliente['activo'] ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </fieldset>

            <!-- Botón para enviar el formulario -->
            <button class="btn-submit" type="submit">Actualizar</button>
        </form>
    </div>

    <!-- Scripts adicionales -->
    <script src="js/menu.js"></script>
    <script src="js/modo_oscuro.js"></script>
    <script src="js/oscuro_recargar.js"></script>
</body>
</html>