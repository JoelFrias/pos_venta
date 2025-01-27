<?php
session_start();
require 'php/conexion.php';

// Inicializar variables de búsqueda
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : "";

// Construir la consulta SQL con filtros de búsqueda
$query = "SELECT
            c.id,
            CONCAT(c.nombre, ' ', c.apellido) AS nombreCompleto,
            c.empresa,
            c.tipo_identificacion,
            c.identificacion,
            c.telefono,
            c.notas,
            cc.limite_credito,
            cc.balance,
            CONCAT(
                '#',
                cd.no,
                ', ',
                cd.calle,
                ', ',
                cd.sector,
                ', ',
                cd.ciudad,
                ', (Referencia: ',
                IFNULL(cd.referencia, 'Sin referencia'),
                ')'
            ) AS direccion,
            c.activo
        FROM
            clientes AS c
        LEFT JOIN clientes_cuenta AS cc
        ON
            c.id = cc.id
        LEFT JOIN clientes_direcciones AS cd
        ON
            c.id = cd.id
        WHERE
            1=1
        ";

if (!empty($search)) {
    $query .= " AND CONCAT(c.nombre,c.apellido,c.empresa) LIKE '%$search%'";
}

$query .= " LIMIT 50"; // Limitar la cantidad de resultados a 50

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
    <div class="container">
        <!-- Mobile Menu Toggle - DEBE ESTAR FUERA DEL SIDEBAR boton unico para el dispositvo moviles-->
        <button id="mobileToggle" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <!-------------------------->
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="logo" style="cursor: pointer;" id="dassd">
                <h2>Pos Venta</h2>
                <button id="toggleMenu" class="toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Redirige al index cuando se preciona el logo -->
            <script>
                document.getElementById('dassd').addEventListener('click', function () {
                    window.location.href = 'index.php';
                });
            </script>

            <ul class="menu">
                <ul class="menu">
                    <li onclick="navigateTo('')"><i class="fas fa-cogs"></i> Administracion</li>
                    <li onclick="navigateTo('')"><i class="fas fa-cash-register"></i>Cajas</li>
                    <li onclick="navigateTo('clientes.php')"><i class="fas fa-users"></i> Clientes</li>
                    <li onclick="navigateTo('')"><i class="fas fa-users"></i> Medidas</li>
                    <li onclick="navigateTo('')"><i class="fas fa-cogs"></i> Categorías</li>
                    <li onclick="navigateTo('productos_nuevo.php')"><i class="fas fa-box"></i> Productos</li>
                    <li onclick="navigateTo('')"><i class="fas fa-sign-in-alt"></i> Entradas</li>
                    <li onclick="navigateTo('')"><i class="fas fa-sign-out-alt"></i> Salidas</li>
                </ul>
            </nav>
<!--------------------------->
            <script>
                function navigateTo(page) {
                    window.location.href = page; // Cambia la URL en la misma pestaña
                }
            
                function toggleNav() {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.toggle('active'); // Añade o quita la clase active para mostrar/ocultar el menú
                }
            </script>
<!--------------------------->
        <!-- Overlay for mobile, no eliminar esto hace que aparezca las opciones sin recargar la pagina  -->
        <div class="overlay" id="overlay">
        </div>
    <div class="container">
        <h1>Lista de Clientes</h1>
         <!-- Formulario de búsqueda -->
         <!-- Formulario de búsqueda -->
        <form method="GET" action="clientes.php">
            <label for="search">Buscar por Nombre o Identificación:</label>
            <input type="text" id="search" name="search" value="<?php echo $search; ?>" autocomplete ="off">
            <button type="submit">Buscar</button>
        </form>
        <a href="clientes_nuevo.php"><input type="button" value="Nuevo Cliente"></a>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Tipo Identificación</th>
                    <th>Identificación</th>
                    <th>Teléfono</th>
                    <th>Notas</th>
                    <th>Límite Crédito</th>
                    <th>Balance Disponible</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nombreCompleto']; ?></td>
                    <td><?php echo $row['empresa']; ?></td>
                    <td><?php echo $row['tipo_identificacion']; ?></td>
                    <td><?php echo $row['identificacion']; ?></td>
                    <td><?php echo $row['telefono']; ?></td>
                    <td><?php echo $row['notas']; ?></td>
                    <td><?php echo $row['limite_credito']; ?></td>
                    <td><?php echo $row['balance']; ?></td>
                    <td><?php echo $row['direccion']; ?></td>
                    <td><?php echo $row['activo'] ? 'Activo' : 'Inactivo'; ?></td> <!-- Mostrar estado -->
                    <td>
                        <a href="clientes_actualizar.php?id=<?php echo $row['id']; ?>">Actualizar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>