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

    include_once 'php/conexion.php';

    // Variables
    $idCliente = $_GET["idCliente"];

    // Tabla Payment History
    $sqlph = "SELECT
                DATE_FORMAT(chp.fecha, '%d/%m/%Y %l:%i %p') AS fechaph,
                chp.metodo AS metodoph,
                chp.monto AS montoph
            FROM
                clientes_historialpagos AS chp
            WHERE
                chp.idCliente = ?
            ORDER BY
                chp.fecha
            DESC
            LIMIT 5";
    $stmtph = $conn->prepare($sqlph);
    $stmtph->bind_param("i", $idCliente);
    $stmtph->execute();
    $resultsph = $stmtph->get_result();

    // Tabla Facturas Pendientes
    $sqlf = "SELECT
                f.numFactura AS nf,
                DATE_FORMAT(f.fecha, '%d/%m/%Y %l:%i %p') AS fechaf,
                f.total_ajuste AS totalf,
                f.balance AS balancef
            FROM
                facturas AS f
            WHERE
                f.balance > 0 AND f.idCliente = ?
            ORDER BY
                f.fecha
            DESC";
    $stmtf = $conn->prepare($sqlf);
    $stmtf->bind_param("i", $idCliente);
    $stmtf->execute();
    $resultsf = $stmtf->get_result();

    
    // Informacion del cliente
    $sqlc = "SELECT
                c.id AS idc,
                CONCAT(c.nombre, ' ', c.apellido) AS nombrec,
                c.empresa AS empresac,
                c.telefono AS telefonoc,
                cc.limite_credito AS limitec,
                cc.balance AS balancec,
                COALESCE(SUM(f.balance),
                0) AS adeudadoc
            FROM
                clientes AS c
            LEFT JOIN clientes_cuenta AS cc
            ON
                cc.idCliente = c.id
            LEFT JOIN facturas AS f
            ON
                f.idCliente = c.id
            WHERE
                c.id = ?";
    $stmtc = $conn->prepare($sqlc);
    $stmtc->bind_param("i", $idCliente);
    $stmtc->execute();
    $resultsc = $stmtc->get_result();
    $rowc = $resultsc->fetch_assoc();

    // FORMATO DE MONEDA
    $limitec = number_format($rowc['limitec'], 2, '.', ',');
    $balancec = number_format($rowc['balancec'], 2, '.', ',');
    $adeudadoc = number_format($rowc['adeudadoc'], 2, '.', ',');


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Avance de Cuenta</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            color: #333;
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }

        .header h2 {
            color: #007bff;
        }

        /* Flex Container Responsive Layout */
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .client-data, .payment-section {
            flex: 1;
            min-width: 300px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .form-row label {
            font-weight: bold;
            color: #555;
        }

        /* Payment Input Styles */
        .payment-input {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .payment-input > div {
            flex: 1;
            min-width: 200px;
        }

        .payment-input label {
            display: block;
            margin-bottom: 5px;
        }

        .payment-input input,
        .payment-input select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Button Styles */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* History Tables */
        .history-tables {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .history-table {
            flex: 1;
            min-width: 300px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table-container {
            max-width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        /* Estilos para el modal de historial de pagos */
        .modal-history-payment {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content-history-payments {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 1200px;
            max-height: 90%;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .close-modal-history-payments {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 15px;
        }

        .close-modal-history-payments:hover,
        .close-modal-history-payments:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 15px;
        }

        .pagination a, 
        .pagination .current {
            color: #007bff;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #f1f1f1;
        }

        .pagination .current {
            background-color: #007bff;
            color: white;
        }

        /* Responsive Adjustments */
        @media screen and (max-width: 768px) {
            .flex-container,
            .history-tables {
                flex-direction: column;
            }

            .client-data,
            .payment-section,
            .history-table {
                width: 100%;
                min-width: 100%;
            }

            .payment-input > div {
                min-width: 100%;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Mobile Specific Adjustments */
        @media screen and (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .form-row {
                flex-direction: column;
            }

            .form-row label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Avance de Cuenta</h2>
        </div>
        
        <div class="flex-container">
            <div class="client-data">
                <h3>Datos del Cliente</h3>
                <div class="form-row">
                    <label>ID:</label>
                    <div><?php echo $rowc['idc'] ?></div>
                </div>
                <div class="form-row">
                    <label>Nombre:</label>
                    <div><?php echo $rowc['nombrec'] ?></div>
                </div>
                <div class="form-row">
                    <label>Empresa:</label>
                    <div><?php echo $rowc['empresac'] ?></div>
                </div>
                <div class="form-row">
                    <label>Teléfono:</label>
                    <div><?php echo $rowc['telefonoc'] ?></div>
                </div>
                <div class="form-row">
                    <label>Límite de Crédito:</label>
                    <div><?php echo "RD$ " .$limitec ?></div>
                </div>
                <div class="form-row">
                    <label>Balance Disponible:</label>
                    <div><?php echo "RD$ " .$balancec ?></div>
                </div>
                <div class="form-row">
                    <label>Monto Total Adeudado:</label>
                    <div><?php echo "RD$ " .$adeudadoc ?></div>
                </div>
            </div>
            
            <div class="payment-section">
                <h3>Avance a Cuenta de Cliente</h3> <br>
                <div class="section">
                    <div class="payment-input">
                        <div style="flex: 1;">
                            <label>Método de Pago:</label>
                            <select id="forma-pago" name="forma-pago">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div id="num-tarjeta-div" style="display: none">
                            <label>Número de tarjeta:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" id="num-tarjeta" name="num-tarjeta" maxlength="4" placeholder="Ultimos 4 digitos" autocomplete="off">
                            </div>
                        </div>
                        <div id="num-auto-div" style="display: none;">
                            <label>Número de autorización:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" id="num-auto" name="num-auto" maxlength="4" placeholder="Ultimos 4 números" autocomplete="off">
                            </div>
                        </div>
                        <div id="banco-div" style="display: none;">
                            <label>Banco:</label>
                            <div style="display: flex; gap: 5px;">
                                <select name="banco" id="banco">
                                    <option value="1" disabled selected>Seleccionar</option>
                                    <?php
                                        $sql = "SELECT * FROM bancos WHERE id <> 1 ORDER BY id ASC";
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
                        </div>
                        <div id="destino-div" style="display: none;">
                            <label>Destino:</label>
                            <div style="display: flex; gap: 5px;">
                            <select name="destino" id="destino">
                                <option value="1" disabled selected>Seleccionar</option>
                                <?php
                                    $sql = "SELECT * FROM destinoCuentas WHERE id <> 1 ORDER BY id ASC";
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
                        </div>
                        <div style="flex: 1;" id="monto-div">
                            <label>Monto Pagado:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="numer" id="monto-pagado" name="monto-pagado" min="0" placeholder="Monto Pagado" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>Devuelta:</label>
                        <div id="devuelta" name="devuelta">RD$ .00</div>
                    </div>
                    
                    <div class="button-group">
                        <button class="btn btn-primary" onclick="procesarPago()">Procesar Pago</button>
                        <button class="btn btn-primary">Procesar e Imprimir</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="history-tables">
                <div class="history-table">
                    <h3>Historial de Pagos:</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php
                                    if($resultsph->num_rows > 0){
                                        while ($rowph = $resultsph->fetch_assoc()) {
                                            // FORMATO DE MONEDA
                                            $montoph = number_format($rowph['montoph'], 2, '.', ',');

                                            echo "
                                                <tr>
                                                    <td>{$rowph['fechaph']}</td>
                                                    <td>{$rowph['metodoph']}</td>
                                                    <td>RD$ {$montoph}</td>
                                                </tr>
                                            ";
                                        }
                                    } else {
                                        echo "<tr>
                                                <td colspan='3'>No se encontraron resultados.</td>
                                            </tr>";
                                    }
                                ?>

                            </tbody>
                        </table>
                    </div> <br>
                    <a class="btn" id="show-more-modal">Ver mas</a>
                </div>

                <div class="history-table">
                    <h3>Facturas Pendientes:</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Fact</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Pendiente</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php
                                    if ($resultsf->num_rows > 0) {
                                        while ($rowf = $resultsf->fetch_assoc()) {
                                            // FORMATO DE MONEDA
                                            $totalf = number_format($rowf['totalf'], 2, '.', ',');
                                            $balancef = number_format($rowf['balancef'], 2, '.', ',');

                                            echo "
                                                <tr>
                                                    <td>{$rowf['nf']}</td>
                                                    <td>{$rowf['fechaf']}</td>
                                                    <td>RD$ {$totalf}</td>
                                                    <td>RD$ {$balancef}</td>
                                                </tr>
                                            ";
                                        }
                                    } else {
                                        echo "<tr>
                                                <td colspan='4'>No se encontraron resultados.</td>
                                            </tr>";
                                    }
                                ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-history-payment">
        <div class="modal-content-history-payments">
            <span class="close-modal-history-payments">&times;</span>

            <?php 

                // Número de registros por página
                $registrosPorPagina = 10;

                // Página actual (por defecto 1)
                $paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

                // Calcular el offset
                $offset = ($paginaActual - 1) * $registrosPorPagina;

                // Consulta para obtener el total de registros
                $sqlTotal = "SELECT COUNT(*) AS total FROM clientes_historialpagos WHERE idCliente = ?";
                $stmtTotal = $conn->prepare($sqlTotal);
                $stmtTotal->bind_param("i", $idCliente);
                $stmtTotal->execute();
                $resultTotal = $stmtTotal->get_result();
                $totalRegistros = $resultTotal->fetch_assoc()['total'];

                // Calcular total de páginas
                $totalPaginas = ceil($totalRegistros / $registrosPorPagina);

                // Modificar la consulta original para incluir LIMIT y OFFSET
                $sql = "SELECT
                            DATE_FORMAT(chp.fecha, '%d/%m/%Y %l:%i %p') AS fechachp,
                            chp.metodo AS metodochp,
                            chp.monto AS montochp,
                            chp.numAutorizacion AS autorizacionchp,
                            chp.referencia AS tarjetachp,
                            b.nombreBanco AS bancochp,
                            d.descripcion AS destinochp,
                            CONCAT(e.nombre, ' ', e.apellido) AS nombree
                        FROM
                            clientes_historialpagos AS chp
                        JOIN bancos AS b ON chp.idBanco = b.id
                        JOIN destinocuentas AS d ON chp.idDestino = d.id
                        JOIN empleados AS e ON e.id = chp.idEmpleado
                        WHERE
                            chp.idCliente = ?
                        ORDER BY
                            chp.fecha DESC
                        LIMIT ? OFFSET ?;";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $idCliente, $registrosPorPagina, $offset);
                $stmt->execute();
                $result = $stmt->get_result();
            ?>

            <h3>Historial de Pagos</h3>
            <p>Pagos realizados por el cliente.</p>

            <div class="payments-history-table">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Monto</th>
                            <th>No. Autorización</th>
                            <th>No. Tarjeta</th>    
                            <th>Banco</th>
                            <th>Destino</th>
                            <th>Empleado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // FORMATO DE MONEDA
                                    $montochp = number_format($row['montochp'], 2, '.', ',');

                                    echo "
                                        <tr>
                                            <td>{$row['fechachp']}</td>
                                            <td>{$row['metodochp']}</td>
                                            <td>RD$ {$montochp}</td>
                                            <td>{$row['autorizacionchp']}</td>
                                            <td>{$row['tarjetachp']}</td>
                                            <td>{$row['bancochp']}</td>
                                            <td>{$row['destinochp']}</td>
                                            <td>{$row['nombree']}</td>
                                        </tr>
                                    ";
                                }
                            } else {
                                echo "<tr>
                                        <td colspan='8'>No se encontraron resultados.</td>
                                    </tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination">
                <?php
                // Botón Anterior
                if ($paginaActual > 1) {
                    echo "<a href='?idCliente=".($idCliente)."&pagina=" . ($paginaActual - 1) . "&modal=true" . "'>Anterior</a>";
                }

                // Números de página
                for ($i = 1; $i <= $totalPaginas; $i++) {
                    if ($i == $paginaActual) {
                        echo "<span class='current'> $i</span>";
                    } else {
                        echo "<a href='?idCliente=".($idCliente)."&pagina=$i&modal=true'>$i</a>";
                    }
                }

                // Botón Siguiente
                if ($paginaActual < $totalPaginas) {
                    echo "<a href='?idCliente=".($idCliente)."&pagina=" . ($paginaActual + 1) . "&modal=true" . "'> Siguiente</a>";
                }
                ?>
            </div>
        </div>
    </div>

    <?php
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if(isset($_GET['modal']) == "true") {
                echo "<script>document.querySelector('.modal-history-payment').style.display = 'flex';</script>";
            } else {
                echo "<script>document.querySelector('.modal-history-payment').style.display = 'none';</script>";
            }
        }
    ?>

    <script>

        // Variables globales
        let totalpagado = 0;
        let deuda = <?php echo $rowc['adeudadoc'] ?>;

        // Script para mostrar el modal de historial de pagos
        const openBtn = document.getElementById("show-more-modal");
        const openModal = document.querySelector('.modal-history-payment');

        openBtn.addEventListener('click', () => {
            openModal.style.display = 'flex';
        });

        // Script para cerrar el modal de historial de pagos
        const Closeodal = document.querySelector('.modal-history-payment');
        const closeBtn = document.querySelector('.close-modal-history-payments');

        closeBtn.addEventListener('click', () => {
            window.location.href = '?idCliente=<?php echo $idCliente ?>';
        });

        window.onclick = function(event) {
            if (event.target == Closeodal) {
                window.location.href = '?idCliente=<?php echo $idCliente ?>';
            }
        }

        function procesarPago() {
            let idCliente = <?php echo $idCliente ?>;
            let formaPago = document.getElementById("forma-pago").value;
            let numeroTarjeta = document.getElementById("num-tarjeta").value;
            let numeroAutorizacion = document.getElementById("num-auto").value;
            let banco = document.getElementById("banco").value;
            let destino = document.getElementById("destino").value;

            // Validacion de seleccion de cliente
            if (!idCliente) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'Ningun cliente fue encontrado seleccionado.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar campos con tarjeta
            if (formaPago == "tarjeta" && (!numeroTarjeta || !numeroAutorizacion || banco == "1" || destino  == "1")){
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'Por favor, complete todos los campos obligatorios.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar campos por transferencia
            if (formaPago == "transferencia" && (!numeroAutorizacion || banco == "1" || banco == "1")){
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación',
                    text: 'Por favor, complete todos los campos obligatorios.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar que el total pagado sea un número válido
            if (Number.isNaN(totalpagado) || totalpagado <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pago no válido',
                    text: 'No se ha registrado ningún pago',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            const datos = {
                idCliente,
                formaPago,
                montoPagado: totalpagado,
                numeroTarjeta: numeroTarjeta || null, 
                numeroAutorizacion: numeroAutorizacion || null, 
                banco: banco || null,
                destino: destino || null,
            };

            console.log("Enviando datos:", datos);

            fetch("php/cuentas_avance.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos)
            })
            .then(response => response.text())
            .then(text => {
                console.log("Respuesta completa del servidor:", text);
                try {
                    let data = JSON.parse(text);
                    if (data.success) {

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'Pago realizado exitosamente.',
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error,
                            showConfirmButton: true,
                            confirmButtonText: 'Aceptar'
                        });
                        console.error("Error al guardar la factura:", data.error);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Se produjo un error inesperado en el servidor. Pago no realizado.',
                        showConfirmButton: true,
                        confirmButtonText: 'Aceptar'
                    });
                    console.error("Error: Respuesta no es JSON válido:", text);
                }
            }).catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Se produjo un error de red o en el servidor.\nPor favor, inténtelo de nuevo.',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                });
                console.error("Error de red o servidor:", error);
            });
        }

        // Script para calcular la devuelta y el total ingresado
        document.getElementById("monto-pagado").addEventListener("keyup", () => {

            // Variables
            const montoPagado = parseFloat(document.getElementById("monto-pagado").value);
            let deuda = <?php echo $rowc['adeudadoc'] ?>;

            // Calcular total ingresado
            let totaldevuelta = montoPagado - deuda;

            // Mostrar total ingresado
            if (totaldevuelta < 0) {
                document.getElementById("devuelta").textContent = "RD$ .00";
                totalpagado = montoPagado;
            } else {
                document.getElementById("devuelta").textContent = "RD$ " + totaldevuelta.toFixed(2);
                totalpagado = montoPagado - totaldevuelta;
            }

        });

        // Script para mostrar u ocultar campos de información de pagos
        const metodo = document.getElementById("forma-pago");
        const tarjeta = document.getElementById("num-tarjeta-div");
        const autorizacion = document.getElementById("num-auto-div");
        const banco = document.getElementById("banco-div");
        const destino = document.getElementById("destino-div");
        
        metodo.addEventListener("change", () => {
            if (metodo.value === "tarjeta") {
                tarjeta.style.display = "flex";
                autorizacion.style.display = "flex";
                banco.style.display = "flex";
                destino.style.display = "flex";

                document.getElementById("monto-pagado").value = "";
                document.getElementById("banco").value = "1";
                document.getElementById("destino").value = "1";
                document.getElementById("num-tarjeta").value = "";
                document.getElementById("num-auto").value = "";

            } else if (metodo.value === "transferencia") {
                tarjeta.style.display = "none";
                autorizacion.style.display = "flex";
                banco.style.display = "flex";
                destino.style.display = "flex";

                document.getElementById("monto-pagado").value = "";
                document.getElementById("banco").value = "1";
                document.getElementById("destino").value = "1";
                document.getElementById("num-tarjeta").value = "";
                document.getElementById("num-auto").value = "";

            } else {
                tarjeta.style.display = "none";
                autorizacion.style.display = "none";
                banco.style.display = "none";
                destino.style.display = "none";

                document.getElementById("monto-pagado").value = "";
                document.getElementById("banco").value = "1";
                document.getElementById("destino").value = "1";
                document.getElementById("num-tarjeta").value = "";
                document.getElementById("num-auto").value = "";

            }
        });
    </script>

</body>
</html>