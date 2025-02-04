<?php
    // Iniciar sesión
    session_start();

    // Verificar si el usuario ha iniciado sesión
    if (!isset($_SESSION['username'])) {
        // Redirigir a la página de inicio de sesión con un mensaje de error
        header('Location: login.php?session_expired=session_expired');
        exit(); // Detener la ejecución del script
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS</title>
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/mant_cliente.css">
    <link rel="stylesheet" href="css/modo_oscuro.css">
    
    <!-- Importación de iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Librería para alertas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <!-- Botón para alternar menú en dispositivos móviles -->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Switch para modo oscuro -->
        <label class="switch">
            <input id="toggleDarkMode" type="checkbox" />
            <span class="slider"></span>
        </label>
        
        <!-- Inclusión del menú de navegación -->
        <?php require 'menu.html' ?>
        
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
    </div>

    <p>Sesion iniciada como: <?php echo $_SESSION['username']?></p>
    
    <!-- Scripts JS -->
    <script src="js/menu.js"></script>
    <script src="js/modo_oscuro.js"></script>
    <script src="js/oscuro_recargar.js"></script>
</body>
</html>
