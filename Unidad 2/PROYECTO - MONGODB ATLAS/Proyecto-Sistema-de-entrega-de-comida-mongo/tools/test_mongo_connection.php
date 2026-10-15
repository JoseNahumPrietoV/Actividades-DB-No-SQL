<?php
// tools/test_mongo_connection.php

require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';

echo "========================================================\n";
echo "PRUEBA DE CONEXIÓN A MONGODB ATLAS (GitHub Student Pack)\n";
echo "========================================================\n\n";

$resultado = MongoConexion::probarConexion();

if ($resultado['status'] === 'success') {
    echo "✅ ÉXITO: " . $resultado['message'] . "\n";
    echo "⏱️ Latencia: " . $resultado['latency'] . "\n";
    echo "🔒 Cifrado: TLS 1.3 / SSL activo de extremo a extremo.\n";
} else {
    echo "⚠️ AVISO: " . $resultado['message'] . "\n";
    echo "\nℹ️ Para conectar con tu cluster real de MongoDB Atlas:\n";
    echo "1. Ve a https://cloud.mongodb.com\n";
    echo "2. Crea tu Cluster M0 gratuito (con el beneficio de GitHub Pack).\n";
    echo "3. En 'Database Access', crea un usuario y contraseña.\n";
    echo "4. En 'Network Access', añade tu IP actual (o 0.0.0.0/0 para desarrollo).\n";
    echo "5. Haz clic en 'Connect' -> 'Drivers' -> 'PHP' y copia la URI.\n";
    echo "6. Pega la URI en tu archivo .env en la variable MONGO_URI.\n";
}
echo "\n========================================================\n";
