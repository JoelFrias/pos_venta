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

    <script src="js/menu.js"></script>
</body>
</html>