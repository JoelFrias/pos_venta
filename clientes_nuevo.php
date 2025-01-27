<?php
session_start();
require 'php/conexion.php';

// Inicializar variables
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

    // Validar campos obligatorios se puede poner mas pero me dio pereza y lo puse por html
    if (empty($nombre) || empty($apellido) || empty($identificacion)) {
        echo "Los campos Nombre, Apellido e Identificación son obligatorios.";
        exit;
    }

    // Manejo de errores con consultas preparadas
    try {
        // Iniciar 
        $conn->begin_transaction();

        // Insertar en la tabla 'clientes'
        $stmt_cliente = $conn->prepare("INSERT INTO clientes (nombre, apellido, empresa, tipo_identificacion, identificacion, telefono, notas, fechaRegistro, activo) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(),TRUE)");
        $stmt_cliente->bind_param("sssssss", $nombre, $apellido, $empresa, $tipo_identificacion, $identificacion, $telefono, $notas);
        $stmt_cliente->execute();

        // Obtener el ID del cliente recién insertado
        $cliente_id = $conn->insert_id;

        // Insertar en la tabla 'clientes_cuenta'
        $stmt_cuenta = $conn->prepare("INSERT INTO clientes_cuenta (id, limite_credito, balance) 
                                       VALUES (?, ?, ?)");
        $stmt_cuenta->bind_param("idd", $cliente_id, $limite_credito, $limite_credito);
        $stmt_cuenta->execute();

        // Insertar en la tabla 'clientes_direcciones'
        $stmt_direccion = $conn->prepare("INSERT INTO clientes_direcciones (id, no, calle, sector, ciudad, referencia) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_direccion->bind_param("isssss", $cliente_id ,$no, $calle, $sector, $ciudad, $referencia);
        $stmt_direccion->execute();

         // Confirmar la transacción
         $conn->commit();

         // Almacenar mensaje de éxito en sesión y redirigir
         $_SESSION['status'] = 'success';
         header("Location: clientes_nuevo.php");
         exit;
 
     } catch (Exception $e) {
         // En caso de error, revertir la transacción
         $conn->rollback();
         $_SESSION['errors'][] = "Error al registrar cliente: " . $e->getMessage();
         header("Location: clientes_nuevo.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS </title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/mant_cliente.css">
    <!-- imports para el diseno de los iconos-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
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
        <div class="overlay" id="overlay">
        </div>
        <div class="form-container">
        <h1 class="form-title">Registro de Cliente</h1>
        
        <form class="registration-form" action="" method="POST">
            <fieldset>
                <legend>Datos del Cliente</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" autocomplete="off" placeholder="Ingrese el Nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" autocomplete="off" placeholder="ingrese el apellido" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa">Empresa:</label>
                        <input type="text" id="empresa" name="empresa" autocomplete="off" placeholder="ingrese nombre de la empresa" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_identificacion">Tipo de Identificación:</label>
                        <select id="tipo_identificacion" name="tipo_identificacion" required>
                            <option value="" disabled selected>Seleccionar</option> <!-- Opción vacía evita que pueda selecionar eso-->
                            <option value="cedula">Cédula</option>
                            <option value="rnc">RNC</option>
                            <option value="pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="identificacion">Número de Identificación:</label>
                        <input type="text" id="identificacion" name="identificacion" autocomplete="off" placeholder="ingrese la indentificacion" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" autocomplete="off" placeholder="000-000-0000" required maxlength="12" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="notas">Notas:</label>
                    <textarea id="notas" name="notas" placeholder="Ojo notas repecto al cliente" required>
                    </textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Datos de la Cuenta</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="limite_credito">Límite de Crédito:</label>
                        <input type="number" id="limite_credito" name="limite_credito" step="0.01" autocomplete="off" placeholder="ingrese un limite de credito" required>
                    </div>
            </fieldset>

            <fieldset>
                <legend>Dirección</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="no">Número:</label>
                        <input type="text" id="no" name="no" autocomplete="off" placeholder=" Ingrese solo el numero de calle" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="calle">Calle:</label>
                        <input type="text" id="calle" name="calle" autocomplete="off" placeholder="ingrese el nombre de la calle" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sector">Sector:</label>
                        <input type="text" id="sector" name="sector" autocomplete="off" placeholder="ingrese el sector" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ciudad">Ciudad:</label>
                        <input type="text" id="ciudad" name="ciudad" autocomplete="off" placeholder="ingrese la cuidad" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="referencia">Referencia:</label>
                    <textarea id="referencia" name="referencia" placeholder="JOhel no se que poner xs" required></textarea>
                </div>
            </fieldset>

            <button type="submit" class="btn-submit">Registrar Cliente</button>
        </form>
    </div>
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
                    window.location.href = 'clientes_nuevo.php'; 
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

    </div>
    <script src="js/menu.js"></script>
                
    <!-- NUMERO TELEFONICO-->
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
<!--  -->

</body>
</html>
