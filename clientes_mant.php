<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Clientes</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Registro de Clientes</h1>
    <form action="Backend/clientes_guardar.php" method="POST" class="mb-5">
        <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="mb-3">
            <label for="credit_limit" class="form-label">Límite de Crédito</label>
            <input type="number" class="form-control" id="credit_limit" name="credit_limit" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Dirección</label>
            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
</div>
</body>
</html>
