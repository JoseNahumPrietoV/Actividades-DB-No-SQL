<?php
namespace Snackver\Servicios;

use MongoConexion;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Exception;
use Throwable;

require_once __DIR__ . '/../conexion/conexion_mongo.php';

/**
 * Servicio centralizado de auditoría y registro de eventos de usuario en MongoDB Atlas.
 */
class HistorialService
{
    /**
     * Registra un evento en la colección historial_usuarios
     *
     * @param string|\MongoDB\BSON\ObjectId|null $id_usuario Identificador del usuario
     * @param string $email Correo electrónico del usuario
     * @param string $rol Rol del usuario (admin, cliente, vendedor)
     * @param string $accion Nombre o descripción de la acción realizada
     * @param array $detalles Información adicional contextual
     * @return bool Retorna true si se registró exitosamente, false en caso de error
     */
    public static function registrar($id_usuario, ?string $email, string $rol = 'usuario', string $accion = 'Evento', array $detalles = []): bool
    {
        try {
            $mongoDb = MongoConexion::getConexion();
            $colHistorial = $mongoDb->selectCollection('historial_usuarios');

            // 1. Normalizar ID de usuario a BSON ObjectId si es un hash hexadecimal de 24 caracteres
            $idBson = $id_usuario;
            if (is_string($id_usuario) && preg_match('/^[a-f\d]{24}$/i', $id_usuario)) {
                try {
                    $idBson = new ObjectId($id_usuario);
                } catch (Throwable $e) {
                    $idBson = $id_usuario;
                }
            } elseif (is_numeric($id_usuario)) {
                $idBson = (int)$id_usuario;
            }

            // 2. Obtener dirección IP del cliente con detección de proxies y fallback seguro
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (is_string($ip) && strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (empty($ip) || !is_string($ip)) {
                $ip = '127.0.0.1';
            }

            // 3. Obtener agente de usuario (dispositivo / navegador) recortado de manera segura en UTF-8
            $rawAgente = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI / Desconocido';
            $dispositivo = trim((string)$rawAgente);
            if (mb_strlen($dispositivo, 'UTF-8') > 255) {
                $dispositivo = mb_substr($dispositivo, 0, 252, 'UTF-8') . '...';
            }

            // 4. Estructurar documento de auditoría
            $documento = [
                'id_usuario'  => $idBson,
                'email'       => mb_strtolower(trim($email ?? ''), 'UTF-8'),
                'rol'         => trim($rol),
                'accion'      => trim($accion),
                'ip'          => $ip,
                'dispositivo' => $dispositivo,
                'detalles'    => $detalles,
                'fecha'       => new UTCDateTime()
            ];

            $colHistorial->insertOne($documento);
            return true;

        } catch (Throwable $e) {
            error_log("Error en HistorialService::registrar: " . $e->getMessage());
            return false;
        }
    }
}

// Alias global para permitir invocar HistorialService::registrar() directamente sin 'use'
if (!class_exists('HistorialService', false)) {
    class_alias('Snackver\Servicios\HistorialService', 'HistorialService');
}
