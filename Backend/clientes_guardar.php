<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $credit_limit = $_POST['credit_limit'];
    $address = $_POST['address'];

    try {
        $sql = "INSERT INTO clientes (nombre, telefono, limite_credito, direccion) VALUES (?, ?, ?, ?)";
        $stmt = $cn->prepare($sql);
        $stmt->execute([$name, $phone, $credit_limit, $address]);
        header('Location: ../clientes_mant.php');
    } catch (PDOException $e) {
        echo "Error al registrar cliente: " . $e->getMessage();
    }
}
?>
