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
        <li onclick="navigateTo('index.php')"><i class="fas fa-home"></i>Inicio</li>
        <li onclick="navigateTo('clientes.php')"><i class="fas fa-users"></i>Clientes</li>
        <li onclick="navigateTo('productos.php')"><i class="fas fa-box"></i> Productos</li>
        <li onclick="empleados(<?php echo $_SESSION['idPuesto'] ?>)"><i class="fa-solid fa-user"></i>Empleados</li>
        <li onclick="navigateTo('inventario.php')"><i class="fa-solid fa-warehouse"></i>Almacén</li>
        <li onclick="navigateTo('inventario-empleados.php')"><i class="fa-solid fa-boxes-stacked"></i></i>Inventario Empleados</li>
        <li onclick="navigateTo('factura-registro.php')"><i class="fa-solid fa-file-lines"></i></i>Registro de Facturas</li>
        <li onclick="inventario_transaccion(<?php echo $_SESSION['idPuesto'] ?>)"><i class="fa-solid fa-cart-flatbed"></i>Transacción Inventario</li>
        <li onclick="navigateTo('facturacion.php')"><i class="fas fa-cash-register"></i>Facturación</li>
        <li onclick="logout()"><i class="fas fa-sign-out-alt"></i>Cerrar Sesión</li>
    </ul>
</nav>

<script>

    function empleados(idPuesto) {
        if (idPuesto > 2) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: 'No tienes permisos para acceder a esta página.'
            });
        } else {
            navigateTo('empleados.php');
        }
    }

    function inventario_transaccion(idPuesto) {
        if (idPuesto > 2) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: 'No tienes permisos para acceder a esta página.'
            });
        } else {
            navigateTo('inventario-transaccion.php');
        }
    }

    function logout() {
        Swal.fire({
            icon: 'question',
            title: 'Cierre de Sesión',
            text: '¿Desea cerrar la sesión?',
            showCancelButton: true,
            confirmButtonText: 'Cerrar sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'php/logout.php';
            }
        });
    }
</script>