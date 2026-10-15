<?php
/**
 * Generador Completo de los 10 Diagramas Técnicos Puros (Unidad 2 - Snackver)
 * Estilo visual: Ingeniería de Software pura (UML / ERD estándar).
 * Cero barras decorativas de diapositivas, cero párrafos ni viñetas explicativas dentro de las cajas.
 * Solo entidades, atributos con tipos de datos (campo : Tipo [PK/Index]) y relaciones limpias.
 * Calibración milimétrica de espaciado sin traslapes ni contacto entre etiquetas y cajas.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$outDir = __DIR__ . '/diagramas_sobrios';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fontBold    = 'C:/Windows/Fonts/segoeuib.ttf';
$fontRegular = 'C:/Windows/Fonts/segoeui.ttf';
$fontMono    = 'C:/Windows/Fonts/consola.ttf';

if (!file_exists($fontBold)) {
    $fontBold    = 'C:/Windows/Fonts/arialbd.ttf';
    $fontRegular = 'C:/Windows/Fonts/arial.ttf';
    $fontMono    = 'C:/Windows/Fonts/cour.ttf';
}

function initCanvasPure($w, $h) {
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $bg);

    $border = imagecolorallocate($im, 226, 232, 240); // #E2E8F0
    imagerectangle($im, 0, 0, $w - 1, $h - 1, $border);

    return $im;
}

function drawUmlCard($im, $x, $y, $w, $h, $title, $fields, $fontBold, $fontMono, $isSub = false) {
    $cardBg   = $isSub ? imagecolorallocate($im, 248, 250, 252) : imagecolorallocate($im, 255, 255, 255);
    $headerBg = $isSub ? imagecolorallocate($im, 51, 65, 85)     : imagecolorallocate($im, 15, 23, 42); // #0F172A
    $border   = imagecolorallocate($im, 203, 213, 225); // #CBD5E1

    imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $cardBg);
    $headerHeight = 26;
    imagefilledrectangle($im, $x, $y, $x + $w, $y + $headerHeight, $headerBg);
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $border);

    $white = imagecolorallocate($im, 255, 255, 255);
    imagettftext($im, 9, 0, $x + 10, $y + 18, $white, $fontBold, $title);

    $textDark = imagecolorallocate($im, 15, 23, 42);
    $currY = $y + $headerHeight + 18;

    foreach ($fields as $field) {
        if ($field === '[LINE]') {
            imageline($im, $x, $currY - 8, $x + $w, $currY - 8, $border);
            $currY += 8;
            continue;
        }

        imagettftext($im, 8.5, 0, $x + 10, $currY, $textDark, $fontMono, $field);
        $currY += 19;
    }
}

function drawUmlArrow($im, $x1, $y1, $x2, $y2, $label = '', $fontBold = '', $isDashed = false) {
    $color = imagecolorallocate($im, 71, 85, 105);
    imagesetthickness($im, 2);

    if ($isDashed) {
        imagedashedline($im, $x1, $y1, $x2, $y2, $color);
    } else {
        imageline($im, $x1, $y1, $x2, $y2, $color);
    }

    $angle = atan2($y2 - $y1, $x2 - $x1);
    $headLen = 8;
    $px1 = (int)($x2 - $headLen * cos($angle - M_PI / 6));
    $py1 = (int)($y2 - $headLen * sin($angle - M_PI / 6));
    $px2 = (int)($x2 - $headLen * cos($angle + M_PI / 6));
    $py2 = (int)($y2 - $headLen * sin($angle + M_PI / 6));
    imagefilledpolygon($im, [$x2, $y2, $px1, $py1, $px2, $py2], $color);

    if ($label !== '' && $fontBold !== '') {
        $mx = (int)(($x1 + $x2) / 2);
        $my = (int)(($y1 + $y2) / 2);
        $bg = imagecolorallocate($im, 255, 255, 255);
        $border = imagecolorallocate($im, 203, 213, 225);
        $textLen = (int)(strlen($label) * 6.5);
        $half = (int)($textLen / 2);
        imagefilledrectangle($im, $mx - $half - 4, $my - 10, $mx + $half + 4, $my + 4, $bg);
        imagerectangle($im, $mx - $half - 4, $my - 10, $mx + $half + 4, $my + 4, $border);
        imagettftext($im, 7.5, 0, $mx - $half, $my, $color, $fontBold, $label);
    }
}

// ============================================================================
// 1. DIAGRAMA 3.1: COLECCION usuarios
// ============================================================================
$im31 = initCanvasPure(1020, 310);
drawUmlCard($im31, 20, 20, 460, 270, "usuarios [Collection]", [
    "_id : ObjectId [PK]",
    "nombre : String",
    "apellido : String",
    "email : String [Index: Unique]",
    "telefono : String",
    "password_hash : String (Bcrypt)",
    "rol : Enum('cliente', 'vendedor', 'admin') [Index]",
    "estado : Enum('pendiente', 'activo', 'inactivo')",
    "fecharegistro : UTCDateTime",
    "[LINE]",
    "tienda : Subdocument [1:1 Embebido]",
    "verificacion_otp : Subdocument [1:1 Embebido]",
    "recuperacion_otp : Subdocument [1:1 Embebido]"
], $fontBold, $fontMono);

drawUmlCard($im31, 570, 20, 430, 120, "tienda [Subdocument 1:1]", [
    "nombre_negocio : String",
    "descripcion : String",
    "direccion : String",
    "foto_negocio : String (Cloudinary URL)"
], $fontBold, $fontMono, true);

drawUmlCard($im31, 570, 160, 430, 130, "verificacion_otp / recuperacion_otp [1:1]", [
    "codigo : String(6)",
    "expira : UTCDateTime (+15m)",
    "creado : UTCDateTime",
    "verificado : Boolean"
], $fontBold, $fontMono, true);

drawUmlArrow($im31, 480, 80, 570, 80, "1:1 Embebido", $fontBold);
drawUmlArrow($im31, 480, 225, 570, 225, "1:1 Embebido", $fontBold);

imagepng($im31, $outDir . '/diag1_1_doc_usuarios.png');
imagedestroy($im31);

// ============================================================================
// 2. DIAGRAMA 3.2: COLECCION pedidos
// ============================================================================
$im32 = initCanvasPure(1040, 340);
drawUmlCard($im32, 20, 20, 480, 300, "pedidos [Collection]", [
    "_id : ObjectId [PK]",
    "id_pedido_num : Int32 [Index]",
    "id_cliente : ObjectId [FK -> usuarios._id]",
    "id_vendedor : ObjectId [FK -> usuarios._id]",
    "total : Double",
    "estadopedido : String [Index]",
    "metodopago : Enum('Efectivo', 'SPEI', 'Tarjeta')",
    "referenciapago : String",
    "notificado_cliente : Int32",
    "notificado_vendedor : Int32",
    "fechapedido : UTCDateTime [Index]",
    "fechaentrega : UTCDateTime",
    "[LINE]",
    "items[] : Array<ItemPedido> [1:N Embebido]"
], $fontBold, $fontMono);

drawUmlCard($im32, 590, 80, 430, 155, "items[] : ItemPedido [Subdocument 1:N]", [
    "id_producto : ObjectId [FK -> productos._id]",
    "nombreproducto : String [Snapshot historico]",
    "cantidad : Int32",
    "precio_unitario : Double [Snapshot historico]",
    "importe : Double"
], $fontBold, $fontMono, true);

drawUmlArrow($im32, 500, 157, 590, 157, "1:N Embebido", $fontBold);

imagepng($im32, $outDir . '/diag1_2_doc_pedidos.png');
imagedestroy($im32);

// ============================================================================
// 3. DIAGRAMA 3.3: COLECCION carritos
// ============================================================================
$im33 = initCanvasPure(1040, 230);
drawUmlCard($im33, 20, 20, 480, 190, "carritos [Collection]", [
    "_id : ObjectId [PK]",
    "id_cliente : ObjectId [Index: Unique -> usuarios._id]",
    "subtotal : Double",
    "ultima_actualizacion : UTCDateTime",
    "[LINE]",
    "items[] : Array<ItemCarrito> [0..N Embebido]"
], $fontBold, $fontMono);

drawUmlCard($im33, 590, 20, 430, 155, "items[] : ItemCarrito [Subdocument 0..N]", [
    "idproducto : ObjectId / String [Ref -> productos]",
    "nombreproducto : String",
    "precio : Double",
    "cantidad : Int32",
    "imagen : String (Cloudinary URL)"
], $fontBold, $fontMono, true);

drawUmlArrow($im33, 500, 97, 590, 97, "0..N Embebido", $fontBold);

imagepng($im33, $outDir . '/diag1_3_doc_carritos.png');
imagedestroy($im33);

// ============================================================================
// 4. DIAGRAMA 3.4: COLECCIONES productos Y categorias
// ============================================================================
$im34 = initCanvasPure(1100, 280);
drawUmlCard($im34, 20, 20, 480, 240, "productos [Collection]", [
    "_id : ObjectId [PK]",
    "id_vendedor : ObjectId [FK -> usuarios._id]",
    "id_categoria : ObjectId [FK -> categorias._id]",
    "nombreproducto : String [Index]",
    "descripcion : String",
    "precio : Double",
    "stock_disponible : Int32",
    "imagen : String (Cloudinary URL)",
    "activo : Boolean [Index]",
    "fechacreacion : UTCDateTime"
], $fontBold, $fontMono);

drawUmlCard($im34, 610, 20, 470, 155, "categorias [Collection]", [
    "_id : ObjectId [PK]",
    "nombrecategoria : String [Index: Unique]",
    "descripcion : String",
    "estado : Enum('activo', 'inactivo') [Index]",
    "fecha_creacion : UTCDateTime"
], $fontBold, $fontMono);

drawUmlArrow($im34, 610, 95, 500, 95, "1:N Clasifica", $fontBold);

imagepng($im34, $outDir . '/diag1_4_doc_catalogo.png');
imagedestroy($im34);

// ============================================================================
// 5. DIAGRAMA 3.5: resenas, notificaciones Y historial_usuarios
// ============================================================================
$im35 = initCanvasPure(1160, 250);
drawUmlCard($im35, 20, 20, 350, 210, "resenas [Collection]", [
    "_id : ObjectId [PK]",
    "id_producto : ObjectId [FK -> productos]",
    "id_vendedor : ObjectId [FK -> usuarios]",
    "id_cliente : ObjectId [FK -> usuarios]",
    "calificacion : Int32 (1..5)",
    "comentario : String",
    "es_anonimo : Boolean",
    "fecharesena : UTCDateTime [Index]"
], $fontBold, $fontMono);

drawUmlCard($im35, 400, 20, 360, 210, "notificaciones [Collection]", [
    "_id : ObjectId [PK]",
    "id_usuario : ObjectId [FK -> usuarios]",
    "id_usuario_str : String [Index]",
    "tipo_usuario : String [Index]",
    "mensaje : String",
    "id_pedido : String",
    "url_destino : String",
    "leida : Boolean [Index]",
    "fecha : UTCDateTime [Index]"
], $fontBold, $fontMono);

drawUmlCard($im35, 790, 20, 350, 210, "historial_usuarios [Collection]", [
    "_id : ObjectId [PK]",
    "id_usuario : ObjectId / String [Index]",
    "email : String",
    "rol : String",
    "accion : String [Index]",
    "ip : String (IPv4 / IPv6)",
    "dispositivo : String (User-Agent)",
    "detalles : Document BSON",
    "fecha : UTCDateTime [Index]"
], $fontBold, $fontMono);

imagepng($im35, $outDir . '/diag1_5_doc_interaccion_auditoria.png');
imagedestroy($im35);

// ============================================================================
// 6. DIAGRAMA 4.1: ARQUITECTURA MONGODB ATLAS (REPLICA SET 3 NODOS)
// ============================================================================
$im41 = initCanvasPure(1260, 400);

drawUmlCard($im41, 20, 20, 300, 170, "Cliente PHP 8.2 (Apache)", [
    "Singleton: conexion_mongo.php",
    "Driver C: ext-mongodb v2.5",
    "SDK: mongodb/mongodb ^2.5",
    "Túnel: TLS 1.3 / SCRAM-SHA-256",
    "WriteConcern: w=majority",
    "Compresión: snappy, zlib"
], $fontBold, $fontMono);

drawUmlCard($im41, 20, 220, 300, 160, "Perímetro Atlas Cloud", [
    "DNS Seedlist: mongodb+srv://",
    "IP Access List (Whitelist)",
    "Cifrado en tránsito: TLS 1.3",
    "Cifrado en reposo: AES-256"
], $fontBold, $fontMono);

drawUmlArrow($im41, 170, 190, 170, 220, "Socket", $fontBold);

// Replica Set Box
$borderSlate = imagecolorallocate($im41, 148, 163, 184);
imagerectangle($im41, 390, 20, 1240, 380, $borderSlate);
$slateDark = imagecolorallocate($im41, 15, 23, 42);
imagettftext($im41, 9.5, 0, 405, 42, $slateDark, $fontBold, "MongoDB Atlas (AWS Cloud) — Replica Set 3 Nodos (Cluster0)");

drawUmlCard($im41, 410, 60, 350, 300, "Nodo Primario (PRIMARY)", [
    "Base de datos: sistemawebsnackver",
    "8 Colecciones NoSQL operativas",
    "[LINE]",
    "Motor: WiredTiger Engine",
    "36 Índices B-Tree activos",
    "Compresión de bloques",
    "Concurrencia a nivel documento",
    "[LINE]",
    "Oplog (Operations Log):",
    "Bitácora de escrituras hacia réplicas",
    "Lectura y Escritura activas"
], $fontBold, $fontMono);

drawUmlCard($im41, 870, 60, 350, 135, "Nodo Secundario 1 (SECONDARY)", [
    "Motor: WiredTiger (Copia espejo)",
    "Sincronización continua de Oplog",
    "Heartbeat cada 2s",
    "Elegible para Auto-Failover"
], $fontBold, $fontMono, true);

drawUmlCard($im41, 870, 225, 350, 135, "Nodo Secundario 2 (SECONDARY)", [
    "Motor: WiredTiger (Copia espejo)",
    "Sincronización continua de Oplog",
    "Garantía de Quórum (w=majority)",
    "Alta disponibilidad 99.99%"
], $fontBold, $fontMono, true);

drawUmlArrow($im41, 320, 210, 410, 210, "TLS 1.3", $fontBold);
drawUmlArrow($im41, 760, 125, 870, 125, "Oplog Sync", $fontBold);
drawUmlArrow($im41, 760, 290, 870, 290, "Oplog Sync", $fontBold);

imagepng($im41, $outDir . '/diag2_arquitectura_mongo_cluster.png');
imagedestroy($im41);

// ============================================================================
// 7. DIAGRAMA 4.2: ARQUITECTURA CLOUD END-TO-END
// ============================================================================
$im42 = initCanvasPure(1320, 360);

// Navegador
drawUmlCard($im42, 20, 20, 240, 160, "Cliente Web (Navegador)", [
    "HTML5 / CSS3 / Vanilla JS",
    "Sincronización MD5 (2s)",
    "Notificaciones reactivas (1.5s)",
    "Modales OTP 0 ms"
], $fontBold, $fontMono);

// Cron-job
drawUmlCard($im42, 20, 210, 240, 130, "cron-job.org (Monitor)", [
    "Ping GET https://snackver.me/",
    "Frecuencia: Cada 10 minutos",
    "Mantiene Render activo 24/7"
], $fontBold, $fontMono);

// DNS Namecheap
drawUmlCard($im42, 340, 100, 240, 160, "Namecheap (DNS & SSL)", [
    "Dominio: snackver.me",
    "A Record -> 216.24.57.1",
    "CNAME www -> Render",
    "SSL TLS 1.3 Let's Encrypt"
], $fontBold, $fontMono);

// Render Docker
drawUmlCard($im42, 660, 60, 250, 240, "Render Cloud (Docker)", [
    "Imagen: php:8.2-apache",
    "Extensión: PECL mongodb",
    "Extensiones: gd, zip, curl",
    "Apache 2.4 + mod_deflate",
    "Hardening: .htaccess",
    "Variables de Entorno .env"
], $fontBold, $fontMono);

// Servicios Cloud Destino
drawUmlCard($im42, 1010, 20, 290, 95, "MongoDB Atlas (Cloud)", [
    "Cluster M0 (AWS N. Virginia)",
    "8 Colecciones / 36 Índices",
    "TLS 1.3 / SCRAM-SHA-256"
], $fontBold, $fontMono);

drawUmlCard($im42, 1010, 135, 290, 95, "Cloudinary (CDN Fotos)", [
    "Imágenes de platillos y tiendas",
    "Auto-WebP / Auto-Quality",
    "Entrega CDN HTTPS"
], $fontBold, $fontMono);

drawUmlCard($im42, 1010, 250, 290, 95, "Google Apps Script (2FA)", [
    "Despacho HTTPS Puerto 443",
    "Evita bloqueo SMTP de Render",
    "Envío a Gmail / Hotmail"
], $fontBold, $fontMono);

drawUmlArrow($im42, 260, 100, 340, 140, "HTTPS", $fontBold);
drawUmlArrow($im42, 260, 260, 340, 220, "Ping", $fontBold);
drawUmlArrow($im42, 580, 180, 660, 180, "Proxy", $fontBold);
drawUmlArrow($im42, 910, 110, 1010, 70, "TLS 1.3", $fontBold);
drawUmlArrow($im42, 910, 180, 1010, 180, "HTTPS API", $fontBold);
drawUmlArrow($im42, 910, 250, 1010, 290, "Port 443", $fontBold);

imagepng($im42, $outDir . '/diag3_arquitectura_cloud.png');
imagedestroy($im42);

// ============================================================================
// 8. DIAGRAMA 5.1: FLUJO TRANSACCIONAL DE VENTAS Y PEDIDOS
// ============================================================================
$im51 = initCanvasPure(1280, 220);

drawUmlCard($im51, 20, 20, 230, 180, "usuarios [Collection]", [
    "_id : ObjectId [PK]",
    "rol : 'cliente' | 'vendedor'",
    "email : String",
    "tienda : { ... } (vendedor)"
], $fontBold, $fontMono);

drawUmlCard($im51, 340, 20, 240, 180, "carritos [Collection]", [
    "_id : ObjectId [PK]",
    "id_cliente : ObjectId [UK]",
    "subtotal : Double",
    "items[] : Array<ItemCarrito>"
], $fontBold, $fontMono);

drawUmlCard($im51, 670, 20, 250, 180, "pedidos [Collection]", [
    "_id : ObjectId [PK]",
    "id_pedido_num : Int32",
    "id_cliente, id_vendedor",
    "items[] : Array<ItemPedido>",
    "total, estadopedido"
], $fontBold, $fontMono);

drawUmlCard($im51, 1010, 20, 250, 180, "productos [Collection]", [
    "_id : ObjectId [PK]",
    "nombreproducto : String",
    "precio : Double",
    "stock_disponible : Int32"
], $fontBold, $fontMono);

drawUmlArrow($im51, 250, 110, 340, 110, "1:1 Cliente", $fontBold);
drawUmlArrow($im51, 580, 110, 670, 110, "Checkout", $fontBold);
drawUmlArrow($im51, 920, 110, 1010, 110, "Stock \$inc", $fontBold);

imagepng($im51, $outDir . '/diag4_1_flujo_pedidos.png');
imagedestroy($im51);

// ============================================================================
// 9. DIAGRAMA 5.2: FLUJO DE CATÁLOGO Y RESEÑAS
// ============================================================================
$im52 = initCanvasPure(1280, 360);

// Fila 1: Catálogo
drawUmlCard($im52, 20, 20, 310, 140, "categorias [Collection]", [
    "_id : ObjectId [PK]",
    "nombrecategoria : String [UK]",
    "estado : 'activo' | 'inactivo'"
], $fontBold, $fontMono);

drawUmlCard($im52, 440, 20, 320, 140, "productos [Collection]", [
    "_id : ObjectId [PK]",
    "id_categoria : ObjectId [FK]",
    "id_vendedor : ObjectId [FK]",
    "nombreproducto, precio, stock"
], $fontBold, $fontMono);

drawUmlCard($im52, 870, 20, 320, 140, "usuarios [Vendedor]", [
    "_id : ObjectId [PK]",
    "rol : 'vendedor'",
    "tienda : { nombre_negocio, ... }"
], $fontBold, $fontMono);

// Fila 2: Reseñas y Cliente
drawUmlCard($im52, 440, 200, 320, 140, "resenas [Collection]", [
    "_id : ObjectId [PK]",
    "id_producto : ObjectId [FK]",
    "id_vendedor : ObjectId [FK]",
    "id_cliente : ObjectId [FK]",
    "calificacion : Int32, comentario"
], $fontBold, $fontMono);

drawUmlCard($im52, 870, 200, 320, 140, "usuarios [Cliente]", [
    "_id : ObjectId [PK]",
    "rol : 'cliente'",
    "nombre, apellido, email"
], $fontBold, $fontMono);

drawUmlArrow($im52, 330, 90, 440, 90, "1:N Clasifica", $fontBold);
drawUmlArrow($im52, 870, 90, 760, 90, "1:N Publica", $fontBold);
drawUmlArrow($im52, 600, 160, 600, 200, "1:N Evaluado", $fontBold);
drawUmlArrow($im52, 870, 270, 760, 270, "1:N Escribe", $fontBold);

imagepng($im52, $outDir . '/diag4_2_flujo_catalogo_resenas.png');
imagedestroy($im52);

// ============================================================================
// 10. DIAGRAMA 5.3: FLUJO DE SEGURIDAD Y ALERTAS
// ============================================================================
$im53 = initCanvasPure(1200, 330);

drawUmlCard($im53, 20, 30, 380, 270, "usuarios [Collection]", [
    "_id : ObjectId [PK]",
    "email : String [Index: Unique]",
    "password_hash : String (Bcrypt)",
    "rol : 'cliente' | 'vendedor' | 'admin'",
    "estado : 'pendiente' | 'activo'",
    "fecharegistro : UTCDateTime",
    "[LINE]",
    "verificacion_otp : { codigo, expira }",
    "recuperacion_otp : { codigo, expira }"
], $fontBold, $fontMono);

drawUmlCard($im53, 580, 30, 590, 120, "historial_usuarios [Collection]", [
    "_id : ObjectId [PK]",
    "id_usuario : ObjectId / String [Index]",
    "accion : 'login' | 'registro' | '2FA' | 'delete' [Index]",
    "ip : String, dispositivo : String, fecha : UTCDateTime"
], $fontBold, $fontMono);

drawUmlCard($im53, 580, 180, 590, 120, "notificaciones [Collection]", [
    "_id : ObjectId [PK]",
    "id_usuario : ObjectId [FK -> usuarios._id]",
    "tipo_usuario : 'cliente' | 'vendedor' [Index]",
    "mensaje : String, leida : Boolean [Index], fecha : UTCDateTime"
], $fontBold, $fontMono);

drawUmlArrow($im53, 400, 90, 580, 90, "Audita Eventos", $fontBold);
drawUmlArrow($im53, 400, 240, 580, 240, "Alertas 0 ms", $fontBold);

imagepng($im53, $outDir . '/diag4_3_flujo_seguridad_auditoria.png');
imagedestroy($im53);

echo "10 diagramas UML limpios generados exitosamente con espaciado perfecto.\n";
