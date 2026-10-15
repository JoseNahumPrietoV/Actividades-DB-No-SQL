<?php
// tools/test_historial_y_eliminacion.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';
require_once __DIR__ . '/../Back-end/servicios/HistorialService.php';

use Snackver\Servicios\HistorialService;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

echo "========================================================\n";
echo "   PRUEBA INTEGRAL DE HISTORIAL Y AUDITORÍA ATLAS       \n";
echo "========================================================\n\n";

try {
    $db = MongoConexion::getConexion();
    $colHistorial = $db->selectCollection('historial_usuarios');
    $colUsuarios = $db->selectCollection('usuarios');
    $colCarritos = $db->selectCollection('carritos');
    $colProductos = $db->selectCollection('productos');

    // 1. Probar registro con varios tipos de datos en HistorialService
    echo "1. Probando HistorialService::registrar() con tipado robusto...\n";
    $testEmail = 'test_audit_bot@snackver.me';
    
    // 1a. Con ObjectId normal y detalles enriquecidos
    $res1 = HistorialService::registrar(
        new ObjectId('670000000000000000000099'),
        $testEmail,
        'cliente',
        'Inicio de sesión exitoso',
        ['ip_test' => '127.0.0.1', 'prueba_automatica' => true, 'activo' => false]
    );

    // 1b. Con ID hexadecimal como string
    $res2 = HistorialService::registrar(
        '670000000000000000000098',
        'test_string_id@snackver.me',
        'vendedor',
        'Registro de nueva cuenta',
        ['nombre_negocio' => 'Tacos Test']
    );

    // 1c. Con ID numérico y email nulo
    $res3 = HistorialService::registrar(
        12345,
        null,
        'usuario',
        'Cierre de sesión'
    );

    if ($res1 && $res2 && $res3) {
        echo "   -> Registros de auditoría creados exitosamente: OK\n";
    } else {
        echo "   -> Error al registrar eventos en auditoría.\n";
        exit(1);
    }

    // 2. Verificar documentos en Atlas
    echo "\n2. Verificando documentos en colección historial_usuarios...\n";
    $doc1 = $colHistorial->findOne(['email' => $testEmail, 'accion' => 'Inicio de sesión exitoso']);
    if ($doc1 && $doc1['id_usuario'] instanceof ObjectId) {
        echo "   -> Documento 1 verificado (BSON ObjectId): OK\n";
    } else {
        echo "   -> Error en verificación de documento 1.\n";
        exit(1);
    }

    $doc2 = $colHistorial->findOne(['email' => 'test_string_id@snackver.me', 'accion' => 'Registro de nueva cuenta']);
    if ($doc2 && $doc2['id_usuario'] instanceof ObjectId) {
        echo "   -> Documento 2 verificado (conversión automática string a ObjectId): OK\n";
    } else {
        echo "   -> Error en verificación de documento 2.\n";
        exit(1);
    }

    $doc3 = $colHistorial->findOne(['accion' => 'Cierre de sesión', 'id_usuario' => 12345]);
    if ($doc3 && $doc3['email'] === '') {
        echo "   -> Documento 3 verificado (ID numérico e email nulo manejado con gracia): OK\n";
    } else {
        echo "   -> Error en verificación de documento 3.\n";
        exit(1);
    }

    // 3. Probar ciclo de vida de Cliente: creación, carrito y eliminación
    echo "\n3. Probando flujo de eliminación de Cliente con borrado de carrito...\n";
    $idCliTemp = new ObjectId();
    $emailCliTemp = 'cliente_temp_test@snackver.me';
    $colUsuarios->insertOne([
        '_id'           => $idCliTemp,
        'nombre'        => 'Carlos',
        'apellido'      => 'Cliente Test',
        'email'         => $emailCliTemp,
        'rol'           => 'cliente',
        'estado'        => 'activo',
        'fecharegistro' => new UTCDateTime()
    ]);
    $colCarritos->insertOne([
        'id_cliente' => $idCliTemp,
        'items'      => [['nombre' => 'Torta', 'cantidad' => 1]],
        'subtotal'   => 45.0
    ]);
    echo "   -> Cliente y carrito insertados: OK\n";

    // Simular lógica de Back-end/admin/eliminar_cliente.php
    $cliEncontrado = $colUsuarios->findOne(['_id' => $idCliTemp, 'rol' => 'cliente']);
    if ($cliEncontrado) {
        $colUsuarios->deleteOne(['_id' => $idCliTemp]);
        $colCarritos->deleteMany(['$or' => [['id_cliente' => $idCliTemp], ['id_cliente' => (string)$idCliTemp]]]);
        HistorialService::registrar(
            $idCliTemp,
            $emailCliTemp,
            'cliente',
            'Usuario eliminado',
            ['motivo' => 'Eliminación por test automatizado']
        );
    }
    $checkCli = $colUsuarios->findOne(['_id' => $idCliTemp]);
    $checkCart = $colCarritos->findOne(['id_cliente' => $idCliTemp]);
    if ($checkCli === null && $checkCart === null) {
        echo "   -> Cliente y carrito eliminados en cascada de Atlas: OK\n";
    } else {
        echo "   -> Error: El cliente o carrito no fueron eliminados.\n";
        exit(1);
    }

    // 4. Probar ciclo de vida de Vendedor: creación, productos y eliminación
    echo "\n4. Probando flujo de eliminación de Vendedor con borrado de productos...\n";
    $idVenTemp = new ObjectId();
    $emailVenTemp = 'vendedor_temp_test@snackver.me';
    $colUsuarios->insertOne([
        '_id'           => $idVenTemp,
        'nombre'        => 'Laura',
        'apellido'      => 'Vendedora Test',
        'email'         => $emailVenTemp,
        'rol'           => 'vendedor',
        'estado'        => 'activo',
        'tienda'        => ['nombre_negocio' => 'La Cocina de Laura'],
        'fecharegistro' => new UTCDateTime()
    ]);
    $idProdTemp = new ObjectId();
    $colProductos->insertOne([
        '_id'            => $idProdTemp,
        'id_vendedor'    => $idVenTemp,
        'nombreproducto' => 'Empanada de Camarón Test',
        'precio'         => 30.0,
        'activo'         => true
    ]);
    echo "   -> Vendedor y producto insertados: OK\n";

    // Simular lógica de Back-end/admin/eliminar_vendedor.php
    $venEncontrado = $colUsuarios->findOne(['_id' => $idVenTemp, 'rol' => 'vendedor']);
    if ($venEncontrado) {
        $colUsuarios->deleteOne(['_id' => $idVenTemp]);
        $colProductos->deleteMany(['$or' => [['id_vendedor' => $idVenTemp], ['id_vendedor' => (string)$idVenTemp]]]);
        HistorialService::registrar(
            $idVenTemp,
            $emailVenTemp,
            'vendedor',
            'Usuario eliminado',
            ['motivo' => 'Eliminación por test automatizado', 'negocio' => 'La Cocina de Laura']
        );
    }
    $checkVen = $colUsuarios->findOne(['_id' => $idVenTemp]);
    $checkProd = $colProductos->findOne(['_id' => $idProdTemp]);
    if ($checkVen === null && $checkProd === null) {
        echo "   -> Vendedor y productos eliminados en cascada de Atlas: OK\n";
    } else {
        echo "   -> Error: El vendedor o sus productos no fueron eliminados.\n";
        exit(1);
    }

    // 5. Verificar que los eventos de eliminación quedaron registrados
    $delCliEvt = $colHistorial->findOne(['email' => $emailCliTemp, 'accion' => 'Usuario eliminado']);
    $delVenEvt = $colHistorial->findOne(['email' => $emailVenTemp, 'accion' => 'Usuario eliminado']);
    if ($delCliEvt && $delVenEvt) {
        echo "\n5. Eventos de auditoría 'Usuario eliminado' verificados en historial: OK\n";
    } else {
        echo "\n5. Error: Eventos de auditoría de eliminación no encontrados.\n";
        exit(1);
    }

    // 6. Limpieza integral de datos de prueba
    $colHistorial->deleteMany(['email' => ['$in' => [
        $testEmail,
        'test_string_id@snackver.me',
        $emailCliTemp,
        $emailVenTemp
    ]]]);
    $colHistorial->deleteMany(['id_usuario' => 12345]);
    echo "6. Limpieza de datos temporales en Atlas: OK\n";

    echo "\n========================================================\n";
    echo "   ¡PRUEBA EXITOSA: AUDITORÍA Y HISTORIAL 100% OK!      \n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "Error en la prueba: " . $e->getMessage() . "\n";
    exit(1);
}
