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
        <li onclick="navigateTo('clientes.php')"><i class="fa-solid fa-user-group"></i><span>Clientes</span></li>
        <li onclick="navigateTo('productos.php')"><i class="fa-solid fa-box-open"></i></i><span>Productos</span></li>
        <li onclick="navigateTo('factura-registro.php')"><i class="fa-solid fa-list-ul"></i></i><span>Registro de Facturas</span></li>
        <li onclick="navigateTo('inventario.php')"><i class="fa-solid fa-warehouse"></i><span>Almacén</span></li>
        <li onclick="navigateTo('inventario-empleados.php')"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario Personal</span></li>
        <li onclick="navigateTo('facturacion.php')"><i class="fa-solid fa-shop"></i><span>Facturación</span></li>
        <li onclick="navigateTo('caja.php')"><i class="fa-solid fa-cash-register"></i><span>Caja</span></li>

        <?php if ($_SESSION['idPuesto'] <= 2): ?>
            <li onclick="panelAdministrativo(<?php echo $_SESSION['idPuesto'] ?>)"><i class="fa-solid fa-screwdriver-wrench"></i><span>Panel Administrativo</span></li>
        <?php endif; ?>
        
        <li onclick="logout()"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Cerrar Sesión</span></li>
    </ul>

</nav>

<script src="js/menu.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>