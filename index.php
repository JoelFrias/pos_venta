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

/* Fin de verificacion de sesion */

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>YSAPELLI</title>
    <link rel="icon" type="image/png" href="img/logo-blanco.png">
    <link rel="stylesheet" href="css/menu.css"> <!-- CSS menu -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> <!-- Librería de iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Librería para gráficos -->
    <style>
        /* General styles */
        :root {
            --primary-colors: #4a6fa5;
            --secondary-colors: #6c757d;
            --dark-colors: #212529;
            --border-radiuss: 8px;
            --box-shadows: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9fafb;
            color: #333;
        }

        /* Welcome section con botón */
        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }

        .welcome h1 {
            font-size: 1.8rem;
            font-weight: 500;
            color: var(--primary-colors);
            margin: 0;
        }

        #btn-edit-profile {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: var(--primary-colors);
            font-weight: 500;
        }

        #btn-edit-profile:hover {
            background-color: #f5f5f5;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #btn-edit-profile:active {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments para el botón */
        @media (max-width: 992px) {
            .welcome {
                flex-direction: column;
                align-items: flex-start;
            }
            
            #btn-edit-profile {
                margin-top: 15px;
                align-self: flex-end;
            }
        }

        /* Headings */
        .page-content h2 {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--dark-colors);
            margin-top: 0;
            margin-bottom: 20px;
        }

        /* Filters section */
        #filters {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: var(--border-radiuss);
            margin-bottom: 25px;
        }

        #filters label {
            margin-right: 10px;
            font-weight: 500;
            color: var(--secondary-colors);
        }

        #filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: white;
            margin-right: 15px;
            font-size: 0.9rem;
            min-width: 150px;
        }

        #btn-filters {
            background-color: var(--primary-colors);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        #btn-filters:hover {
            background-color: #3a5885;
        }

        #btn-filters i {
            margin-right: 5px;
        }

        /* Graphics containers */
        .graphics {
            margin-top: 20px;
        }

        .containers {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-md-6 {
            flex: 0 0 calc(50% - 30px);
            max-width: calc(50% - 30px);
            padding-right: 15px;
            padding-left: 15px;
            margin-bottom: 30px;
            position: relative;
        }

        /* Canvas containerss for charts */
        canvas {
            background-color: white;
            border-radius: var(--border-radiuss);
            box-shadow: var(--box-shadows);
            padding: 15px;
            width: 100% !important;
            height: 300px !important;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            #filters {
                flex-direction: column;
                align-items: flex-start;
            }
            
            #filters select {
                margin-bottom: 10px;
                width: 100%;
            }
            
            #btn-filters {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    
    <div class="navegator-nav">

        <!-- Menu-->
        <?php include 'menu.php'; ?>

        <div class="page-content">
        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR DEBAJO DE ESTA LINEA -->

            <!-- Switch para modo oscuro 
            <label class="switch">
                <input id="toggleDarkMode" type="checkbox" />
                <span class="slider"></span>
            </label>
            -->

            <!-- Mensaje de bienvenida -->
            <div class="welcome">
                <h1 id="mensaje"></h1>
                <button id="btn-edit-profile">Editar Perfil</button>
            </div>

            <h2>Dashboard de Estadísticas Personal</h2>
            
            <!-- filters -->
            <div id="filters">
                <label for="months">Periodo:</label>
                <select name="months" id="months">
                    <option value="current" <?php echo (isset($_GET['periodo']) && $_GET['periodo'] == 'current') ? 'selected' : ''; ?>>Mes Actual</option>
                    <option value="previous" <?php echo (isset($_GET['periodo']) && $_GET['periodo'] == 'previous') ? 'selected' : ''; ?>>Mes Anterior</option>
                </select>

                <button id="btn-filters" name="btn-filters" onclick="recargar()"><i class="fa-solid fa-magnifying-glass"></i> Aplicar</button>
            </div>

            <!-- graphics -->
            <div class="graphics">
                <div class="containers">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="ventas"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="no-ventas"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="clientes-populares"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="mas-vendidos"></canvas>
                        </div>
                    </div>
                </div>
            </div>


        <!-- TODO EL CONTENIDO DE LA PAGINA DEBE DE ESTAR ENCIMA DE ESTA LINEA -->
        </div>
    </div>

    <!-- graphics -->
    <script>
        function cargarVentasPorDia(periodo) {
            fetch(`graphics/index/no-ventas.php?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    const dias = data.map(item => item.dia);
                    const ventas = data.map(item => item.cantidad_ventas);

                    const ctx = document.getElementById('ventas').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: dias,
                            datasets: [{
                                label: 'Número de Ventas',
                                data: ventas,
                                backgroundColor: 'rgba(54, 162, 235, 0.92)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Número de Ventas por Día',
                                    font: {
                                        size: 16
                                    },
                                    padding: {
                                        top: 10,
                                        bottom: 20
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error detallado:', error);
                    // Opcional: Muestra un indicador visual en el gráfico
                    const ctx = document.getElementById('ventas').getContext('2d');
                    ctx.font = '14px Arial';
                    ctx.fillText('Error al cargar datos', 10, 20);
                });
        }

        // Función para obtener los datos del total de ventas por día
        function cargarTotalVentasPorDia(periodo) {
            fetch(`graphics/index/total-ventas.php?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    const dias = data.map(item => item.dia);
                    const ventas = data.map(item => item.total_ventas);

                    const ctx = document.getElementById('no-ventas').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dias,
                            datasets: [{
                                label: 'Total de Ventas ($)',
                                data: ventas,
                                backgroundColor: 'rgba(46, 204, 113, 0.2)',
                                borderColor: 'rgba(39, 174, 96, 1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Total de Ventas ($) por Día',
                                    font: {
                                        size: 16
                                    },
                                    padding: {
                                        top: 10,
                                        bottom: 20
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error detallado:', error);
                    // Opcional: Muestra un indicador visual en el gráfico
                    const ctx = document.getElementById('no-ventas').getContext('2d');
                    ctx.font = '14px Arial';
                    ctx.fillText('Error al cargar datos', 10, 20);
                });
        }

        // Función para obtener los datos de clientes más populares (por cantidad de compras)
        function cargarClientesPopulares(periodo) {
            fetch(`graphics/index/clientes-popular.php?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    const clientes = data.map(item => item.nombre_cliente);
                    const compras = data.map(item => item.ventas);

                    const ctx = document.getElementById('clientes-populares').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: clientes,
                            datasets: [{
                                label: 'Compras por Cliente',
                                data: compras,
                                backgroundColor: 'rgba(255, 99, 132, 0.92)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Clientes Más Frecuentes',
                                    font: {
                                        size: 16
                                    },
                                    padding: {
                                        top: 10,
                                        bottom: 20
                                    }
                                },
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error detallado:', error);
                    // Opcional: Muestra un indicador visual en el gráfico
                    const ctx = document.getElementById('clientes-populares').getContext('2d');
                    ctx.font = '14px Arial';
                    ctx.fillText('Error al cargar datos', 10, 20);
                });
        }

        // Función para obtener los datos de productos más vendidos
        function cargarProductosMasVendidos(periodo) {
            fetch(`graphics/index/mas-vendidos.php?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    const productos = data.map(item => item.descripcion);
                    const cantidades = data.map(item => item.cantidad_vendida);

                    const ctx = document.getElementById('mas-vendidos').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: productos,
                            datasets: [{
                                label: 'Cantidad Vendida',
                                data: cantidades,
                                backgroundColor: [
                                    'rgba(255, 183, 77, 0.7)',  // Naranja suave pero vibrante
                                    'rgba(129, 199, 132, 0.7)', // Verde menta elegante
                                    'rgba(100, 181, 246, 0.7)', // Azul cielo armónico
                                    'rgba(244, 143, 177, 0.7)', // Rosa coral sutil
                                    'rgba(77, 182, 172, 0.7)',  // Verde azulado moderno
                                    'rgba(171, 71, 188, 0.7)'   // Morado pastel sofisticado
                                ],
                                borderColor: [
                                    'rgba(255, 183, 77, 1)',
                                    'rgba(129, 199, 132, 1)',
                                    'rgba(100, 181, 246, 1)',
                                    'rgba(244, 143, 177, 1)',
                                    'rgba(77, 182, 172, 1)',
                                    'rgba(171, 71, 188, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Productos Más Vendidos',
                                    font: {
                                        size: 16
                                    },
                                    padding: {
                                        top: 10,
                                        bottom: 20
                                    }
                                },
                                legend: {
                                    display: true
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error detallado:', error);
                    // Opcional: Muestra un indicador visual en el gráfico
                    const ctx = document.getElementById('mas-vendidos').getContext('2d');
                    ctx.font = '14px Arial';
                    ctx.fillText('Error al cargar datos', 10, 20);
                });
        }

        // Función para inicializar los gráficos con el periodo (actual o anterior)
        function cargarGraficos(periodo = 'current') {
            cargarVentasPorDia(periodo);
            cargarTotalVentasPorDia(periodo);
            cargarClientesPopulares(periodo);
            cargarProductosMasVendidos(periodo);
        }

        // Función para recargar la página con el filtro seleccionado
        function recargar() {
            const periodo = document.getElementById('months').value;
            window.location.href = `index.php?periodo=${periodo}`;
        }

        // Llamar la función de inicialización (por defecto con el periodo actual)
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const periodo = urlParams.get('periodo') || 'current';
            document.getElementById('months').value = periodo;
            cargarGraficos(periodo);
        });
    </script>

    <!-- Script de mensaje de bienvenida -->
    <script>
        const mensaje = document.getElementById('mensaje');
        const hora = new Date().getHours();

        if (hora >= 6 && hora < 12) {
            mensaje.textContent = "Buenos días <?php echo $_SESSION['nombre']; ?>.";
        } else if (hora >= 12 && hora < 18) {
            mensaje.textContent = "Buenas tardes <?php echo $_SESSION['nombre']; ?>.";
        } else {
            mensaje.textContent = "Buenas noches <?php echo $_SESSION['nombre']; ?>.";
        }
    </script>
    
</body>
</html>