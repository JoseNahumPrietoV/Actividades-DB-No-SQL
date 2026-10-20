<?php
/**
 * ============================================================
 * CONEXIÓN CENTRALIZADA A MONGODB ATLAS
 * ============================================================
 * Proyecto: TechnoGames
 * Patrón: Singleton (una sola conexión por request)
 * 
 * Uso:
 *   require_once __DIR__ . '/config/database.php';
 *   $db = Database::getInstance();
 *   $usuarios = $db->getCollection('usuarios');
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Database
{
    private static ?Database $instance = null;
    private Client $client;
    private $db;
    
    private function __construct()
    {
        // Cargar .env manualmente (sin dependencia de vlucas/phpdotenv)
        $this->loadEnv();
        
        $uri = getenv('MONGODB_URI');
        if (!$uri) {
            throw new RuntimeException('MONGODB_URI no configurada en .env');
        }
        
        $dbName = getenv('MONGODB_DATABASE') ?: 'technogames';
        
        try {
            $this->client = new Client($uri, [
                'tls' => true,
                'tlsAllowInvalidCertificates' => false,
                'retryWrites' => true,
                'serverSelectionTimeoutMS' => 10000,
            ]);
            
            $this->db = $this->client->selectDatabase($dbName);
            
            // Verificación rápida
            $this->client->selectDatabase('admin')->command(['ping' => 1]);
            
        } catch (Exception $e) {
            error_log("Error MongoDB: " . $e->getMessage());
            throw new RuntimeException("Error de conexión a MongoDB: " . $e->getMessage());
        }
    }
    
    private function loadEnv(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            return;
        }
        
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
    
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCollection(string $name)
    {
        return $this->db->selectCollection($name);
    }
    
    public function getDb()
    {
        return $this->db;
    }
    
    // Impedir clonación y deserialización
    private function __clone() {}
    public function __wakeup() { throw new RuntimeException("No se puede deserializar singleton"); }
}
