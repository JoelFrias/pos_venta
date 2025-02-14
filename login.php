<?php

session_start();

// Verificar si el usuario ya inició sesión, redirigir a la página de inicio
if (isset($_SESSION['username'])) {
    // Redirigir a la página de inicio
    header('Location: ./');
    exit(); // Detener la ejecución del script
}

require 'php/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (empty($user) || empty($pass)) {
        $error = "Usuario y contraseña son requeridos.";
    } else {
        // Verificar que la conexión está establecida
        if (!isset($conn)) {
            die("Error: No se estableció la conexión a la base de datos. Por favor contacta al administrador.");
        }
        
        $query = "SELECT
                    u.id,
                    e.id AS idEmpleado,
                    u.username,
                    u.password,
                    CONCAT(e.nombre, ' ', e.apellido) AS nombre,
                    e.idPuesto
                FROM
                    usuarios AS u
                INNER JOIN empleados AS e
                ON
                    u.idEmpleado = e.id
                  WHERE u.username = ? AND e.activo = 1
                  LIMIT 1";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $user); // Solo se pasa el username
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Verificar la contraseña usando password_verify
                if (password_verify($pass, $row['password'])) {
                    // Guardar datos en la sesión
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['idEmpleado'] = $row['idEmpleado'];
                    $_SESSION['nombre'] = $row['nombre'];
                    $_SESSION['idPuesto'] = $row['idPuesto'];

                    // Redirigir a la página de inicio
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Credenciales incorrectas.";
                }
            } else {
                $error = "Credenciales incorrectas.";
            }

            $stmt->close();
        } else {
            $error = "Error en la consulta: " . $conn->error; // Mostrar el error de MySQLi
        }
    }
}

// Verificar si la sesión ha expirado
if (isset($_GET['session_expired']) && $_GET['session_expired'] === 'session_expired') {
    $error = "Tu sesión ha expirado. Por favor, inicia sesión nuevamente.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
    <h2>Iniciar Sesión</h2>
        <!-- Mensaje de error con botón de cierre -->
        <?php if(isset($error)): ?>
            <div class="error-message" id="error-message">
                <?php echo $error; ?>
                <button class="close-btn" onclick="closeErrorMessage()">×</button>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <input type="text" name="username" id="username" autocomplete="off" required>
                <label for="username">Username</label>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" required>
                <label for="password">Contraseña</label>
            </div>
            <input type="submit" value="Iniciar Sesión">
        </form>
    </div>
    <script>
        // Función para cerrar el mensaje de error
        function closeErrorMessage() {
            const errorMessage = document.getElementById('error-message');
            errorMessage.style.display = 'none'; // Oculta el mensaje
        }
    </script>
</body>
</html>