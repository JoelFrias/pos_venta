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

require 'php/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $tipo_identificacion = trim($_POST['tipo_identificacion']);
    $identificacion = trim($_POST['identificacion']);
    $telefono = trim($_POST['telefono']);
    $idPuesto = intval($_POST['idPuesto']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Encriptar la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Iniciar transacción
    $conn->begin_transaction();
    try {
        // Insertar empleado
        $queryEmpleado = "INSERT INTO empleados (nombre, apellido, tipo_identificacion, identificacion, telefono, idPuesto, fechaIngreso, activo) VALUES (?, ?, ?, ?, ?, ?, NOW(), TRUE)";
        $stmt = $conn->prepare($queryEmpleado);
        $stmt->bind_param("sssssi", $nombre, $apellido, $tipo_identificacion, $identificacion, $telefono, $idPuesto);
        $stmt->execute();
        $idEmpleado = $stmt->insert_id;
        $stmt->close();

        // Insertar usuario
        $queryUsuario = "INSERT INTO usuarios (username, password, idEmpleado) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($queryUsuario);
        $stmt->bind_param("ssi", $username, $hashed_password, $idEmpleado);
        $stmt->execute();
        $stmt->close();

        // Confirmar transacción
        $conn->commit();
        $_SESSION['success_message'] = 'Registro exitoso.'; // Almacenar mensaje de éxito
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        $_SESSION['error_message'] = 'Error en el registro: ' . $e->getMessage(); // Almacenar mensaje de error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Nuevo Empleado</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/registro_empleados.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

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
                    window.location.href = './';
                });
          </script>";
    exit();
}

?>
<!----------------------------------------->
  <!-- Contenedor principal -->
  <div class="container">
        <!-- Botón para mostrar/ocultar el menú en dispositivos móviles -->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Incluir el menú -->
        <?php require 'menu.php' ?>
        <!-- Script para navegación interna -->
        <script>
            /**
             * Redirige a la página especificada dentro de la misma pestaña.
             * @param {string} page - URL de la página a la que se desea navegar.
             */
            function navigateTo(page) {
                window.location.href = page;
            }
            
            /**
             * Alterna la visibilidad del menú lateral.
             */
            function toggleNav() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('active');
            }
        </script>
        
        <!-- Overlay para móviles (evita recarga innecesaria de la página) -->
        <div class="overlay" id="overlay"></div>
<!------------------------------------------------------------>
    <div class="form-container">
        <h2 class="form-title">Registro de Empleado</h2>
        <form class="registration-form" action="" method="post">
        <legend>Datos del Empleado</legend>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" autocomplete="off" placeholder="Nombre" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" autocomplete="off" placeholder="Apellido" required>
                </div>
                <div class="form-group">
                    <label for="tipo_identificacion">Tipo de Identificación:</label>
                    <select id="tipo_identificacion" name="tipo_identificacion" required>
                        <option value="Cedula">Cédula</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="identificacion">Identificación:</label>
                    <input type="text" id="identificacion" name="identificacion" autocomplete="off" placeholder="Identificacion" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" autocomplete="off" placeholder="000-000-0000" minlength="12" maxlength="12" required>
                </div>
                <div class="form-group">
                    <label for="idPuesto">Puesto:</label>
                    <select id="idPuesto" name="idPuesto" required>
                        <?php
                        // Obtener el id y la descripción de los tipos de producto
                        $sql = "SELECT id, descripcion FROM empleados_puestos ORDER BY descripcion ASC";
                        $resultado = $conn->query($sql);

                        if ($resultado->num_rows > 0) {
                            while ($fila = $resultado->fetch_assoc()) {
                                echo "<option value='" . $fila['id'] . "'>" . $fila['descripcion'] . "</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No hay opciones</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <legend>Datos de Usuario</legend>
                    <div class="form-grid">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" placeholder="Usuario" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" autocomplete="off" minlength="4" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Guardar Cambios</button>
        </form>
    </div>
    <!--script de manejo de mensajes-->
    <script>
        // Verificar si hay un mensaje de éxito y mostrarlo con SweetAlert2
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '<?php echo $_SESSION['success_message']; ?>',
            });
            <?php unset($_SESSION['success_message']); ?> // Limpiar el mensaje de la sesión
        <?php endif; ?>

        // Verificar si hay un mensaje de error y mostrarlo con SweetAlert2
        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $_SESSION['error_message']; ?>',
            });
            <?php unset($_SESSION['error_message']); ?> // Limpiar el mensaje de la sesión
        <?php endif; ?>
    </script>
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
    <!-- Script para manejar el menú móvil -->
    <script src="js/menu.js"></script>
</body>
</html>