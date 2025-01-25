<?php
    $host = 'localhost';
    $user = 'root';
    $password = 'Joelbless23';
    $dbname = 'pruebas_posventa';

    try {
        $cn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
        $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
?>
