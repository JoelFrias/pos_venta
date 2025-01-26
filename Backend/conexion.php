<?php
// Datos de conexión
$servername = "localhost";
$username = "root";
$password = "Joelbless23";
$dbname = "pos_venta";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>