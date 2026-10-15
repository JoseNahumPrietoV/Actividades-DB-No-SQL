<?php 

$db = "sistemawebsnackver";
$servidor = "127.0.0.1";
$usuario = "root";
$clave = "2884";

// Crear conexión con MySQLi
$conexion = new mysqli($servidor, $usuario, $clave, $db);

// --- VERIFICACIÓN DE LA CONEXIÓN ---
// Si hay un error de conexión, el script se detiene y muestra el error.
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Si la conexión fue exitosa, el script continúa y establece el charset.
$conexion->set_charset("utf8");

?>