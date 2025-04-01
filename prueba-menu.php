<!-- Botón móvil -->
<button id="mobileToggle" class="toggle-btn">
    <i class="fas fa-bars"></i>
</button>

<!-- Barra lateral de navegación -->
<nav class="sidebar" id="sidebar">
    <div class="logo" style="cursor: pointer;" id="dassd">
        <h2>YSAPELLI</h2>
        <!-- Botón para alternar el menú -->
        <button id="toggleMenu" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Menú de navegación -->
    <ul class="menu">
        <li onclick="navigateTo('index.php')"><i class="fas fa-home"></i><span>Inicio</span></li>
        <li onclick="navigateTo('clientes.php')"><i class="fas fa-users"></i><span>Clientes</span></li>
        <li onclick="navigateTo('productos.php')"><i class="fas fa-box"></i><span>Productos</span></li>
        <li onclick="navigateTo('factura-registro.php')"><i class="fa-solid fa-file-lines"></i><span>Registro de Facturas</span></li>
        <li onclick="navigateTo('inventario.php')"><i class="fa-solid fa-warehouse"></i><span>Almacén</span></li>
        <li onclick="navigateTo('inventario-empleados.php')"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario Empleados</span></li>
        <li onclick="inventario_transaccion(<?php echo $_SESSION['idPuesto'] ?>)"><i class="fa-solid fa-cart-flatbed"></i><span>Transacción Inventario</span></li>
        <li onclick="navigateTo('facturacion.php')"><i class="fas fa-cash-register"></i><span>Facturación</span></li>
        <li onclick="panelAdministrativo(<?php echo $_SESSION['idPuesto'] ?>)"><i class="fa-solid fa-screwdriver-wrench"></i><span>Panel Administrativo</span></li>
        <li onclick="logout()"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></li>
    </ul>
</nav>

<script src="js/prueba-menu.js"></script>