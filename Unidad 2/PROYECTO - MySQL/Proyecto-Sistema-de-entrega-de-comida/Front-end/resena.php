<?php
// --- 1. Configuración de la base de datos ---
// (Reemplaza con tus propios datos)
$db = "sistemawebsnackver";
$servidor = "127.0.0.1";
$usuario = "root";
$clave = "2884";

// Crear conexión con MySQLi
$conexion = new mysqli($servidor, $usuario, $clave, $db);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- 2. Consulta SQL ---
// Esta es la consulta que une las tres tablas
$sql = "SELECT
            c.nombre_cliente,
            c.apellido_cliente,
            p.nombreproducto,
            r.calificacion,
            r.comentario,
            r.fecharesena
        FROM
            resena AS r
        JOIN
            cliente AS c ON r.idcliente = c.idcliente
        JOIN
            producto AS p ON r.idproducto = p.idproducto
        ORDER BY
            r.fecharesena DESC"; // Ordena por fecha (más nuevas primero)

$result = $conexion->query($sql);
?>