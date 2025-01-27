<?php
session_start();
require 'php/conexion.php';

// Obtener el ID del cliente desde la URL
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener los datos del cliente desde la base de datos
$query = "SELECT c.id, c.nombre, c.apellido, c.empresa, c.tipo_identificacion, c.identificacion, c.telefono, c.notas, cc.limite_credito, cd.no, cd.calle, cd.sector, cd.ciudad, cd.referencia, c.activo 
          FROM clientes c
          JOIN clientes_cuenta cc ON c.id = cc.id
          JOIN clientes_direcciones cd ON c.id = cd.id
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
    try {
        $conn->begin_transaction();

        $stmt_cliente = $conn->prepare("UPDATE clientes SET nombre = ?, apellido = ?, empresa = ?, tipo_identificacion = ?, identificacion = ?, telefono = ?, notas = ?, activo = ? WHERE id = ?");
        $stmt_cliente->bind_param("ssssssssi", $nombre, $apellido, $empresa, $tipo_identificacion, $identificacion, $telefono, $notas, $inactividad, $cliente_id);
        $stmt_cliente->execute();

        $stmt_cuenta = $conn->prepare("UPDATE clientes_cuenta SET limite_credito = ? WHERE id = ?");
        $stmt_cuenta->bind_param("di", $limite_credito, $cliente_id);
        $stmt_cuenta->execute();

        $stmt_direccion = $conn->prepare("UPDATE clientes_direcciones SET no = ?, calle = ?, sector = ?, ciudad = ?, referencia = ? WHERE id = ?");
        $stmt_direccion->bind_param("sssssi", $no, $calle, $sector, $ciudad, $referencia, $cliente_id);
        $stmt_direccion->execute();

        $conn->commit();

        $_SESSION['status'] = 'update_success';
        header("Location: clientes_actualizar.php?id=$cliente_id");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['errors'][] = "Error al actualizar cliente: " . $e->getMessage();
        header("Location: clientes_actualizar.php?id=$cliente_id");
        exit;
    } finally {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Cliente</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/mant_cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php 
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
        <!-- Mobile Menu Toggle - DEBE ESTAR FUERA DEL SIDEBAR boton unico para el dispositvo moviles-->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <!-------------------------->
        <!-- Requerimiento de Menu -->
        <?php require 'menu.html' ?>
<!--------------------------->
            <script>
                function navigateTo(page) {
                    window.location.href = page; // Cambia la URL en la misma pestaña
                }
            
                function toggleNav() {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.toggle('active'); // Añade o quita la clase active para mostrar/ocultar el menú
                }
            </script>
<!--------------------------->
        <!-- Overlay for mobile, no eliminar esto hace que aparezca las opciones sin recargar la pagina  -->
        <div class="overlay" id="overlay"></div>

        <div class="container">
        <h1>Actualizar Cliente</h1>
        <form method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo $cliente['nombre']; ?>" required>
            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" value="<?php echo $cliente['apellido']; ?>" required>
            <label for="empresa">Empresa:</label>
            <input type="text" id="empresa" name="empresa" value="<?php echo $cliente['empresa']; ?>">
            <label for="tipo_identificacion">Tipo Identificación:</label>
            <select id="tipo_identificacion" name="tipo_identificacion" required>
                <option value="cedula" <?php echo $cliente['tipo_identificacion'] === 'cedula' ? 'selected' : ''; ?>>Cédula</option>
                <option value="rnc" <?php echo $cliente['tipo_identificacion'] === 'rnc' ? 'selected' : ''; ?>>RNC</option>
                <option value="pasaporte" <?php echo $cliente['tipo_identificacion'] === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
            </select>
            <label for="identificacion">Identificación:</label>
            <input type="text" id="identificacion" name="identificacion" value="<?php echo $cliente['identificacion']; ?>" required>
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo $cliente['telefono']; ?>">
            <label for="notas">Notas:</label>
            <textarea id="notas" name="notas"><?php echo $cliente['notas']; ?></textarea>
            <label for="limite_credito">Límite Crédito:</label>
            <input type="number" id="limite_credito" name="limite_credito" value="<?php echo $cliente['limite_credito']; ?>" step="0.01">
            <label for="no">No:</label>
            <input type="text" id="no" name="no" value="<?php echo $cliente['no']; ?>">
            <label for="calle">Calle:</label>
            <input type="text" id="calle" name="calle" value="<?php echo $cliente['calle']; ?>">
            <label for="sector">Sector:</label>
            <input type="text" id="sector" name="sector" value="<?php echo $cliente['sector']; ?>">
            <label for="ciudad">Ciudad:</label>
            <input type="text" id="ciudad" name="ciudad" value="<?php echo $cliente['ciudad']; ?>">
            <label for="referencia">Referencia:</label>
            <input type="text" id="referencia" name="referencia" value="<?php echo $cliente['referencia']; ?>">
            
        <label for="inactividad">Estado:</label>
        <select id="inactividad" name="inactividad">
            <option value="TRUE" <?php echo $cliente['activo'] ? 'selected' : ''; ?>>Activo</option>
            <option value="FALSE" <?php echo !$cliente['activo'] ? 'selected' : ''; ?>>Inactivo</option>
        </select>

            <button type="submit">Actualizar</button>
        </form>
    </div>
    <script src="js/menu.js"></script>
</body>
</html>