<?php

$host = '127.0.0.1';
$db_name = 'sistemawebsnackver';
$username = 'root';
$password = '2884';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

$sql = "SELECT idcategoria, nombrecategoria FROM categoriaproducto WHERE estado = 'activo'";
$stmt = $pdo->prepare($sql);
$stmt->execute();

// 3. Obtenemos todos los resultados en un nuevo array
$categorias_db = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Obtener la hora actual para el saludo dinámico
$hour = date('G');
if ($hour < 12) {
    $greeting = "¡Buenos días!";
} elseif ($hour < 19) {
    $greeting = "¡Buenas tardes!";
} else {
    $greeting = "¡Buenas noches!";
}
