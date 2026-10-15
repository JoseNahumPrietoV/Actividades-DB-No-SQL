<?php
/**
 * Test de Integración: Notificaciones por id_usuario, Marcar Todas Leídas y Reseñas Anónimas
 */

require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';
require_once __DIR__ . '/../Back-end/servicios/NotificacionService.php';

use Snackver\Servicios\NotificacionService;

echo "========================================================\n";
echo "   TEST DE INTEGRACIÓN: NOTIFICACIONES Y RESEÑAS        \n";
echo "========================================================\n\n";

try {
    $db = MongoConexion::getConexion();

    // 1. TEST DE NOTIFICACIONES
    echo "[1] Probando NotificacionService...\n";
    $testUsuarioId = "test_user_" . time();

    // Crear notificación 1
    $id1 = NotificacionService::crearNotificacion(
        $testUsuarioId,
        "Tu pedido #TEST1 está En Preparación",
        "pedido",
        "pedido_test_1"
    );
    echo "  -> Creada notificación 1: $id1\n";

    // Crear notificación 2
    $id2 = NotificacionService::crearNotificacion(
        $testUsuarioId,
        "Tu pedido #TEST1 está Listo para Entrega",
        "pedido",
        "pedido_test_1"
    );
    echo "  -> Creada notificación 2: $id2\n";

    // Obtener notificaciones
    $resNotif = NotificacionService::obtenerNotificaciones($testUsuarioId, 10);
    echo "  -> Total obtenidas: " . count($resNotif['notificaciones']) . " (No leídas: " . $resNotif['total_no_leidas'] . ")\n";
    if ($resNotif['total_no_leidas'] !== 2) {
        throw new Exception("Se esperaban 2 notificaciones no leídas, pero se obtuvieron " . $resNotif['total_no_leidas']);
    }

    // Marcar todas leídas
    $totalMarcadas = NotificacionService::marcarTodasLeidas($testUsuarioId);
    echo "  -> Marcadas como leídas: $totalMarcadas\n";

    // Re-comprobar no leídas
    $resNotifPost = NotificacionService::obtenerNotificaciones($testUsuarioId, 10);
    echo "  -> Total no leídas tras 'Leer todas': " . $resNotifPost['total_no_leidas'] . "\n";
    if ($resNotifPost['total_no_leidas'] !== 0) {
        throw new Exception("El contador de no leídas debió ser 0 tras marcar todas como leídas.");
    }
    echo "  [ OK ] Notificaciones por id_usuario y 'Leer todas' funcionan al 100%.\n\n";

    // Limpiar notificaciones de prueba
    $db->selectCollection('notificaciones')->deleteMany(['id_usuario' => $testUsuarioId]);

    // 2. TEST DE RESEÑA ANÓNIMA
    echo "[2] Probando Reseña Anónima en Atlas...\n";
    $colResenas = $db->selectCollection('resenas');

    $testResena = [
        'id_producto'  => new MongoDB\BSON\ObjectId(),
        'id_cliente'   => new MongoDB\BSON\ObjectId(),
        'calificacion' => 5,
        'comentario'   => 'Prueba de reseña con opción anónima',
        'es_anonimo'   => true,
        'fecharesena'  => new MongoDB\BSON\UTCDateTime()
    ];

    $resInsert = $colResenas->insertOne($testResena);
    $insertedId = $resInsert->getInsertedId();
    echo "  -> Insertada reseña de prueba con ID: $insertedId\n";

    $docLeido = $colResenas->findOne(['_id' => $insertedId]);
    $esAnonimoGuardado = !empty($docLeido['es_anonimo']);
    echo "  -> Campo 'es_anonimo' recuperado: " . ($esAnonimoGuardado ? 'TRUE' : 'FALSE') . "\n";

    if (!$esAnonimoGuardado) {
        throw new Exception("El campo es_anonimo no se persistió como true.");
    }

    // Simular lógica de mapeo en index.php
    $nomCliente = !empty($docLeido['es_anonimo']) ? "Estudiante Anónimo" : "Estudiante";
    echo "  -> Nombre resultante para frontend: '$nomCliente'\n";
    if ($nomCliente !== "Estudiante Anónimo") {
        throw new Exception("El nombre para la reseña anónima debió ser 'Estudiante Anónimo'.");
    }
    echo "  [ OK ] Reseña anónima persistida y validada correctamente.\n\n";

    // Limpiar reseña de prueba
    $colResenas->deleteOne(['_id' => $insertedId]);

    echo "========================================================\n";
    echo "  TODAS LAS PRUEBAS DE INTEGRACIÓN PASARON CON ÉXITO    \n";
    echo "========================================================\n";

} catch (Throwable $e) {
    echo " [ ERROR ] " . $e->getMessage() . "\n";
    exit(1);
}
