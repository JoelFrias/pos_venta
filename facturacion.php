<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facturación</title>
  <link rel="stylesheet" href="css/facturacion.css">
  <style>

    /* Estilos del modal */
    .modal {
      display: none; /* Ocultar por defecto */
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5); /* Fondo semitransparente */
    }

    /* Contenido del modal */
    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 80%;
      max-width: 500px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      animation: slide-down 0.3s ease-out;
    }

    /* Animación del modal */
    @keyframes slide-down {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Botón para cerrar el modal */
    .close-btn-producto, .close-btn-cliente {
      color: #aaa;
      float: right;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-btn:hover, .close-btn-producto, .close-btn-cliente:hov {
      color: #000;
    }

  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Facturación</h1>
    </header>

    <section class="client-info">
      <fieldset>
        <legend>Datos del Cliente</legend>
        <button id="buscar-cliente">Buscar Cliente</button>
        <label for="id-cliente">ID Cliente:</label>
        <input type="text" id="id-cliente" value="Seleccionar Cliente" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <label for="nombre-cliente">Nombre del Cliente:</label>
        <input type="text" id="nombre-cliente" value="Seleccionar Cliente" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <label for="empresa">Empresa:</label>
        <input type="text" id="empresa" value="Seleccionar Cliente" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
      </fieldset>
    </section>

    <!-- Modal Selección Cliente -->
    <div id="modal-seleccionar-cliente" class="modal">
        <div class="modal-content">
            <span class="close-btn-cliente">&times;</span>
            <h2>Buscar Cliente</h2>
            <input type="text" id="search-input-cliente" placeholder="Buscar por nombre o empresa" autocomplete="off">

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
                    <!-- Clientes añadidios dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script para abrir y cerrar el modal de selección de cliente -->
    <script>
      const modalCliente = document.getElementById("modal-seleccionar-cliente");
      const openModalButtonCliente = document.getElementById("buscar-cliente");
      const closeModalButtonCliente = document.querySelector(".close-btn-cliente");

      openModalButtonCliente.addEventListener("click", () => {
        modalCliente.style.display = "block";
      });

      closeModalButtonCliente.addEventListener("click", () => {
        modalCliente.style.display = "none";
      });

      window.addEventListener("click", (event) => {
        if (event.target === modalCliente) {
          modalCliente.style.display = "none";
        }
      });
    </script>


    <!-- Script para llenar tabla y buscar clientes en tiempo real -->
    <script>

      getDataClientes();

      document.getElementById("search-input-cliente").addEventListener("keyup", getDataClientes)

      function getDataClientes(){
        let input = document.getElementById('search-input-cliente').value
        let content = document.getElementById('table-body-cliente')
        let url = 'php/facturacion_buscadorClientes.php'
        let formData = new FormData()
        formData.append('campo', input)

        fetch(url, {
          method: 'POST',
          body: formData
        }).then(response => response.json())
          .then(data => {
            content.innerHTML = data
          }).catch(error => console.error(error))

      }

    </script>

    <!-- Script para seleccionar cliente -->
    <script>
      function selectCliente(id) {
        if (id == null) {
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

        modalCliente.style.display = "none";

      }
    </script>


    <section class="article-search">
      <fieldset>
        <legend>Buscador de Productos</legend>
        <button id="buscar-producto">Buscar Producto</button>
        <label for="id-producto">ID Producto:</label>
        <input type="text" id="id-producto" value="Seleccionar Producto" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <label for="descripcion-producto">Descripción Producto:</label>
        <input type="text" id="descripcion-producto" value="Seleccionar Producto" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <label for="cantidad-producto">Cantidad:</label>
        <input type="number" step="1" id="cantidad-producto" placeholder="Ingrese la cantidad">
        <label for="precio-1">Precio 1:</label>
        <input type="text" id="precio-1" value="Seleccionar Producto" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <input type="button" id="seleccion-precio-1" onclick="buscarProducto(1)" value="Seleccionar">
        <label for="precio-2">Precio 2:</label>
        <input type="text" id="precio-2" value="Seleccionar Producto" style = "color: black; opacity: 1; background-color:rgb(202, 202, 202);" disabled>
        <input type="button" id="seleccion-precio-2" onclick="buscarProducto(2)"value="Seleccionar">
      </fieldset>
    </section>

    <!-- Modal Selección Producto -->
    <div id="modal-seleccionar-producto" class="modal">
        <div class="modal-content">
            <span class="close-btn-producto">&times;</span>
            <h2>Buscar Producto</h2>
            <input type="text" id="search-input-productos" placeholder="Buscar Producto" autocomplete="off">

            <table id="table-buscar-producto">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Existencia</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="table-body-productos">
                  <!-- Productos añadidos dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script para abrir y cerrar el modal de selección de producto -->
    <script>
      const modalProducto = document.getElementById("modal-seleccionar-producto");
      const openModalButtonProducto = document.getElementById("buscar-producto");
      const closeModalButtonProducto = document.querySelector(".close-btn-producto");

      openModalButtonProducto.addEventListener("click", () => {
        modalProducto.style.display = "block";
      });

      closeModalButtonProducto.addEventListener("click", () => {
        modalProducto.style.display = "none";
      });

      window.addEventListener("click", (event) => {
        if (event.target === modalProducto) {
          modalProducto.style.display = "none";
        }
      });
    </script>

    <!-- Script para llenar tabla y buscar productos en tiempo real -->
    <script>

      getDataProductos()

      document.getElementById('search-input-productos').addEventListener('keyup', getDataProductos)

      function getDataProductos() {

        let inputP = document.getElementById('search-input-productos').value
        let contetP = document.getElementById('table-body-productos')
        let urlP = 'php/facturacion_buscadorProductos.php'

        let formDataP = new FormData()
        formDataP.append('campoProducto', inputP)

        fetch(urlP, {
          method: 'POST',
          body: formDataP
        }).then(response => response.json())
          .then(data => {
            contetP.innerHTML = data
          }).catch(error => console.error(error))
        
      }

    </script>

    <!-- Script para seleccionar Producto -->
    <script>
      function selectProducto(id) {
        if (id == null) {
          alert("Error al seleccionar producto");
          return;
        }

        fetch("php/facturacion_seleccionarProducto.php?id=" + id)
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              alert(data.error);
            } else {
              document.getElementById("id-producto").value = data.id;
              document.getElementById("descripcion-producto").value = data.descripcion;
              document.getElementById("precio-1").value = data.precioVenta1;
              document.getElementById("precio-2").value = data.precioVenta2;
            }
          })
          .catch(error => console.error("Error en fetch:", error));

        modalProducto.style.display = "none";

      }
    </script>

    <section class="invoice">
      <fieldset>
        <legend>Artículos en Factura</legend>
        <table>
          <thead>
            <tr>
              <th>ID ART</th>
              <th>Descripción</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Importe</th>
              <th>Eliminar</th>
            </tr>
          </thead>
          <tbody id="invoice-articles">
            <!-- Artículos añadidos dinámicamente -->
          </tbody>
        </table>
        <p id="mensaje-vacio" style="display: block; color: #f44336;">No hay artículos agregados.</p>
      </fieldset>
    </section>

    <section class="totals">
      <div>
        <p>Total: $<span id="total">0.00</span></p>
      </div>
    </section>

    <footer>
      <a href="facturacion.php"><button id="limpiar-factura">Limpiar Factura</button></a>
      <button id="procesar-factura">Procesar Factura</button>
      <button id="volver">Volver</button>
    </footer>
  </div>

  <!-- Script para añadir producto a factura -->
  <script>
        // Función para buscar el producto
        function buscarProducto(precio) {

            // Validar precio seleccionado
            if (precio == 1 || precio == 2) {

                let precioSeleccionado = (precio == 1) ? "precioVenta1" : "precioVenta2";
                let idProducto = document.getElementById("id-producto").value;
                let cantidad = document.getElementById("cantidad-producto").value;

                // Validar producto
                if (idProducto === "" || idProducto === "Seleccionar Producto") {
                    alert("Seleccione un producto");
                    return;
                }

                // Validar cantidad
                if (cantidad === "" || cantidad <= 0) {
                    alert("Ingrese una cantidad válida");
                    return;
                }

                // Se realiza la solicitud a PHP para agregar el producto
                fetch("php/facturacion_agregarProducto.php?id=" + idProducto + "&precioSeleccionado=" + precioSeleccionado + "&cantidad=" + cantidad)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            // Función para agregar el producto a la tabla
                            agregarATabla(data);
                        }
                    })
                    .catch(error => console.error("Error:", error));
            } else {
                alert("Error al seleccionar precio");
            }
        }

        // Función para agregar el producto a la tabla
        function agregarATabla(producto) {
          // Seleccionar el cuerpo de la tabla
          const tableBody = document.querySelector("#invoice-articles");

          if (!tableBody) {
              console.error("No se encontró el tbody");
              return;
          }

          // Crear una nueva fila para el producto
          const row = document.createElement("tr");

          // Crear celdas para cada dato del producto
          const cellId = document.createElement("td");
          cellId.textContent = producto.id;

          const cellDescripcion = document.createElement("td");
          cellDescripcion.textContent = producto.descripcion;

          const cellCantidad = document.createElement("td");
          cellCantidad.textContent = producto.cantidad.toFixed(2);

          const cellPrecio = document.createElement("td");
          cellPrecio.textContent = producto.precio.toFixed(2);

          const cellImporte = document.createElement("td");
          cellImporte.textContent = producto.importe.toFixed(2);

          // Crear celda para el botón de eliminar
          const cellEliminar = document.createElement("td");
          const botonEliminar = document.createElement("button");
          botonEliminar.textContent = "Eliminar";
          botonEliminar.classList.add("btn-eliminar");
          botonEliminar.onclick = function() {
              row.remove();
              calcularTotal();
              verificarTablaVacia();
          };

          // Añadir el botón a la celda
          cellEliminar.appendChild(botonEliminar);

          // Añadir las celdas a la fila
          row.appendChild(cellId);
          row.appendChild(cellDescripcion);
          row.appendChild(cellCantidad);
          row.appendChild(cellPrecio);
          row.appendChild(cellImporte);
          row.appendChild(cellEliminar);

          // Añadir la fila a la tabla
          tableBody.appendChild(row);

          calcularTotal();
          verificarTablaVacia();
      }

      function calcularTotal() {
        const tableBody = document.querySelector("#invoice-articles");
        const rows = tableBody.querySelectorAll("tr");

        let total = 0;

        rows.forEach(row => {
            const importeCell = row.querySelector("td:nth-child(5)"); // Columna de "Importe"
            if (importeCell) {
                const importe = parseFloat(importeCell.textContent);
                if (!isNaN(importe)) {
                    total += importe;
                }
            }
        });

        // Mostrar el total
        const totalElement = document.querySelector("#total");
        if (totalElement) {
            totalElement.textContent = total.toFixed(2); // Mostrar el total con dos decimales
        }
    }

    function verificarTablaVacia() {
      const tableBody = document.querySelector("#invoice-articles");
      const rows = tableBody.querySelectorAll("tr");

      // Si no hay filas (excluyendo el encabezado), mostrar el mensaje
      const mensaje = document.querySelector("#mensaje-vacio");
      if (rows.length === 0) {
          mensaje.style.display = "block"; // Mostrar el mensaje
      } else {
          mensaje.style.display = "none"; // Ocultar el mensaje
      }
    }

    </script>

</body>
</html>