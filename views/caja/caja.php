<?php
    /* Verificacion de sesion */
    session_start();

    // Configurar el tiempo de caducidad de la sesión
    $inactivity_limit = 900; // 15 minutos en segundos

    // Verificar si el usuario ha iniciado sesión
    if (!isset($_SESSION['username'])) {
        session_unset();
        session_destroy();
        header('Location: ../../views/auth/login.php.php');
        exit();
    }

    // Verificar si la sesión ha expirado por inactividad
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
        session_unset();
        session_destroy();
        header("Location: ../../views/auth/login.php?session_expired=session_expired");
        exit();
    }

    // Actualizar el tiempo de la última actividad
    $_SESSION['last_activity'] = time();

    /* Fin de verificacion de sesion */

    // Configuración de la zona horaria
    date_default_timezone_set('America/Santo_Domingo');

    // Conexión a la base de datos
    require_once '../../models/conexion.php';

    // Variables
    $mensaje;
    $id_empleado = $_SESSION['idEmpleado'];
    $nombre_empleado = $_SESSION['nombre'];

    // Verificar si el empleado tiene una caja abierta 
    $sql_verificar = "SELECT
                        numCaja,
                        idEmpleado,
                        fechaApertura AS fechaApertura,
                        saldoApertura,
                        registro
                    FROM
                        cajasabiertas
                    WHERE
                        idEmpleado = ?";

    $stmt = $conn->prepare($sql_verificar);
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $caja_abierta = false;
    $datos_caja = null;

    if ($resultado->num_rows > 0) {
        $caja_abierta = true;
        $datos_caja = $resultado->fetch_assoc();

        // Almacenar datos de la caja abierta - aseguramos que numCaja sea string
        $_SESSION['numCaja'] = $datos_caja['numCaja'];
        $_SESSION['fechaApertura'] = $datos_caja['fechaApertura'];
        $_SESSION['saldoApertura'] = $datos_caja['saldoApertura'];
        $_SESSION['registro'] = $datos_caja['registro'];
    }
    $stmt->close();

    // Consultar totales para caja actual (si está abierta)
    $total_ingresos = 0;
    $total_egresos = 0;

    if ($caja_abierta) {

        $num_caja = $datos_caja['numCaja'];
        
        // Calcular total de ingresos
        $sql_ingresos = "SELECT SUM(monto) as total FROM cajaingresos WHERE metodo = 'efectivo' AND numCaja = ?";
        $stmt = $conn->prepare($sql_ingresos);
        $stmt->bind_param("s", $num_caja);
        $stmt->execute();
        $result_ingresos = $stmt->get_result();
        if ($result_ingresos->num_rows > 0) {
            $row_ingresos = $result_ingresos->fetch_assoc();
            $total_ingresos = $row_ingresos['total'] ? $row_ingresos['total'] : 0;
        }
        $stmt->close();
        
        // Calcular total de egresos
        $sql_egresos = "SELECT SUM(monto) as total FROM cajaegresos WHERE metodo = 'efectivo' AND numCaja = ?";
        $stmt = $conn->prepare($sql_egresos);
        $stmt->bind_param("s", $num_caja);
        $stmt->execute();
        $result_egresos = $stmt->get_result();
        if ($result_egresos->num_rows > 0) {
            $row_egresos = $result_egresos->fetch_assoc();
            $total_egresos = $row_egresos['total'] ? $row_egresos['total'] : 0;
        }
        $stmt->close();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Abrir caja con transacción
        if (isset($_POST['abrir_caja']) && !$caja_abierta) {

            $saldo_apertura = filter_input(INPUT_POST, 'saldo_apertura', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            if ($saldo_apertura === false || $saldo_apertura < 0) {
                $mensaje = "Error: Saldo inicial no válido";
            } else {
                $conn->autocommit(FALSE); // Iniciar transacción
                $error = false;
                
                try {
                    // Obtener y bloquear el contador
                    $sql_contador = "SELECT contador FROM cajacontador LIMIT 1 FOR UPDATE";
                    $stmt = $conn->prepare($sql_contador);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al obtener número de caja");
                    }
                    
                    $result_contador = $stmt->get_result();
                    $contador_row = $result_contador->fetch_assoc();
                    
                    // Asegurar que el contador sea un entero
                    $contador_num = intval($contador_row['contador']);
                    
                    // Formatear el contador con ceros a la izquierda (5 dígitos)
                    $num_caja = str_pad($contador_num, 5, '0', STR_PAD_LEFT);
                    
                    // Incrementar contador para el próximo uso
                    $nuevo_contador = $contador_num + 1;
                    
                    // Actualizar el contador
                    $sql_update_contador = "UPDATE cajacontador SET contador = ?";
                    $stmt = $conn->prepare($sql_update_contador);
                    $stmt->bind_param("i", $nuevo_contador);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al actualizar contador");
                    }
                    
                    // Insertar caja abierta - usar num_caja como string formateado
                    $sql = "INSERT INTO cajasabiertas (numCaja, idEmpleado, fechaApertura, saldoApertura) 
                            VALUES (?, ?, NOW(), ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sid", $num_caja, $id_empleado, $saldo_apertura);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al abrir caja en sistema");
                    }

                    /**
                     *  2. Auditoria de acciones de usuario
                     */

                    require_once '../../models/auditorias.php';
                    $usuario_id = $_SESSION['idEmpleado'];
                    $accion = 'APERTURA_CAJA';
                    $detalle = 'Caja abierta con saldo inicial: ' . $saldo_apertura . ' y número de caja: ' . $num_caja;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                    registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);
                    
                    $conn->commit();
                    $mensaje = "Caja abierta exitosamente";
                    $caja_abierta = true;
                    
                    // Refrescar datos
                    $sql_verificar = "SELECT * FROM cajasabiertas WHERE idEmpleado = ?";
                    $stmt = $conn->prepare($sql_verificar);
                    $stmt->bind_param("i", $id_empleado);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $datos_caja = $resultado->fetch_assoc();

                    // Almacenar datos de la caja abierta
                    $_SESSION['numCaja'] = $num_caja;
                    $_SESSION['fechaApertura'] = $datos_caja['fechaApertura'];
                    $_SESSION['saldoApertura'] = $datos_caja['saldoApertura'];
                    $_SESSION['registro'] = $datos_caja['registro'];
                    
                    // Registrar auditoría
                    registrarAuditoriaCaja($conn, $id_empleado, 'APERTURA_CAJA', 
                        "Caja #$num_caja abierta con saldo inicial: $saldo_apertura");
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "Error en transacción: " . $e->getMessage();
                    $error = true;
                }
                
                $conn->autocommit(TRUE);
                if ($error) {
                    registrarAuditoriaCaja($conn, $id_empleado, 'ERROR_APERTURA', 
                        "Fallo al abrir caja: " . $mensaje);
                }
            }

        }
        
        // Cerrar caja con transacción
        if (isset($_POST['cerrar_caja']) && $caja_abierta) {
            $saldo_final = filter_input(INPUT_POST, 'saldo_final', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $num_caja = filter_input(INPUT_POST, 'num_caja', FILTER_SANITIZE_STRING);
            $registro = filter_input(INPUT_POST, 'registro', FILTER_SANITIZE_NUMBER_INT);
            $fecha_apertura = filter_input(INPUT_POST, 'fecha_apertura', FILTER_SANITIZE_STRING);
            $saldo_inicial = filter_input(INPUT_POST, 'saldo_inicial', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            if ($saldo_final === false || $saldo_final < 0) {
                $mensaje = "Error: Saldo final no válido";
            } else {
                $conn->autocommit(FALSE); // Iniciar transacción
                $error = false;
                
                try {
                    // Calcular diferencia
                    $diferencia = $saldo_final - ($saldo_inicial + $total_ingresos - $total_egresos);
                    
                    // Insertar en cajas cerradas
                    $sql = "INSERT INTO cajascerradas (numCaja, idEmpleado, fechaApertura, fechaCierre, saldoInicial, saldoFinal, diferencia) 
                            VALUES (?, ?, ?, NOW(), ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sisddd", $num_caja, $id_empleado, $fecha_apertura, $saldo_inicial, $saldo_final, $diferencia);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar cierre de caja");
                    }
                    
                    // Eliminar de cajas abiertas
                    $sql_delete = "DELETE FROM cajasabiertas WHERE registro = ?";
                    $stmt = $conn->prepare($sql_delete);
                    $stmt->bind_param("i", $registro);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al eliminar caja abierta");
                    }

                    /**
                     *  2. Auditoria de acciones de usuario
                     */

                    require_once '../../models/auditorias.php';
                    $usuario_id = $_SESSION['idEmpleado'];
                    $accion = 'CIERRE_CAJA';
                    $detalle = 'Caja cerrada con saldo final: ' . $saldo_final . ' y número de caja: ' . $num_caja;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                    registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);
                    
                    $conn->commit();
                    $mensaje = "Caja cerrada exitosamente";
                    $caja_abierta = false;

                    // Registrar auditoría
                    registrarAuditoriaCaja($conn, $id_empleado, 'CIERRE_CAJA', 
                        "Caja #$num_caja cerrada. Saldo final: $saldo_final, Diferencia: $diferencia");
                    
                    // Limpiar variables de caja
                    unset($_SESSION['numCaja']);
                    unset($_SESSION['fechaApertura']);
                    unset($_SESSION['saldoApertura']);
                    unset($_SESSION['registro']);
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "Error en transacción de cierre: " . $e->getMessage();
                    $error = true;
                }
                
                $conn->autocommit(TRUE);
                if ($error) {
                    registrarAuditoriaCaja($conn, $id_empleado, 'ERROR_CIERRE', 
                        "Fallo al cerrar caja #$num_caja: " . $mensaje);
                }
            }

        }
        
        // Registrar ingreso con transacción
        if (isset($_POST['registrar_ingreso']) && $caja_abierta) {
            $monto = filter_input(INPUT_POST, 'monto_ingreso', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $metodo = $_POST['metodo_ingreso'];
            $metodo = filter_var($metodo, FILTER_SANITIZE_STRING);
            $razon = filter_input(INPUT_POST, 'razon_ingreso', FILTER_SANITIZE_STRING);
            $num_caja = filter_input(INPUT_POST, 'num_caja', FILTER_SANITIZE_STRING);
            
            if ($monto === false || $monto <= 0) {
                $mensaje = "Error: Monto no válido";
            } elseif (empty($razon)) {
                $mensaje = "Error: Razón no puede estar vacía";
            } else {
                $conn->autocommit(FALSE);
                $error = false;
                
                try {
                    // Insertar ingreso
                    $sql = "INSERT INTO cajaingresos (metodo, monto, IdEmpleado, numCaja, razon, fecha) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sdiss", $metodo, $monto, $id_empleado, $num_caja, $razon);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar ingreso");
                    }
                    
                    // Actualizar total de ingresos
                    $sql_ingresos = "SELECT SUM(monto) as total FROM cajaingresos WHERE numCaja = ?";
                    $stmt = $conn->prepare($sql_ingresos);
                    $stmt->bind_param("s", $num_caja);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al actualizar total de ingresos");
                    }
                    
                    $result_ingresos = $stmt->get_result();
                    if ($result_ingresos->num_rows > 0) {
                        $row_ingresos = $result_ingresos->fetch_assoc();
                        $total_ingresos = $row_ingresos['total'] ? $row_ingresos['total'] : 0;
                    }

                    /**
                     *  2. Auditoria de acciones de usuario
                     */

                    require_once '../../models/auditorias.php';
                    $usuario_id = $_SESSION['idEmpleado'];
                    $accion = 'INGRESO_CAJA';
                    $detalle = 'Ingreso registrado con monto: ' . $monto . ' y número de caja: ' . $num_caja . ' Razón: ' . $razon;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                    registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);
                    
                    $conn->commit();
                    $mensaje = "Ingreso registrado exitosamente";
                    
                    // Registrar auditoría
                    registrarAuditoriaCaja($conn, $id_empleado, 'INGRESO', 
                        "Monto: $monto, Razón: $razon, Caja: $num_caja");
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "Error en transacción de ingreso: " . $e->getMessage();
                    $error = true;
                }
                
                $conn->autocommit(TRUE);
                if ($error) {
                    registrarAuditoriaCaja($conn, $id_empleado, 'ERROR_INGRESO', 
                        "Fallo al registrar ingreso: " . $mensaje);
                }
            }


        }
        
        // Registrar egreso con transacción
        if (isset($_POST['registrar_egreso']) && $caja_abierta) {
            $monto = filter_input(INPUT_POST, 'monto_egreso', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $metodo = $_POST['metodo_egreso'];
            $razon = filter_input(INPUT_POST, 'razon_egreso', FILTER_SANITIZE_STRING);
            $num_caja = filter_input(INPUT_POST, 'num_caja', FILTER_SANITIZE_STRING);
            
            if ($monto === false || $monto <= 0) {
                $mensaje = "Error: Monto no válido";
            } elseif (empty($razon)) {
                $mensaje = "Error: Razón no puede estar vacía";
            } else {
                $conn->autocommit(FALSE);
                $error = false;
                
                try {
                    // Insertar egreso
                    $sql = "INSERT INTO cajaegresos (metodo, monto, IdEmpleado, numCaja, razon, fecha) 
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sdiss", $metodo, $monto, $id_empleado, $num_caja, $razon);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar egreso");
                    }
                    
                    // Actualizar total de egresos
                    $sql_egresos = "SELECT SUM(monto) as total FROM cajaegresos WHERE numCaja = ?";
                    $stmt = $conn->prepare($sql_egresos);
                    $stmt->bind_param("s", $num_caja);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al actualizar total de egresos");
                    }
                    
                    $result_egresos = $stmt->get_result();
                    if ($result_egresos->num_rows > 0) {
                        $row_egresos = $result_egresos->fetch_assoc();
                        $total_egresos = $row_egresos['total'] ? $row_egresos['total'] : 0;
                    }

                    /**
                     *  2. Auditoria de acciones de usuario
                     */

                    require_once '../../models/auditorias.php';
                    $usuario_id = $_SESSION['idEmpleado'];
                    $accion = 'EGRESO_CAJA';
                    $detalle = 'Egreso registrado con monto: ' . $monto . ' y número de caja: ' . $num_caja . ' Razón: ' . $razon;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
                    registrarAuditoriaUsuarios($conn, $usuario_id, $accion, $detalle, $ip);
                    
                    // Registrar auditoría
                    registrarAuditoriaCaja($conn, $id_empleado, 'EGRESO', 
                        "Monto: $monto, Razón: $razon, Caja: $num_caja");

                    $conn->commit();
                    $mensaje = "Egreso registrado exitosamente";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = "Error en transacción de egreso: " . $e->getMessage();
                    $error = true;
                }
                
                $conn->autocommit(TRUE);
                if ($error) {
                    registrarAuditoriaCaja($conn, $id_empleado, 'ERROR_EGRESO', 
                        "Fallo al registrar egreso: " . $mensaje);
                }
            }
            
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Sistema de Caja</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-blanco.png">
    <link rel="stylesheet" href="../../assets/css/menu.css"> <!-- CSS menu -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Libreria de alertas -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Librería de iconos -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            width: 100%;
            margin: 0 auto;
            padding: 10px;
            background-color: #f5f6fa;
            font-size: 16px;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }
        
        .container .mensaje {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 5px solid #28a745;
            word-wrap: break-word;
        }
        
        .container .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .container .panel {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            width: 100%;
        }
        
        .container h1 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .container h2 {
            font-size: 1.4rem;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .container input[type="number"], 
        .container input[type="text"], 
        .container select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .container button, 
        .container input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-bottom: 5px;
        }
        
        .container button:hover, 
        .container input[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .container .info-caja {
            margin-top: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #e7f3fe;
            border-left: 5px solid #2196F3;
            border-radius: 5px;
        }
        
        .container .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            width: 100%;
        }
        
        .container .resumen {
            margin: 15px 0;
            font-size: 1rem;
        }
        
        .container .resumen p {
            margin: 8px 0;
        }
        
        .container .header {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }
        
        .container .empleado-info {
            margin-top: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .container hr {
            margin: 15px 0;
            border: 0;
            border-top: 1px solid #eee;
        }
        
        /* Media queries para hacer responsive */
        @media screen and (min-width: 768px) {
            body {
                padding: 20px;
            }
            
            .container .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .container .empleado-info {
                text-align: right;
            }
            
            .container .grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .container button, 
            .container input[type="submit"] {
                width: auto;
                padding: 10px 20px;
            }
            
            .container h1 {
                font-size: 2rem;
            }
            
            .container h2 {
                font-size: 1.5rem;
            }
            
            .container .panel {
                padding: 20px;
                margin-bottom: 20px;
            }
        }
        
        /* Para pantallas más grandes */
        @media screen and (min-width: 1024px) {
            .container {
                padding: 0;
            }
            
            .container .resumen {
                font-size: 1.1rem;
            }
        }
    </style>
    
</head>
<body>

    <?php
        if (isset($mensaje)) {
            $icon = strpos($mensaje, 'Error') !== false ? 'error' : 'success';
            $title = strpos($mensaje, 'Error') !== false ? 'ERROR' : 'Éxito';

            echo "
            <script>
                Swal.fire({
                    icon: '$icon',
                    title: '$title',
                    text: '$mensaje',
                    showConfirmButton: true,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = 'caja.php';
                });
            </script>
            ";
        }
    ?>


    <div class="navegator-nav">

        <!-- Menu-->
        <?php include '../../views/layouts/menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->
            <div class="container">
                <div class="header">
                    <h1>Sistema de Caja</h1>
                    <div class="empleado-info">
                        <p><strong>ID Empleado:</strong> <?php echo $id_empleado; ?></p>
                        <p><strong>Empleado:</strong> <?php echo $nombre_empleado; ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('j/n/Y h:i A'); ?></p>
                    </div>
                </div>
                
                <?php if(!$caja_abierta): ?>
                    <!-- Panel para abrir caja -->
                    <div class="panel">
                        <h2>Abrir Caja</h2>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <label for="saldo_apertura">Saldo Inicial:</label>
                            <input type="number" id="saldo_apertura" name="saldo_apertura" step="0.01" min="0" required>
                            <button type="submit" name="abrir_caja">Abrir Caja</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Información de caja abierta -->
                    <div class="info-caja">
                        <h2>Usted presenta una caja abierta</h2>
                        <p><strong>Fecha de apertura:</strong> <?php echo date('j/n/Y h:i A', strtotime($datos_caja['fechaApertura'])); ?></p>
                        <p><strong>Saldo inicial:</strong> $<?php echo number_format($datos_caja['saldoApertura'], 2); ?></p>
                    </div>
                    
                    <!-- Grid para ingresos y egresos -->
                    <div class="grid">
                        <!-- Panel para registrar ingresos -->
                        <div class="panel">
                            <h2>Registrar Ingreso</h2>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <label for="monto_ingreso">Monto:</label>
                                <input type="number" id="monto_ingreso" name="monto_ingreso" step="0.01" min="1" required>
                                
                                <label for="metodo_ingreso">Método de pago:</label>
                                <select id="metodo_ingreso" name="metodo_ingreso" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>

                                <label for="razon_ingreso">Razón:</label>
                                <input type="text" id="razon_ingreso" name="razon_ingreso" required>
                                
                                <input type="hidden" name="num_caja" value="<?php echo $_SESSION['numCaja']; ?>">
                                <button type="submit" name="registrar_ingreso">Registrar Ingreso</button>
                            </form>
                        </div>
                        
                        <!-- Panel para registrar egresos -->
                        <div class="panel">
                            <h2>Registrar Egreso</h2>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <label for="monto_egreso">Monto:</label>
                                <input type="number" id="monto_egreso" name="monto_egreso" step="0.01" min="1" required>

                                <label for="metodo_egreso">Método de pago:</label>
                                <select id="metodo_egreso" name="metodo_egreso" required>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                                
                                <label for="razon_egreso">Razón:</label>
                                <input type="text" id="razon_egreso" name="razon_egreso" required>
                                
                                <input type="hidden" name="num_caja" value="<?php echo $datos_caja['numCaja']; ?>">
                                <button type="submit" name="registrar_egreso">Registrar Egreso</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Resumen de caja y cierre -->
                    <div class="panel">
                        <h2>Resumen de Caja</h2>
                        <div class="resumen">
                            <p><strong>Saldo inicial:</strong> $<?php echo number_format($datos_caja['saldoApertura'], 2); ?></p>
                            <p><strong>Total ingresos (Efectivo):</strong> $<?php echo number_format($total_ingresos, 2); ?></p>
                            <p><strong>Total egresos (Efectivo):   </strong> $<?php echo number_format($total_egresos, 2); ?></p>
                            <p><strong>Saldo esperado:</strong> $<?php echo number_format($datos_caja['saldoApertura'] + $total_ingresos - $total_egresos, 2); ?></p>
                        </div>
                        
                        <hr>
                        
                        <h2>Cerrar Caja</h2>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <label for="saldo_final">Saldo Final (conteo físico):</label>
                            <input type="number" id="saldo_final" name="saldo_final" step="0.01" min="0" required>
                            
                            <input type="hidden" name="num_caja" value="<?php echo $datos_caja['numCaja']; ?>">
                            <input type="hidden" name="registro" value="<?php echo $datos_caja['registro']; ?>">
                            <input type="hidden" name="fecha_apertura" value="<?php echo $_SESSION['fechaApertura']; ?>">
                            <input type="hidden" name="saldo_inicial" value="<?php echo $_SESSION['saldoApertura']; ?>">
                            
                            <button type="submit" name="cerrar_caja">Cerrar Caja</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR ARRIBA DE ESTA LINEA -->
        </div>
    </div>

</body>
</html>