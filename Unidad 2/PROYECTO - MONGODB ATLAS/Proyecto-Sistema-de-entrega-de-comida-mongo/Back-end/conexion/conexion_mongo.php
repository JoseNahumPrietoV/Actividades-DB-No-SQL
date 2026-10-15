<?php
// Back-end/conexion/conexion_mongo.php

// Cargar autoload de composer buscando en Back-end o en la raíz
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../Back-end/vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/Back-end/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use Dotenv\Dotenv;
use MongoDB\Client as MongoClient;
use MongoDB\Driver\ServerApi;

class MongoConexion
{
    private static $cliente = null;
    private static $db = null;
    private static $nombreDb = "sistemawebsnackver";
    private static $uri = "";

    /**
     * Obtiene y retorna la instancia de base de datos activa en MongoDB Atlas
     * @return \MongoDB\Database
     * @throws Exception
     */
    public static function getConexion()
    {
        if (self::$db !== null) {
            return self::$db;
        }

        // Validación preventiva de la extensión nativa de MongoDB
        if (!extension_loaded('mongodb')) {
            $msg = "Error crítico: La extensión oficial de MongoDB para PHP no está activa en este servidor.\n"
                 . "Solución: Pídele al asistente AI en el chat que verifique o configure el entorno en segundo plano.";
            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "\n⚠️ " . $msg . "\n\n");
            } else {
                echo "<div style='font-family:Segoe UI,Helvetica,Arial,sans-serif;max-width:650px;margin:60px auto;padding:25px;border:2px solid #e11d48;border-radius:12px;background:#fff1f2;color:#881337;box-shadow:0 10px 25px rgba(0,0,0,0.1);'>"
                   . "<h2 style='margin-top:0;display:flex;align-items:center;gap:10px;color:#be123c;'>⚠️ Extensión de MongoDB no detectada en PHP</h2>"
                   . "<p style='font-size:15px;line-height:1.5;'>El servidor PHP no tiene cargada la extensión nativa <code>php_mongodb.dll</code> requerida para conectar con MongoDB Atlas.</p>"
                   . "<div style='background:#ffffff;border:1px solid #fecdd3;border-radius:8px;padding:15px;margin:15px 0;'>"
                   . "<strong style='color:#1e293b;'>💡 Solución asistida:</strong>"
                   . "<ol style='margin:10px 0 0 20px;padding:0;color:#334155;line-height:1.6;'>"
                   . "<li>Pídele al asistente de IA en el chat que configure o active el entorno en segundo plano.</li>"
                   . "<li>Reinicia Apache en tu panel de control de XAMPP.</li>"
                   . "<li>Recarga esta página web.</li>"
                   . "</ol>"
                   . "</div>"
                   . "</div>";
            }
            throw new Exception("Extensión 'mongodb' no encontrada en PHP.");
        }

        // Buscar .env en Back-end o raíz solo si no se ha cargado previamente
        if (empty($_ENV['MONGO_URI']) && !getenv('MONGO_URI')) {
            $envPaths = [
                __DIR__ . '/..',
                dirname(__DIR__),
                dirname(dirname(__DIR__)) . '/Back-end'
            ];
            foreach ($envPaths as $envDir) {
                if (file_exists($envDir . '/.env')) {
                    $dotenv = Dotenv::createImmutable($envDir);
                    $dotenv->safeLoad();
                    break;
                }
            }
        }

        self::$uri = $_ENV['MONGO_URI'] ?? getenv('MONGO_URI') ?: ($_SERVER['MONGO_URI'] ?? 'mongodb://127.0.0.1:27017');
        self::$nombreDb = $_ENV['MONGO_DB_NAME'] ?? getenv('MONGO_DB_NAME') ?: ($_SERVER['MONGO_DB_NAME'] ?? 'sistemawebsnackver');

        try {
            // Configurar API de servidor v1 y opciones de alto rendimiento para MongoDB Atlas
            $apiVersion = new ServerApi(ServerApi::V1);
            $uriOptions = [
                'appName'                  => 'Snackver',
                'compressors'              => 'snappy,zlib',
                'serverSelectionTimeoutMS' => 10000, // Timeout de selección de servidor cloud calibrado
                'serverSelectionTryOnce'   => false, // Reintento ágil dentro del timeout
                'connectTimeoutMS'         => 10000, // Timeout de conexión inicial
                'socketTimeoutMS'          => 20000, // Timeout para lectura/escritura de socket
                'retryWrites'              => true   // Reintentos automáticos nativos de escritura
            ];

            self::$cliente = new MongoClient(self::$uri, $uriOptions, [
                'serverApi' => $apiVersion
            ]);

            self::$db = self::$cliente->selectDatabase(self::$nombreDb);
            return self::$db;

        } catch (Exception $e) {
            error_log("Error de conexión a MongoDB Atlas: " . $e->getMessage());
            throw new Exception("No se pudo conectar a MongoDB Atlas. Verifica tu MONGO_URI en el archivo .env. Detalle: " . $e->getMessage());
        }
    }

    /**
     * Obtiene una colección de MongoDB por nombre
     * @param string $nombreColeccion
     * @return \MongoDB\Collection
     */
    public static function getColeccion($nombreColeccion)
    {
        $database = self::getConexion();
        return $database->selectCollection($nombreColeccion);
    }

    /**
     * Obtiene la instancia del cliente oficial de MongoDB
     * @return \MongoDB\Client
     */
    public static function getClient()
    {
        if (self::$cliente === null) {
            self::getConexion();
        }
        return self::$cliente;
    }

    /**
     * Prueba el estado de la conexión a MongoDB Atlas con reintento automático
     * @return array [status => 'success'|'error', 'latency' => ..., 'message' => ...]
     */
    public static function probarConexion()
    {
        for ($intento = 1; $intento <= 3; $intento++) {
            try {
                $start = microtime(true);
                $db = self::getConexion();
                $comando = new \MongoDB\Driver\Command(['ping' => 1]);
                self::$cliente->getManager()->executeCommand(self::$nombreDb, $comando);
                $latency = round((microtime(true) - $start) * 1000, 2);

                return [
                    'status' => 'success',
                    'latency' => $latency . ' ms',
                    'message' => '¡Conexión exitosa a MongoDB Atlas con cifrado TLS!'
                ];
            } catch (\Throwable $e) {
                self::$cliente = null;
                self::$db = null;
                if ($intento < 3) {
                    usleep(300000); // Espera 300ms ante fluctuaciones de handshake TLS cloud
                    continue;
                }
                return [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
    }
}
