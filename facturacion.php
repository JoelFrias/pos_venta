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
        <input type="text" id="id-cliente" value="Seleccionar Cliente" disabled>
        <label for="nombre-cliente">Nombre del Cliente:</label>
        <input type="text" id="nombre-cliente" value="Seleccionar Cliente" disabled>
        <label for="empresa">Empresa:</label>
        <input type="text" id="empresa" value="Seleccionar Cliente" disabled>
      </fieldset>
    </section>

    <!-- Modal Selección Cliente -->
    <div id="modal-seleccionar-cliente" class="modal">
        <div class="modal-content">
            <span class="close-btn-cliente">&times;</span>
            <h2>Buscar Cliente</h2>
            <input type="text" id="search-input-cliente" placeholder="Buscar por nombre o empresa">

            <table id="table-buscar-cliente">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Empresa</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                  <?php
                    include 'php/conexion.php';
                    $sql = "SELECT id,CONCAT(nombre,' ',apellido) AS nombreCompleto, empresa FROM clientes ORDER BY nombre ASC limit 5";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                      while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["id"] . "</td>";
                        echo "<td>" . $row["nombreCompleto"] . "</td>";
                        echo "<td>" . $row["empresa"] . "</td>";
                        echo "<td><button>Seleccionar</button></td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4'>No se encontraron registros</td></tr>";
                    }
                    $conn->close();
                  ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
      // Modal Cliente
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

    <section class="article-search">
      <fieldset>
        <legend>Buscador de Productos</legend>
        <button id="buscar-producto">Buscar Producto</button>
        <label for="id-producto">ID Producto:</label>
        <input type="text" id="id-producto" value="Seleccionar Producto" disabled>
        <label for="descripcion-producto">Descripción Producto:</label>
        <input type="text" id="descripcion-producto" value="Seleccionar Producto" disabled>
        <label for="precio-1">Precio 1:</label>
        <input type="text" id="precio-1" value="Seleccionar Producto" disabled>
        <input type="button" id="seleccion-precio-1" value="Seleccionar">
        <label for="precio-2">Precio 2:</label>
        <input type="text" id="precio-2" value="Seleccionar Producto" disabled>
        <input type="button" id="seleccion-precio-2" value="Seleccionar">
      </fieldset>
    </section>

    <!-- Modal Selección Producto -->
    <div id="modal-seleccionar-producto" class="modal">
        <div class="modal-content">
            <span class="close-btn-producto">&times;</span>
            <h2>Buscar Producto</h2>
            <input type="text" id="search-input-producto" placeholder="Buscar Producto">

            <table id="table-buscar-producto">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Existencia</th>
                        <th>Seleccionar</th>
                    </tr>
                </thead>
                <tbody>
                  <?php
                    include 'php/conexion.php';
                    $sql = "SELECT id,descripcion,existencia FROM productos ORDER BY descripcion ASC limit 5";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                      while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["id"] . "</td>";
                        echo "<td>" . $row["descripcion"] . "</td>";
                        echo "<td>" . $row["existencia"] . "</td>";
                        echo "<td><button>Seleccionar</button></td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4'>No se encontraron registros</td></tr>";
                    }
                    $conn->close();
                  ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
      // Modal Producto
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

      // Selección de producto
      document.querySelectorAll('#table-buscar-producto button').forEach(button => {
        button.addEventListener('click', function() {
          let row = this.closest('tr');
          document.getElementById('id-producto').value = row.cells[0].textContent;
          document.getElementById('descripcion-producto').value = row.cells[1].textContent;
          document.getElementById('precio-1').value = row.cells[2].textContent;
          modalProducto.style.display = 'none';
        });
      });
    </script>

    <section class="invoice">
      <fieldset>
        <legend>Artículos en Factura</legend>
        <table>
          <thead>
            <tr>
              <th>ID ART</th>
              <th>DESCRIPCIÓN</th>
              <th>CANTIDAD</th>
              <th>PRECIO</th>
              <th>IMPORTE</th>
              <th>ITBIS</th>
              <th>SUBTOTAL</th>
            </tr>
          </thead>
          <tbody id="invoice-articles">
            <!-- Artículos añadidos dinámicamente -->
          </tbody>
        </table>
      </fieldset>
    </section>

    <section class="totals">
      <div>
        <label>Subtotal:</label>
        <span id="subtotal">RD$ 0.00</span>
      </div>
      <div>
        <label>ITBIS:</label>
        <span id="itbis">RD$ 0.00</span>
      </div>
      <div>
        <label>Total:</label>
        <span id="total">RD$ 0.00</span>
      </div>
    </section>

    <footer>
      <button id="limpiar-factura">Limpiar Factura</button>
      <button id="procesar-factura">Procesar Factura</button>
      <button id="volver">Volver</button>
    </footer>
  </div>
</body>
</html>
