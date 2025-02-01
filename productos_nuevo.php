<?php
session_start();
require 'php/conexion.php';

// Validar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obtener y sanitizar los datos del formulario
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $idTipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0; // Captura el idTipo aquí
    $cantidad = floatval($_POST['cantidad']);
    $precioCompra = floatval($_POST['precioCompra']);
    $precio1 = floatval($_POST['precio1']);
    $precio2 = floatval($_POST['precio2']);
    $reorden = floatval($_POST['reorden']);

    // Debug: Imprimir el idTipo para verificar
    error_log("ID Tipo: " . $idTipo); // Esto se registrará en el log de errores

    // Manejo de errores con consultas preparadas
    try {
        // Iniciar la transacción
        $conn->begin_transaction();
    
        // Insertar en la tabla 'productos'
        $stmt = $conn->prepare("INSERT INTO productos (descripcion, idTipo, existencia, precioCompra, precioVenta1, precioVenta2, reorden, fechaRegistro, activo) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), TRUE)");
        $stmt->bind_param("siidddd", $descripcion, $idTipo, $cantidad, $precioCompra, $precio1, $precio2, $reorden);
        $stmt->execute();
    
        // Obtener el ID del producto recién insertado
        $idProducto = $stmt->insert_id;
    
        // Insertar en la tabla 'inventario'
        $stmt = $conn->prepare("INSERT INTO inventario (idProducto, existencia, ultima_actualizacion) 
                                VALUES (?, ?, NOW())");
        $stmt->bind_param("id", $idProducto, $cantidad);
        $stmt->execute();
    
        // Confirmar la transacción
        $conn->commit();
    
        // Almacenar mensaje de éxito en sesión y redirigir
        $_SESSION['status'] = 'success';
        header("Location: productos_nuevo.php");
        exit;
    
    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        $conn->rollback();
        $_SESSION['errors'][] = "Error al registrar producto: " . $e->getMessage();
        header("Location: productos_nuevo.php");
        exit;
    } finally {
        // Cerrar las declaraciones preparadas
        if (isset($stmt)) $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Ventas</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/mant_producto.css">
    <link rel="stylesheet" href="css/modo_ocuro.css">
    <!--  -->
    <link rel="stylesheet" href="css/producto_modal.css">
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
            
<!------------------------------------------------------------>
<!--------------------------->
<button onclick="document.getElementById('myModal').style.display='flex'" class="btn-abrir">Agregar tipo de producto</button>
  <!-- Botón para abrir el modal -->
   
<!-- Modal -->
<div id="myModal" class="MODAL2">
    <div class="modal-contenedor">
        <span onclick="document.getElementById('myModal').style.display='none'" class="cerrar">&times;</span>
        <h2>Registrar Tipo de Producto</h2>
        <br>
        <form action="modal_categoria.php" method="POST">
            <input type="text" name="descripcion" placeholder="Tipo de Producto" class="input-fila" required>
            <button type="submit" class="btn-subir">Registrar</button>
        </form>
    </div>
</div>
<!------------------------------------------------------------------------------------------------------------------->

        <!-- Overlay for mobile, no eliminar esto hace que aparezca las opciones sin recargar la pagina  -->
        <div class="overlay" id="overlay">
        </div>
        <div class="form-container">
        <h1 class="form-title">Registro de Productos</h1>
        
        <form class="registration-form" action="" method="POST">
            <fieldset>
                <legend>Datos del Producto</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Descripción:</label>
                        <input type="text" id="descripcion" name="descripcion" autocomplete="off" required>
                    </div>

                    <div class="form-group">
                      <label for="tipo_identificacion">Tipo de Producto:</label>
                      <select id="tipo" name="tipo" required>
         <option value="" disabled selected>Seleccionar</option>
        
        <?php
        // Obtener el id y la descripción
        $sql = "SELECT id, descripcion FROM productos_tipo ORDER BY descripcion ASC";
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
                    
                    <div class="form-group">
                        <label for="apellido">Precio de Compra:</label>
                        <input type="number" id="precioCompra" name="precioCompra" step="0.01" autocomplete="off" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa">Precio de Venta 1:</label>
                        <input type="number" id="precio1" name="precio1" step="0.01" autocomplete="off" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="identificacion">Precio de Venta 2:</label>
                        <input type="number" id="precio2" name="precio2" step="0.01" autocomplete="off" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Cantidad Existente:</label>
                        <input type="number" id="cantidad" name="cantidad" step="0.01" autocomplete="off" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Reorden:</label>
                        <input type="number" id="reorden" name="reorden" step="0.01" autocomplete="off" required>
                    </div>
                </div>
            </fieldset>

            <button type="submit" class="btn-submit">Registrar Producto</button>
        </form>
    </div>
<?php 
if (isset($_SESSION['status']) && $_SESSION['status'] === 'success') {
    echo "
        <script>
            Swal.fire({
                title: '¡Éxito!',
                text: 'El producto ha sido registrado exitosamente.',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then(function() {
                window.location.href = 'productos_nuevo.php'; 
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
    <script src="js/producto_modal.js"></script>
    <script src="js/menu.js"></script>
    <script src="js/modo_oscuro.js"></script>
    <script src="js/oscuro_recargar.js"></script>
</body>
</html>