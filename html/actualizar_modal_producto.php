<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
</head>
<body>
<!-- Modal de actualización de producto -->
<div id="modalActualizar" class="modal-actualizar-producto">
    <div class="modal-actualizar-contenedor">
        <span class="cerrar-actualizar" onclick="cerrarModal()">&times;</span>
        <h3 class="form-title">Actualizar Producto</h3>
        <form class="registration-form" action="php/producto_actualizar.php" method="POST">
            <fieldset>
                <legend>Datos del Producto</legend>
                
                <!-- Campos ocultos para enviar el ID del producto -->
                <input type="hidden" id="idProducto" name="idProducto" value="<?php echo $idProducto; ?>">

                <div class="form-grid">
                    <div class="form-group">
                       <div class="modal-input1-form-group">
                        <label for="descripcion">Descripción:</label>
                        <input type="text" id="descripcion" name="descripcion" class="modal-input1" >
                    </div>
                </div>
                <div class="form-group"></div>
                    <div class="form-group">
                        <label for="precioCompra">Precio Compra:</label>
                        <input type="number" id="precioCompra" name="precioCompra" step="0.01" class="modal-input" >
                    </div>
                    <div class="form-group">
                        <label for="precioVenta1">Precio Venta 1:</label>
                        <input type="number" id="precioVenta1" name="precioVenta1" step="0.01" class="modal-input" >
                    </div>
                    <div class="form-group">
                        <label for="precioVenta2">Precio Venta 2:</label>
                        <input type="number" id="precioVenta2" name="precioVenta2" step="0.01" class="modal-input" >
                    </div>
                    <div class="form-group">
                        <label for="reorden">Reorden:</label>
                        <input type="number" id="reorden" name="reorden" class="modal-input" >
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo de Producto:</label>
                         <select id="tipo" name="tipo" class="modal-input">
                         <?php foreach ($tipos_producto as $tipo): ?>
                          <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['descripcion']); ?></option>
                         <?php endforeach; ?>
                       </select>
                </div> 
                    <div class="form-group">
                        <label for="activo">Estado:</label>
                        <select id="activo" name="activo" class="modal-input" >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="button-actualizar-modal">Actualizar</button>
                <!-- implementarle diseno -->
            </fieldset>
        </form>
    </div>
</div>
    
</body>
</html>