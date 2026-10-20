<?php
/**
 * Helpers generales para toda la aplicación
 */

require_once __DIR__ . '/database.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// ============================================
// CONFIGURACIÓN DE SESIÓN
// ============================================

/**
 * Configuración robusta de sesión.
 * Debe llamarse ANTES de session_start().
 */
function configureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // Ya hay sesión activa
    }
    
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '7200');
    
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
}
// ============================================
// RESPUESTAS JSON ESTANDARIZADAS
// ============================================

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonSuccess($data = null, string $message = ''): void
{
    $response = ['success' => true];
    if ($data !== null) $response = array_merge($response, $data);
    if ($message) $response['message'] = $message;
    jsonResponse($response);
}

function jsonError(string $message, int $status = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $status);
}

// ============================================
// AUTENTICACIÓN
// ============================================

function requireLogin(): array
{
    if (!isset($_SESSION['user_id'])) {
        jsonError('Debe iniciar sesión', 401);
    }
    
    try {
        $db = Database::getInstance();
        $userDoc = $db->getCollection('usuarios')->findOne([
            '_id' => new ObjectId($_SESSION['user_id'])
        ]);
        
        if (!$userDoc) {
            session_destroy();
            jsonError('Sesión inválida', 401);
        }
        
        // Convertir BSONDocument a array asociativo
        $user = (array)$userDoc->getArrayCopy();
        
        return $user;
    } catch (Exception $e) {
        jsonError('Error de autenticación: ' . $e->getMessage(), 500);
    }
}

function requireRole(string ...$roles): array
{
    $user = requireLogin();
    if (!in_array($user['rol'], $roles, true)) {
        jsonError('No tienes permisos para esta acción', 403);
    }
    return $user;
}

// ============================================
// CONVERSIONES
// ============================================

function toObjectId($value): ?ObjectId
{
    if ($value instanceof ObjectId) return $value;
    if (empty($value)) return null;
    try {
        return new ObjectId((string)$value);
    } catch (Exception $e) {
        return null;
    }
}

function bsonToArray($value)
{
    // Convierte recursivamente un documento BSON a array PHP
    if ($value instanceof ObjectId) {
        return (string)$value;
    }
    if ($value instanceof UTCDateTime) {
        return $value->toDateTime()->format('Y-m-d H:i:s');
    }
    if (is_array($value)) {
        return array_map('bsonToArray', $value);
    }
    if (is_object($value)) {
        return array_map('bsonToArray', (array)$value);
    }
    return $value;
}

// ============================================
// SANITIZACIÓN
// ============================================

function clean($str): string
{
    return trim((string)$str);
}

function cleanEmail($email): string
{
    return strtolower(trim((string)$email));
}

// ============================================
// LOG DE AUDITORÍA
// ============================================

function auditLog(string $event, array $data = [], ?string $userId = null): void
{
    try {
        $db = Database::getInstance();
        $db->getCollection('audit_log')->insertOne([
            'evento' => $event,
            'usuario_id' => $userId ? toObjectId($userId) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'datos' => $data,
            'fecha' => new UTCDateTime(),
        ]);
    } catch (Exception $e) {
        // No interrumpir el flujo si falla el log
        error_log("Audit log error: " . $e->getMessage());
    }
}
