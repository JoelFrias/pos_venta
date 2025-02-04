<?php
require 'php/conexion.php'; // Asegúrate de que $conn esté definido en este archivo

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if (empty($user) || empty($pass)) {
        $error = "Usuario y contraseña son requeridos.";
    } else {
        // Verificar que la conexión está establecida
        if (!isset($conn)) {
            die("Error: No se estableció la conexión a la base de datos.");
        }

        // Consulta segura con MySQLi
        $query = "SELECT
                    u.id,
                    u.username,
                    u.password,
                    e.nombre,
                    e.apellido,
                    e.idPuesto
                FROM
                    usuarios AS u
                INNER JOIN empleados AS e
                ON
                    u.idEmpleado = e.id
                  WHERE u.username = ? AND u.password = ? AND e.activo = 1
                  LIMIT 1";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ss", $user, $pass);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Guardar datos en la sesión
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['apellido'] = $row['apellido'];
                $_SESSION['idPuesto'] = $row['idPuesto'];

                // Redirigir a la página de inicio
                header("Location: index.php");
                exit();
            } else {
                $error = "Credenciales incorrectas.";
            }

            $stmt->close();
        } else {
            $error = "Error en la consulta: " . $conn->error; // Mostrar el error de MySQLi
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Iniciar Sesión</title>
</head>
<body>
    <h2>Inciar Sesión</h2>
    <div><?php echo isset($error) ? $error : ''; ?></div>

    <?php
    if (isset($_GET['session_expired']) && $_GET['session_expired'] === 'session_expired') {
        echo "<p>Tu sesión ha expirado. Por favor, inicia sesión nuevamente.</p>";
    }
    ?>

    <form action="" method="post">
        <label>Username:</label>
        <input type="text" name="username" id="username" required><br>
        <label>Contraseña:</label>
        <input type="password" name="password" id="password" required><br>
        <input type="submit" value="Iniciar Sesión">
    </form>
</body>
</html>