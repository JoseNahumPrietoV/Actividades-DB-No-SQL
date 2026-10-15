<?php
// cerrar_sesion.php

// 1. Siempre inicia la sesión antes de manipularla.
session_start();

// 2. Elimina todas las variables de la sesión.
$_SESSION = array();

// 3. Destruye la sesión por completo.
session_destroy();

// 4. Redirige al usuario a la página de login.
// (Asegúrate de que esta ruta sea correcta desde la ubicación de cerrar_sesion.php)
header("Location: ../../index.php"); 
exit();
?>