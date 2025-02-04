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
    <title>Iniciar Sesión</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <div><?php echo isset($error) ? $error : ''; ?></div>

    <form action="" method="post">
        <label>Username:</label>
        <input type="text" name="username" id="username" autocomplete="off" required><br>
        <label>Contraseña:</label>
        <input type="password" name="password" id="password" required><br>
        <input type="submit" value="Iniciar Sesión">
    </form>
</body>
</html>