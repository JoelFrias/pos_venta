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
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($pass, $row['password'])) {
                    // Guardar datos en la sesión
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['idEmpleado'] = $row['idEmpleado'];
                    $_SESSION['nombre'] = $row['nombre'];
                    $_SESSION['idPuesto'] = $row['idPuesto'];

                    // Verificar si el empleado tiene una caja abierta
                    caja($conn);

                    /**
                     *  2. Auditoria de acciones de usuario
                     */

                    require_once 'php/auditorias.php';
                    $usuario_id = $_SESSION['idEmpleado'];
                    $accion = 'Nueva sesión iniciada';
                    $detalle = 'El usuario ' . $_SESSION['username'] . ' ha iniciado sesión.';
                    $ip = $_SERVER['REMOTE_ADDR']; // Obtener la dirección IP del cliente
                    registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);

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
            $error = "Se ha producido un error interno en el servidor.";
            ?>

            <script>
                console.log("Error: <?php echo $conn->error; ?>");
            </script>

            <?php
        }
    }
}

// Verificar si la sesión ha expirado
if (isset($_GET['session_expired']) && $_GET['session_expired'] === 'session_expired') {
    $error = "Tu sesión ha expirado. Por favor, inicia sesión nuevamente.";
}

// Verificar si el empleado tiene una caja abierta
function caja($conn){
    
    $sql_verificar = "SELECT
                        numCaja,
                        idEmpleado,
                        DATE_FORMAT(fechaApertura, '%d/%m/%Y %l:%i %p') AS fechaApertura,
                        saldoApertura,
                        registro
                    FROM
                        cajasabiertas
                    WHERE
                        idEmpleado = " . $_SESSION['idEmpleado'];

    $resultado = $conn->query($sql_verificar);
    $datos_caja = null;

    if ($resultado->num_rows > 0) {
        $datos_caja = $resultado->fetch_assoc();

        // Almacenar datos de la caja abierta
        $_SESSION['numCaja'] = strval($datos_caja['numCaja']);
        $_SESSION['fechaApertura'] = $datos_caja['fechaApertura'];
        $_SESSION['saldoApertura'] = $datos_caja['saldoApertura'];
        $_SESSION['registro'] = $datos_caja['registro'];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <style>
        :root {
            --primary-color: #4a6bff;
            --primary-hover: #3a56e0;
            --error-color: #ff4b4b;
            --text-color: #333;
            --light-bg: #f7f9ff;
            --border-color: #e0e4f6;
            --shadow-color: rgba(74, 107, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            background: linear-gradient(135deg, #f5f7ff 0%, #e9f0ff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100%;
            padding: 10px;
        }

        .login-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: 30px 25px;
            width: 100%;
            max-width: 400px;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: var(--text-color);
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 24px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 60px;
            height: auto;
        }

        .error-message {
            background-color: #ffebee;
            color: var(--error-color);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: fadeIn 0.3s ease;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--error-color);
            font-size: 18px;
            cursor: pointer;
            padding: 0 5px;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background-color: var(--light-bg);
            color: var(--text-color);
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.15);
        }

        .form-group label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
            pointer-events: none;
            transition: all 0.3s ease;
            background-color: transparent;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 0;
            left: 10px;
            font-size: 12px;
            padding: 0 5px;
            background-color: white;
            color: var(--primary-color);
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: var(--primary-hover);
        }

        /* Para que los inputs con placeholder vacío funcionen con las etiquetas flotantes */
        .form-group input::placeholder {
            color: transparent;
        }

        /* Media queries para responsividad */
        @media screen and (max-width: 480px) {
            .login-container {
                padding: 25px 15px;
                margin: 0 10px;
            }
            
            h2 {
                font-size: 22px;
                margin-bottom: 15px;
            }
            
            .logo {
                max-width: 50px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .form-group label {
                font-size: 14px;
            }
            
            input[type="submit"] {
                padding: 10px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img class="logo" src="img/logo-negro.png" alt="Logo de la empresa">
        </div>
        
        <h2>Iniciar Sesión</h2>
        
        <!-- Mensaje de error con botón de cierre -->
        <?php if(isset($error)): ?>
            <div class="error-message" id="error-message">
                <?php echo $error; ?>
                <button class="close-btn" onclick="closeErrorMessage()">×</button>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <input type="text" name="username" id="username" autocomplete="off" placeholder=" " value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                <label for="username">Usuario</label>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder=" " required>
                <label for="password">Contraseña</label>
            </div>
            <input type="submit" value="Iniciar Sesión">
        </form>
    </div>
    
    <script>
        // Función para cerrar el mensaje de error
        function closeErrorMessage() {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.display = 'none'; // Oculta el mensaje
            }
        }
        
        // Ajustar las etiquetas si los campos tienen valor
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-group input');
            
            inputs.forEach(input => {
                // Verificar si el input tiene valor al cargar la página
                if (input.value !== '') {
                    input.classList.add('has-value');
                    // Asegurarnos que la etiqueta se mueva arriba
                    const label = input.nextElementSibling;
                    if (label && label.tagName === 'LABEL') {
                        label.style.top = '0';
                        label.style.left = '10px';
                        label.style.fontSize = '12px';
                        label.style.padding = '0 5px';
                        label.style.backgroundColor = 'white';
                        label.style.color = 'var(--primary-color)';
                    }
                }
                
                // Escuchar cambios en los inputs
                input.addEventListener('input', function() {
                    if (this.value !== '') {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            });
        });
    </script>
</body>
</html>