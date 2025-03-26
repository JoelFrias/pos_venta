<?php

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section {
            margin-bottom: 20px;
        }
        .flex-container {
            display: flex;
            gap: 20px;
        }
        .client-data {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .payment-section {
            flex: 2;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .payment-input {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }
        .payment-summary {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
        }
        .history-tables {
            display: flex;
            gap: 20px;
        }
        .history-table {
            flex: 1;
        }
        .history-table h3 {
            margin-top: 0;
        }
        .history-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .history-table th {
            background-color: #e6e9f0;
            text-align: left;
            padding: 8px;
        }
        .history-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .table-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        .btn {
            background-color: #e6e9f0;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #d8dce6;
        }
        .btn-primary {
            background-color: #e6e9f0;
            font-weight: bold;
        }
        .form-row {
            margin-bottom: 10px;
        }
        .form-row label {
            display: block;
            font-weight: bold;
            margin-bottom: 3px;
        }
        select, input {
            padding: 6px;
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .note {
            font-size: 12px;
            color: #666;
        }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
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
                <h3>Avance a Cuenta de Cliente</h3>
                <div class="section">
                    <h4>Ingresar Avance a Cuenta</h4>
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
                                <input type="number" id="num-tarjeta" name="num-tarjeta" min="0" max="9999" minlength="4" maxlength="4" placeholder="Ultimos 4 digitos">
                            </div>
                        </div>
                        <div id="num-auto-div" style="display: none;">
                            <label>Número de autorización:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" id="num-auto" name="num-auto" min="0" max="9999" minlength="4" maxlength="4" placeholder="Ultimos 4 números">
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
                    <a class="btn">Ver mas</a>
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
    
    <script>

        // Variables globales
        let totalpagado = 0;
        let deuda = <?php echo $rowc['adeudadoc'] ?>;

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