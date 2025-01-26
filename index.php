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
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="logo" style="cursor: pointer;" id="dassd">
                <h2>Pos Venta</h2>
                <button id="toggleMenu" class="toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Redirige al index cuando se preciona el logo -->
            <script>
                document.getElementById('dassd').addEventListener('click', function () {
                    window.location.href = 'index.php';
                });
            </script>

            <ul class="menu">
                <ul class="menu">
                    <li onclick="navigateTo('index.php')"><i class="fas fa-cogs"></i> Administracion</li>
                    <li onclick="navigateTo('cliente.php')"><i class="fas fa-cash-register"></i>Cajas</li>
                    <li onclick="navigateTo('clientes_nuevo.php')"><i class="fas fa-users"></i> Clientes</li>
                    <li onclick="navigateTo('actualizar_cliente.php')"><i class="fas fa-users"></i> Medidas</li>
                    <li onclick="navigateTo('actualizar_prestamo.php')"><i class="fas fa-cogs"></i> Categorías</li>
                    <li onclick="navigateTo('productos_nuevo.php')"><i class="fas fa-box"></i> Productos</li>
                    <li onclick="navigateTo('buscar_.php')"><i class="fas fa-sign-in-alt"></i> Entradas</li>
                    <li onclick="navigateTo('buscar_prestamos.php')"><i class="fas fa-sign-out-alt"></i> Salidas</li>
                </ul>
            </nav>
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

    <script src="Assets/js/menu.js"></script>
</body>
</html>