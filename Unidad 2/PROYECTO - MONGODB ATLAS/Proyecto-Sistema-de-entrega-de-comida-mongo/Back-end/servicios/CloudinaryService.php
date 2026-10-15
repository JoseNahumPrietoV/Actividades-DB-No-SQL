<?php
namespace Snackver\Servicios;

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
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Exception;

class CloudinaryService
{
    private static $inicializado = false;
    private static $cloudName;
    private static $apiKey;
    private static $apiSecret;
    private static $folder;

    private static function inicializar()
    {
        if (self::$inicializado) {
            return;
        }

        if (empty($_ENV['CLOUDINARY_CLOUD_NAME']) && !getenv('CLOUDINARY_CLOUD_NAME')) {
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

        self::$cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?: ($_SERVER['CLOUDINARY_CLOUD_NAME'] ?? '');
        self::$apiKey    = $_ENV['CLOUDINARY_API_KEY'] ?? getenv('CLOUDINARY_API_KEY') ?: ($_SERVER['CLOUDINARY_API_KEY'] ?? '');
        self::$apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?: ($_SERVER['CLOUDINARY_API_SECRET'] ?? '');
        self::$folder    = $_ENV['CLOUDINARY_UPLOAD_FOLDER'] ?? getenv('CLOUDINARY_UPLOAD_FOLDER') ?: ($_SERVER['CLOUDINARY_UPLOAD_FOLDER'] ?? 'snackver_platillos');

        if (!empty(self::$cloudName) && !empty(self::$apiKey) && !empty(self::$apiSecret) && self::$cloudName !== 'tu_cloud_name') {
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => self::$cloudName,
                    'api_key'    => self::$apiKey,
                    'api_secret' => self::$apiSecret,
                ],
                'url' => [
                    'secure' => true
                ]
            ]);
            self::$inicializado = true;
        }
    }

    /**
     * Sube un archivo de imagen a Cloudinary y devuelve la URL segura HTTPS optimizada.
     * Si las credenciales no están configuradas aún, usa un fallback local temporal con advertencia.
     */
    public static function subirImagen($fileTmpPath, $customFolder = null)
    {
        self::inicializar();

        if (empty($fileTmpPath) || !file_exists($fileTmpPath)) {
            throw new Exception("El archivo de imagen no existe o no es válido.");
        }

        $carpeta = $customFolder ?? self::$folder ?? 'snackver_platillos';

        // Si Cloudinary está configurado correctamente en .env
        if (self::$inicializado) {
            try {
                $upload = new UploadApi();
                $resultado = $upload->upload($fileTmpPath, [
                    'folder' => $carpeta,
                    'resource_type' => 'image'
                ]);

                if (isset($resultado['secure_url'])) {
                    $urlOptimizada = $resultado['secure_url'];
                    if (strpos($urlOptimizada, '/upload/') !== false && strpos($urlOptimizada, '/upload/f_auto') === false) {
                        $urlOptimizada = str_replace('/upload/', '/upload/f_auto,q_auto/', $urlOptimizada);
                    }
                    return [
                        'status' => 'success',
                        'url' => $urlOptimizada,
                        'public_id' => $resultado['public_id'] ?? null
                    ];
                } else {
                    throw new Exception("Cloudinary no devolvió una URL válida.");
                }
            } catch (Exception $e) {
                throw new Exception("Error al subir a Cloudinary: " . $e->getMessage());
            }
        } else {
            // Fallback mientras el usuario ingresa sus credenciales de Cloudinary en .env
            $targetDir = dirname(dirname(__DIR__)) . "/img/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $fileName = time() . '_' . uniqid() . '.jpg';
            $dest = $targetDir . $fileName;
            copy($fileTmpPath, $dest);
            return [
                'status' => 'local_fallback',
                'url' => 'img/' . $fileName,
                'message' => 'Configura CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY y CLOUDINARY_API_SECRET en .env para almacenamiento en la nube permanente.'
            ];
        }
    }
}
