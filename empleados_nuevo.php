<?php

session_start();

// Verificar si el usuario ya inicio sesion, redirigir a la página de inicio
if (!isset($_SESSION['username'])) {

    // Redirigir a la página de inicio de sesión con un mensaje de error
    header('Location: login.php?session_expired=session_expired');
    exit(); // Detener la ejecución del script
}

if ($_SESSION['idPuesto'] > 2) {
    
    ?>
    
    <script>
        alert('No tienes permisos para acceder a esta página.');
    </script>

    <?php
    header('Location: ./');
    exit();
}

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
        $queryEmpleado = "INSERT INTO empleados (nombre, apellido, tipo_identificacion, identificacion, telefono, idPuesto, fechaIngreso, activo) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW(), TRUE)";
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
        echo "Registro exitoso.";
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo "Error en el registro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Registro de Empleado</title>
</head>
<body>
    <h2>Registro de Empleado</h2>
    <form action="" method="post">
        <label>Nombre:</label>
        <input type="text" name="nombre" autocomplete="off" required><br>
        
        <label>Apellido:</label>
        <input type="text" name="apellido" autocomplete="off" required><br>
        
        <label>Tipo de Identificación:</label>
        <select name="tipo_identificacion" required>
            <option value="Cedula">Cédula</option>
            <option value="Pasaporte">Pasaporte</option>
        </select><br>
        
        <label>Identificación:</label>
        <input type="text" name="identificacion" autocomplete="off" required><br>
        
        <label>Teléfono:</label>
        <input type="text" name="telefono" autocomplete="off" required><br>
        
        <label>Puesto:</label>
        <select name="idPuesto" required>
            <?php
            // Obtener el id y la descripción de los tipos de producto
            $sql = "SELECT id, descripcion FROM empleados_puestos WHERE id <> 1 ORDER BY descripcion ASC";
            $resultado = $conn->query($sql);

            if ($resultado->num_rows > 0) {
                while ($fila = $resultado->fetch_assoc()) {
                    echo "<option value='" . $fila['id'] . "'>" . $fila['descripcion'] . "</option>";
                }
            } else {
                echo "<option value='' disabled>No hay opciones</option>";
            }
            ?>
        </select><br>
        
        <label>Usuario:</label>
        <input type="text" name="username" autocomplete="off" required><br>
        
        <label>Contraseña:</label>
        <input type="password" name="password" autocomplete="off" required><br>
        
        <input type="submit" value="Registrar">
    </form>
</body>
</html>