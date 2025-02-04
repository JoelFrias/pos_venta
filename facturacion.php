<?php

// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    // Redirigir a la página de inicio de sesión con un mensaje de error
    header('Location: login.php?session_expired=session_expired');
    exit(); // Detener la ejecución del script
}

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
</head>
<body>
    
<button class="toggle-menu" id="toggleMenu">☰</button>

    <div class="container">
        <h2 class="title">Seleccione los Productos</h2>
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
                    echo '        <input type="number" class="product-input" id="input1-' . $row["id"] . '" value="' . $row["precioVenta2"] . '">';
                    echo '        <input type="number" class="product-input" id="input2-' . $row["id"] . '" value="' . $row["precioVenta1"] . '">';
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
        <input type="text" class="menu-input" id="id-cliente" placeholder="ID del cliente">
        <input type="text" class="menu-input" id="nombre-cliente" placeholder="Nombre del cliente">
        <input type="text" class="menu-input" id="empresa" placeholder="Empresa">
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
    const toggleButton = document.getElementById('toggleMenu');
    const orderMenu = document.getElementById('orderMenu');

    toggleButton.addEventListener('click', () => {
        orderMenu.classList.toggle('active');
    });

    /*

    // Event listeners
    document.getElementById('searchButton').addEventListener('click', searchProducts);
    document.getElementById('minPriceButton').addEventListener('click', filterByMinPrice);
    document.getElementById('maxPriceButton').addEventListener('click', filterByMaxPrice);

    // Inicializar
    document.addEventListener('DOMContentLoaded', () => renderProducts(products));

    */
    
</script>
  
</body>
</html>