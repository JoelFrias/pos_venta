<?php
session_start();
session_unset();  // Elimina todas las variables de sesión
session_destroy(); // Destruye la sesión

// Redirigir al login con un mensaje opcional
header("Location: ../login.php");
exit();
?>
