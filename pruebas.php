<?php

    include_once 'php/conexion.php';

    // Variables
    $idCliente = $_GET["idCliente"];

    // Tabla Payment History
    $sqlph = "SELECT DATE_FORMAT(chp.fecha, '%d/%m/%Y %l:%i %p') AS fechaph, chp.metodo AS metodoph , chp.monto AS montoph FROM clientes_historialpagos AS chp WHERE chp.idCliente = ? ORDER BY chp.fecha DESC LIMIT 5";
    $stmtph = $conn->prepare($sqlph);
    $stmtph->bind_param("i", $idCliente);
    $stmtph->execute();
    $resultsph = $stmtph->get_result();

    //  Tabla Facturas Pendientes
    $sqlf = "SELECT f.numFactura AS nf, DATE_FORMAT(f.fecha, '%d/%m/%Y %l:%i %p') AS fechaf,f.total_ajuste AS totalf,f.balance AS balancef FROM facturas AS f WHERE f.balance > 0 AND f.idCliente = ? ORDER BY f.fecha DESC";
    $stmtf = $conn->prepare($sqlf);
    $stmtf->bind_param("i", $idCliente);
    $stmtf->execute();
    $resultsf = $stmtf->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avance de Cuenta</title>
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
        .payment-method-list {
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        .payment-method-list table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-method-list th {
            background-color: #e6e9f0;
            text-align: left;
            padding: 8px;
        }
        .payment-method-list td {
            padding: 8px;
            border-bottom: 1px solid #eee;
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
        .footer {
            text-align: right;
            margin-top: 20px;
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
            justify-content: flex-end;
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
                    <div>000</div>
                </div>
                <div class="form-row">
                    <label>Nombre:</label>
                    <div>Nombre del Cliente</div>
                </div>
                <div class="form-row">
                    <label>Teléfono:</label>
                    <div>(000) 000 - 0000</div>
                </div>
                <div class="form-row">
                    <label>Límite de Crédito:</label>
                    <div>RD$ .00</div>
                </div>
                <div class="form-row">
                    <label>Balance Disponible:</label>
                    <div>RD$ .00</div>
                </div>
                <div class="form-row">
                    <label>Monto Total Adeudado:</label>
                    <div>RD$ .00</div>
                </div>
            </div>
            
            <div class="payment-section">
                <h3>Avance a Cuenta de Cliente</h3>
                <div class="section">
                    <h4>Ingresar Avance a Cuenta</h4>
                    <div class="payment-input">
                        <div style="flex: 1;">
                            <label>Método de Pago:</label>
                            <select>
                                <option>Efectivo</option>
                                <option>Transferencia</option>
                                <option>Tarjeta</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label>Monto Ingresado:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="text">
                                <button class="btn">⁞</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>Total Ingresado:</label>
                        <div>RD$ .00</div>
                    </div>
                    <div class="form-row">
                        <label>Devuelta:</label>
                        <div>RD$ .00</div>
                    </div>
                    <div class="payment-method-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th>Monto Pagado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Efectivo</td>
                                    <td>.00</td>
                                </tr>
                                <tr>
                                    <td>Transferencia</td>
                                    <td>.00</td>
                                </tr>
                                <tr>
                                    <td>Tarjeta</td>
                                    <td>.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="button-group">
                        <button class="btn btn-primary">Procesar Pago</button>
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
                    </div>
                </div>
                
                <div class="history-table">
                    <h3>Facturas Pendientes:</h3>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha y Hora</th>
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
        
        <div class="footer">
            <button class="btn">Cerrar</button>
        </div>
    </div>
    
    <script>
        
    </script>
</body>
</html>