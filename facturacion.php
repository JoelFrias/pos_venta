<?php

/* Verificacion de sesion */

// Iniciar sesión
session_start();

// Configurar el tiempo de caducidad de la sesión
$inactivity_limit = 900; // 15 minutos en segundos

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header('Location: login.php'); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Eliminar todas las variables de sesión
    session_destroy(); // Destruir la sesión
    header("Location: login.php?session_expired=session_expired"); // Redirigir al login
    exit(); // Detener la ejecución del script
}

// Actualizar el tiempo de la última actividad
$_SESSION['last_activity'] = time();

/* Fin de verificacion de sesion */

require 'php/conexion.php';

$sql = "SELECT id, descripcion, existencia, precioVenta1, precioVenta2 FROM productos LIMIT 30";
$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conn->error); // Muestra el error de la consulta
}

if ($result->num_rows > 0) {
    // echo "Número de filas: " . $result->num_rows; // Muestra el número de filas obtenidas
} else {
    echo "0 resultados";
}
?>
<!--NO BORRAR ESTO:> PORQUE ESTO ES COMO MUESTRA LOS PRODUCTOS EN SU RESPECTIVAS POSISCIONES -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturacion</title>
    <link rel="stylesheet" href="css/facturacion.css">
    <link rel="stylesheet" href="css/menu.css">
    <!-- <link rel="stylesheet" href="css/menu.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<!-- Contenedor principal -->
<div class="container">
        <!-- Botón para mostrar/ocultar el menú en dispositivos móviles -->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Incluir el menú -->
        <?php require 'menu.html' ?>
        <script src="js/sidebar_menu.js"></script>
        

        <!-- Overlay para dispositivos móviles -->
        <div class="overlay" id="overlay"></div>

    <!-- Contenedor principal -->
    
<button class="toggle-menu" id="toggleMenuFacturacion">☰</button>

    <div class="facturacion-container">
        <h2>Facturación</h2><br>
        <h3>Seleccione los productos</h3><br>
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar productos...">
            <button id="searchButton" class="search-button">Buscar</button>
        </div>
       <div class="products-grid" id="productsGrid">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
                    echo '    <div class="product-info">';
                    echo '        <div>';
                    echo '            <div class="product-name">' . $row["descripcion"] . '</div>';
                    echo '            <div class="product-quantity">Existencia: ' . $row["existencia"] . '</div>';
                    echo '        </div>';
                    echo '        <div class="product-total"></div>';
                    echo '    </div>';
                    echo '    <div class="product-inputs">';
                    echo '        <input type="number" class="product-input" id="input1-' . $row["id"] . '" value="' . $row["precioVenta2"] . '" readonly>';
                    echo '        <input type="number" class="product-input" id="input2-' . $row["id"] . '" value="' . $row["precioVenta1"] . '" readonly>';
                    echo '        <button class="product-button" id="button1-' . $row["id"] . '" onclick="handleButton2(' . $row["id"] . ', ' . $row["precioVenta2"] . ')">Precio 2</button>';
                    echo '        <button class="product-button" id="button2-' . $row["id"] . '" onclick="handleButton1(' . $row["id"] . ', ' . $row["precioVenta1"] . ')">Precio 1</button>';
                    echo '    </div>';
                    echo '    <input type="number" class="quantity-input" id="quantity-' . $row["id"] . '" placeholder="Cantidad a llevar" min="1">';
                    echo '    <button class="quantity-button" onclick="addToCart(' . $row["id"] . ', \'' . addslashes($row["descripcion"]) . '\', ' . $row["precioVenta1"] . ')">Agregar Producto</button>';
                    echo '</div>';
                }
            } else {
                echo "No hay productos disponibles.";
            }
            ?>
        </div>
    </div>
<!-- ---------------0aca codigo 100%----------------->
<!-- Modal para mostrar la información del cliente -->
<!-- Modal Selección Cliente -->
<div id="modal-seleccionar-cliente" class="modal">
    <div class="modal-content">
        <span class="close-btn-cliente">&times;</span>
        <h2 class="titulo-centrado" >Buscar Cliente</h2>
        <input type="text" id="search-input-cliente" placeholder="Buscar por id, nombre o empresa" autocomplete="off">
        <table id="table-buscar-cliente">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="table-body-cliente">
                <!-- Clientes añadidos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<!-- Campos del cliente en el menú desplegable -->
<div class="order-menu" id="orderMenu">
    <div class="menu-header">
        <h2 class="menu-title">Menu<span>Facturacion</span></h2>
    </div>
    <div class="menu-content">
        <input type="text" class="menu-input" id="id-cliente" placeholder="ID del cliente" readonly>
        <input type="text" class="menu-input" id="nombre-cliente" placeholder="Nombre del cliente" readonly>
        <input type="text" class="menu-input" id="empresa" placeholder="Empresa" readonly>
        <button class="menu-button" id="buscar-cliente">Buscar Cliente</button>

          <!-- Lista de productos agregados -->
          <div class="order-list" id="orderList">
            <!-- Los productos se agregarán aquí dinámicamente -->
        </div>

        <!-- Total de la compra -->
        <div class="order-total">
            <span>Total:</span>
            <span id="totalAmount">RD$ 0.00</span>
        </div>
    </div>
    <!-- Nuevos botones en fila -->
    <div class="menu-footer">
        <button class="footer-button" id="btn-volver">Volver Atrás</button>
        <button class="footer-button" id="btn-limpiar">Limpiar</button>
        <button class="footer-button primary" id="btn-generar">Generar Factura</button>
    </div>
</div>

<!-- Modal para procesar la factura -->
<div id="modal-procesar-factura" class="modal">
    <div class="modal-content">
    <span class="close-btn-factura">&times;</span>
    <h2>Procesar Factura</h2>
    <label for="tipo-factura">Tipo de factura:</label>
    <select id="tipo-factura">
        <option value="contado">Contado</option>
        <option value="credito">Crédito</option>
    </select>
    <label for="forma-pago">Forma de Pago:</label>
    <select id="forma-pago">
        <option value="efectivo">Efectivo</option>
        <option value="tarjeta">Tarjeta</option>
        <option value="transferencia">Transferencia</option>
    </select>
    <div id="div-numero-tarjeta" style="display: none;">
        <label for="numero-tarjeta">Número de Tarjeta:</label>
        <input type="text" name="numero-tarjeta" id="numero-tarjeta" placeholder="Ingrese los últimos 4 dígitos de la tarjeta" maxlength="4">
    </div>
    <div id="div-numero-autorizacion" style="display: none;">
        <label for="numero-autorizacion">Número de autorización:</label>
        <input type="text" name="numero-autorizacion" id="numero-autorizacion" placeholder="Ingrese los 4 últimos dígitos de autorización" maxlength="4">
    </div>
    <div id="div-banco" style="display: none;">
        <label for="banco">Seleccione el banco:</label>
        <select name="banco" id="banco">
        <option value="0" disabled selected>Seleccionar banco</option>
        <?php
        $sql = "SELECT * FROM bancos ORDER BY id ASC";
        $resultado = $conn->query($sql);
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
            echo "<option value='" . $fila['id'] . "'>" . $fila['nombreBanco'] . "</option>";
            }
        } else {
            echo "<option value='' disabled>No hay opciones</option>";
        }
        ?>
        </select>
    </div>
    <div id="div-destino" style="display: none;">
        <label for="destino-cuenta">Seleccione el destino:</label>
        <select name="banco" id="banco">
        <option value="0" disabled selected>Seleccionar destino</option>
        <?php
        $sql = "SELECT * FROM destinoCuentas ORDER BY id ASC";
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
    <label for="monto-pagado">Monto Pagado:</label>
    <input type="number" name="monto-pagado" id="monto-pagado" placeholder="Ingrese la cantidad pagada" step="0.01" min="0" required>
    <div id="div-devuelta">
        <label for="devuelta">Devuelta:<span id="devuelta">0.00</span></label>
    </div>
    <div id="botones-facturas">
        <button id="guardar-factura" class="footer-button">Guardar Factura</button>
        <button id="guardar-imprimir-factura" class="footer-button">Guardar e Imprimir Factura</button>
    </div>
    </div>
</div>

<script>

    // Script para abrir y cerrar el modal de prcesar factura
    const modalfactura = document.getElementById("modal-procesar-factura");
    const openModalButtonfactura = document.getElementById("btn-generar");
    const closeModalButtonfactura = document.querySelector(".close-btn-factura");

    openModalButtonfactura.addEventListener("click", () => {
        modalfactura.style.display = "block";
        getDataClientes(); // Cargar datos al abrir el modal
    });

    closeModalButtonfactura.addEventListener("click", () => {
        modalfactura.style.display = "none";
    });

    window.addEventListener("click", (event) => {
        if (event.target === modalfactura) {
            modalfactura.style.display = "none";
        }
    });

    // Script para mostrar u ocultar campos de información de pagos
    const metodo = document.getElementById("forma-pago");
    const tarjeta = document.getElementById("div-numero-tarjeta");
    const autorizacion = document.getElementById("div-numero-autorizacion");
    const banco = document.getElementById("div-banco");
    const destino = document.getElementById("div-destino");
    
    metodo.addEventListener("change", () => {
    if (metodo.value === "tarjeta") {
        tarjeta.style.display = "block";
        autorizacion.style.display = "block";
        banco.style.display = "block";
        destino.style.display = "block";

        tarjeta.value = "";
        autorizacion.value = "";
        banco.value = "0";
        destino.value = "0";
        document.getElementById("monto-pagado").value = "";

    } else if (metodo.value === "transferencia") {
        tarjeta.style.display = "none";
        autorizacion.style.display = "block";
        banco.style.display = "block";
        destino.style.display = "block";

        tarjeta.value = "";
        autorizacion.value = "";
        banco.value = "0";
        destino.value = "0";
        document.getElementById("monto-pagado").value = "";

    } else {
        tarjeta.style.display = "none";
        autorizacion.style.display = "none";
        banco.style.display = "none";
        destino.style.display = "none";

        tarjeta.value = "";
        autorizacion.value = "";
        banco.value = "0";
        destino.value = "0";
        document.getElementById("monto-pagado").value = "";

    }
    });


</script>

<!-------------------------------------------------------------------------->
<!-----------------------------cliente-------------------------------------->

<script>

// Script para abrir y cerrar el modal de selección de cliente
const modalCliente = document.getElementById("modal-seleccionar-cliente");
const openModalButtonCliente = document.getElementById("buscar-cliente");
const closeModalButtonCliente = document.querySelector(".close-btn-cliente");

openModalButtonCliente.addEventListener("click", () => {
    modalCliente.style.display = "block";
    getDataClientes(); // Cargar datos al abrir el modal
});

closeModalButtonCliente.addEventListener("click", () => {
    modalCliente.style.display = "none";
});

window.addEventListener("click", (event) => {
    if (event.target === modalCliente) {
        modalCliente.style.display = "none";
    }
});

getDataClientes();

// Script para llenar tabla y buscar clientes en tiempo real
document.getElementById("search-input-cliente").addEventListener("keyup", getDataClientes);

function getDataClientes() {
    const input = document.getElementById('search-input-cliente').value;
    const content = document.getElementById('table-body-cliente');
    const url = 'php/facturacion_buscadorClientes.php';
    const formData = new FormData();
    formData.append('campo', input);

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => console.error("Error al buscar clientes:", error));
}

// Script para seleccionar cliente
function selectCliente(id) {
    if (!id) {
        alert("Error al seleccionar cliente");
        return;
    }

    fetch("php/facturacion_seleccionarCliente.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                document.getElementById("id-cliente").value = data.id;
                document.getElementById("nombre-cliente").value = data.nombre;
                document.getElementById("empresa").value = data.empresa;
            }
        })
        .catch(error => console.error("Error en fetch:", error));

    modalCliente.style.display = "none"; // Cerrar el modal después de seleccionar
}
</script>   
<!-------------------------------------------------------------------------------->

<!-----codigo de pasas los productos------>
<!-------CODIGO DE PRODUCTO--------->

<script>
// Variable global para almacenar el precio seleccionado
let selectedPrices = {};

// Variable global para almacenar el total de la compra
let total = 0;

function handleButton1(productId, price1) {
    selectedPrices[productId] = price1; // Almacenar precio1
    document.getElementById(`button2-${productId}`).classList.add("selected");
    document.getElementById(`button1-${productId}`).classList.remove("selected");
}

function handleButton2(productId, price2) {
    selectedPrices[productId] = price2; // Almacenar precio2
    document.getElementById(`button1-${productId}`).classList.add("selected");
    document.getElementById(`button2-${productId}`).classList.remove("selected");
}

// Función para agregar productos al carrito
function addToCart(productId, productName) {
    const quantityInput = document.getElementById(`quantity-${productId}`);
    const quantity = quantityInput.value;

    if (quantity <= 0) {
        alert("La cantidad debe ser mayor que 0.");
        return;
    }

    // Obtener el precio seleccionado
    const selectedPrice = selectedPrices[productId];
    if (!selectedPrice) {
        alert("Por favor, selecciona un precio antes de agregar al carrito.");
        return;
    }

    // Calcular el subtotal del producto
    const subtotal = selectedPrice * quantity;

    // Crear el elemento del producto en el carrito
    const orderList = document.getElementById('orderList');
    const orderItem = document.createElement('div');
    orderItem.classList.add('order-item');

    orderItem.innerHTML = `
        <div class="item-info">
            <span class="item-name">${productName}</span>
            <span class="item-base-price">RD$${selectedPrice.toFixed(2)}</span>
        </div>
        <div class="item-total">
            <span class="item-quantity">x${quantity}</span>
            <span class="item-total-price">RD$${subtotal.toFixed(2)}</span>
        </div>
        <button class="delete-item" onclick="removeFromCart(this, ${subtotal})">&times;</button>
    `;

    // Agregar el producto al carrito
    orderList.appendChild(orderItem);

    // Actualizar el total
    total += subtotal;
    updateTotal();

    // Limpiar el campo de cantidad
    quantityInput.value = '';
}

// Función para eliminar un producto del carrito
function removeFromCart(button, subtotal) {
    // Restar el subtotal del producto eliminado
    total -= subtotal;
    updateTotal();

    // Eliminar el elemento del DOM
    button.parentElement.remove();
}

// Función para actualizar el total en el modal
function updateTotal() {
    document.getElementById('totalAmount').textContent = `RD$ ${total.toFixed(2)}`;
}
// Función para eliminar un producto del carrito
function removeFromCart(button, subtotal) {
    // Restar el subtotal del producto eliminado
    total -= subtotal;
    updateTotal();

    // Eliminar el elemento del DOM
    button.parentElement.remove();
}

// Función para uso de , ., donde esta minimunfraction y maximumfraction
function updateTotal() {
    document.getElementById('totalAmount').textContent = `RD$ ${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
</script>

<!--------------------------------------------------------------------->
<!--------------PARA ABRIR EL MENU DESPEJABLE DE FACTURA--------------->
<!--------------------------------------------------------------------->
<script>
       // Toggle del menú
    const toggleButton = document.getElementById('toggleMenuFacturacion');
    const orderMenu = document.getElementById('orderMenu');

    toggleButton.addEventListener('click', () => {
        orderMenu.classList.toggle('active');
    });
</script>

 <!-- Scripts adicionales -->
    <script src="js/menu.js"></script>
    <script src="js/modo_oscuro.js"></script>
    <script src="js/oscuro_recargar.js"></script>
</body>
</html>