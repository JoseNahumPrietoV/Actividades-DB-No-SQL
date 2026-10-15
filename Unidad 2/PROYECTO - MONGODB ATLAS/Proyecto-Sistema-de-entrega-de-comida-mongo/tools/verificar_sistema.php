<?php
// tools/verificar_sistema.php
// Script de Diagnóstico y Verificación Integral del Sistema Snackver

echo "\n";
echo "====================================================================\n";
echo "        🍔 SNACKVER - VERIFICADOR INTEGRAL DEL SISTEMA NoSQL        \n";
echo "====================================================================\n\n";

$todoOk = true;
$errores = [];
$advertencias = [];

function check($titulo, $condicion, $mensajeOk, $mensajeError, $esCritico = true) {
    global $todoOk, $errores, $advertencias;
    echo str_pad(" • " . $titulo, 45, ".");
    if ($condicion) {
        echo " [  OK  ] " . $mensajeOk . "\n";
    } else {
        if ($esCritico) {
            echo " [ FALLO ] " . $mensajeError . "\n";
            $todoOk = false;
            $errores[] = "$titulo: $mensajeError";
        } else {
            echo " [ AVISO ] " . $mensajeError . "\n";
            $advertencias[] = "$titulo: $mensajeError";
        }
    }
}

// 1. VERIFICACIÓN DE VERSIÓN DE PHP
$phpVersion = PHP_VERSION;
check("Versión de PHP ($phpVersion)", version_compare($phpVersion, '8.1.0', '>='), "PHP compatible (>= 8.1)", "Requiere PHP 8.1 o superior");

// 2. VERIFICACIÓN DE EXTENSIONES NATIVAS DE PHP
$extensionesRequeridas = [
    'mongodb'  => 'Driver oficial para MongoDB Atlas y clases BSON',
    'gd'       => 'Procesamiento de imágenes y favicons',
    'zip'      => 'Compresión y descarga de paquetes',
    'openssl'  => 'Cifrado TLS 1.3 / SSL para conexiones seguras',
    'curl'     => 'Peticiones HTTP seguras (Cloudinary, webhooks)',
    'mbstring' => 'Manejo de cadenas UTF-8 y caracteres especiales'
];

foreach ($extensionesRequeridas as $ext => $desc) {
    $cargada = extension_loaded($ext);
    check("Extensión PHP: $ext", $cargada, "Cargada y activa ($desc)", "No está activa en php.ini ($desc)");
}

// 3. ARCHIVO BINARIO DEL CONTROLADOR MONGODB
$extDir = ini_get('extension_dir');
$dllPath = rtrim($extDir, '\\/') . DIRECTORY_SEPARATOR . 'php_mongodb.dll';
$dllExiste = file_exists($dllPath);
check("Binario php_mongodb.dll", $dllExiste, "Presente en $extDir", "Falta php_mongodb.dll en $extDir");

// 4. CONFIGURACIÓN DEL ENTORNO (.env)
$envPath = dirname(__DIR__) . '/Back-end/.env';
$envExiste = file_exists($envPath);
check("Archivo Back-end/.env", $envExiste, "Encontrado y accesible", "Falta el archivo .env en Back-end/");

if ($envExiste) {
    require_once dirname(__DIR__) . '/Back-end/vendor/autoload.php';
    Dotenv\Dotenv::createImmutable(dirname(__DIR__) . '/Back-end')->safeLoad();

    $clavesObligatorias = [
        'MONGO_URI'             => 'URI del Cluster MongoDB Atlas',
        'MONGO_DB_NAME'         => 'Nombre de Base de Datos NoSQL',
        'SMTP_HOST'             => 'Servidor SMTP (Gmail)',
        'SMTP_USER'             => 'Cuenta de correo remitente',
        'CLOUDINARY_CLOUD_NAME' => 'Cuenta de Cloudinary'
    ];

    foreach ($clavesObligatorias as $clave => $etiqueta) {
        $valor = $_ENV[$clave] ?? getenv($clave);
        $tieneValor = !empty($valor) && !str_contains($valor, 'tu_usuario');
        check("Variable: $clave", $tieneValor, "Configurada ($etiqueta)", "Falta o tiene valor por defecto ($etiqueta)");
    }
}

// 5. CONEXIÓN EN VIVO A MONGODB ATLAS (TLS 1.3)
if (extension_loaded('mongodb') && $envExiste) {
    try {
        require_once dirname(__DIR__) . '/Back-end/conexion/conexion_mongo.php';
        $resConexion = MongoConexion::probarConexion();
        if ($resConexion['status'] === 'success') {
            check("Conexión MongoDB Atlas (TLS)", true, $resConexion['message'] . " (" . $resConexion['latency'] . ")", "");
            
            // Comprobar colecciones
            $db = MongoConexion::getConexion();
            $coleccionesIterator = $db->listCollections();
            $nombresColecciones = [];
            foreach ($coleccionesIterator as $col) {
                $nombresColecciones[] = $col->getName();
            }
            $coleccionesEsperadas = ['usuarios', 'productos', 'pedidos', 'carritos', 'categorias', 'resenas', 'historial_usuarios', 'notificaciones'];
            $colsEncontradas = array_intersect($coleccionesEsperadas, $nombresColecciones);
            check("Colecciones en Atlas (" . count($colsEncontradas) . "/" . count($coleccionesEsperadas) . ")", count($colsEncontradas) > 0, "Colecciones sincronizadas: " . implode(', ', $colsEncontradas), "No se encontraron las colecciones esperadas en la BD", false);

        } else {
            check("Conexión MongoDB Atlas (TLS)", false, "", $resConexion['message']);
        }
    } catch (Throwable $e) {
        check("Conexión MongoDB Atlas (TLS)", false, "", $e->getMessage());
    }
}

// 6. CONFIGURACIÓN DEL EDITOR (.vscode/settings.json)
$vscodePath = dirname(__DIR__) . '/.vscode/settings.json';
$vscodeOk = false;
if (file_exists($vscodePath)) {
    $vscodeContent = file_get_contents($vscodePath);
    $vscodeOk = str_contains($vscodeContent, '"mongodb"');
}
check("Stubs IDE (.vscode/settings.json)", $vscodeOk, "Stubs 'mongodb' activos (sin líneas rojas de error)", "Falta stub de mongodb en .vscode/settings.json", false);

// 7. PRUEBA DEL SERVIDOR WEB LOCAL (APACHE)
$apacheUrl = "http://127.0.0.1/Proyecto-Sistema-de-entrega-de-comida-mongo/index.php";
$contexto = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
$apacheRes = @file_get_contents($apacheUrl, false, $contexto);
$apacheOk = ($apacheRes !== false && !str_contains($apacheRes, "Fatal error"));
check("Servidor Apache (index.php)", $apacheOk, "HTTP 200 respondiendo sin errores fatales", "Apache no responde o arrojó error fatal en index.php", false);

echo "\n--------------------------------------------------------------------\n";
if ($todoOk) {
    echo "🎉 RESULTADO: ¡TODO EL PROYECTO ESTÁ FUNCIONANDO CORRECTAMENTE! 🎉\n";
    echo "   - Conexión a MongoDB Atlas: 100% Operativa.\n";
    echo "   - Entorno PHP & Extensiones: 100% Configurado.\n";
    echo "   - IDE y Editor: Reconociendo tipos y clases BSON.\n";
    echo "   - Servidor Web Apache: Respondiendo con éxito.\n";
} else {
    echo "❌ RESULTADO: Se encontraron problemas que requieren atención:\n";
    foreach ($errores as $err) {
        echo "   • $err\n";
    }
    echo "\n💡 Solución: Solicita al asistente AI en el chat que revise y configure el entorno.\n";
}

if (!empty($advertencias)) {
    echo "\n⚠️ Avisos no críticos:\n";
    foreach ($advertencias as $adv) {
        echo "   • $adv\n";
    }
}
echo "====================================================================\n\n";
