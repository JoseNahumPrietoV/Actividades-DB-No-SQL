<?php
/**
 * Generador Maestro del Documento Técnico Oficial Snackver (Unidad 2)
 * Incorpora los 10 diagramas técnicos puros estilo UML/ERD,
 * las 11 capturas de pantalla reales de las vistas programadas en producción,
 * y redacción técnica directa, clara y enfocada en lo que se realizó.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$projectRoot = realpath(__DIR__ . '/..');
if (!$projectRoot) {
    $projectRoot = 'c:/xampp/htdocs/Proyecto-Sistema-de-entrega-de-comida-mongo';
}

$diagramsDir = __DIR__ . '/diagramas_sobrios';
$vistasDir   = __DIR__ . '/vistas_capturas';

// Asegurar que los 10 diagramas existan y estén actualizados
require_once __DIR__ . '/generar_diagramas_sobrios.php';

function xmlEsc($str) {
    return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function wPageBreak() {
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

function wEmptyPara() {
    return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:line="360" w:lineRule="auto"/></w:pPr></w:p>';
}

function wHeading1($text) {
    $t = xmlEsc(mb_strtoupper((string)$text, 'UTF-8'));
    return '<w:p>'
        . '<w:pPr><w:pStyle w:val="Heading1"/><w:jc w:val="center"/><w:spacing w:before="280" w:after="140" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr>'
        . '<w:t>' . $t . '</w:t></w:r></w:p>';
}

function wHeading2($text) {
    $t = xmlEsc($text);
    return '<w:p>'
        . '<w:pPr><w:pStyle w:val="Heading2"/><w:jc w:val="left"/><w:spacing w:before="220" w:after="90" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr>'
        . '<w:t>' . $t . '</w:t></w:r></w:p>';
}

function wHeading3($text) {
    $t = xmlEsc($text);
    return '<w:p>'
        . '<w:pPr><w:pStyle w:val="Heading3"/><w:jc w:val="left"/><w:spacing w:before="160" w:after="60" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
        . '<w:t>' . $t . '</w:t></w:r></w:p>';
}

function wPara($text, $align = 'both', $bold = false, $size = 24, $color = '000000', $spaceAfter = 160) {
    $t = xmlEsc($text);
    $bTag = $bold ? '<w:b/>' : '';
    return '<w:p>'
        . '<w:pPr><w:jc w:val="' . $align . '"/><w:spacing w:after="' . $spaceAfter . '" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>' . $bTag . '<w:color w:val="' . $color . '"/><w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/></w:rPr>'
        . '<w:t xml:space="preserve">' . $t . '</w:t></w:r></w:p>';
}

function wBullet($boldPrefix, $text) {
    $bp = xmlEsc($boldPrefix);
    $t  = xmlEsc($text);
    return '<w:p>'
        . '<w:pPr><w:jc w:val="both"/><w:ind w:left="480" w:hanging="260"/><w:spacing w:after="120" w:line="360" w:lineRule="auto"/></w:pPr>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t xml:space="preserve">• ' . $bp . ' </w:t></w:r>'
        . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t xml:space="preserve">' . $t . '</w:t></w:r>'
        . '</w:p>';
}

function wCodeBlock($lines) {
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/>'
         . '<w:tblBorders>'
         . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:left w:val="single" w:sz="12" w:space="0" w:color="000000"/>'
         . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '</w:tblBorders>'
         . '<w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="160" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="160" w:type="dxa"/></w:tblCellMar>'
         . '</w:tblPr>'
         . '<w:tblGrid><w:gridCol w:w="9360"/></w:tblGrid>'
         . '<w:tr><w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="F8F9FA"/></w:tcPr>';
    foreach ($lines as $line) {
        $xml .= '<w:p><w:pPr><w:spacing w:after="15" w:line="240" w:lineRule="auto"/></w:pPr>'
             . '<w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:color w:val="000000"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>'
             . '<w:t xml:space="preserve">' . xmlEsc($line) . '</w:t></w:r></w:p>';
    }
    $xml .= '</w:tc></w:tr></w:tbl><w:p><w:pPr><w:spacing w:after="120"/></w:pPr></w:p>';
    return $xml;
}

function wTable($headers, $rows, $headerFill = 'F2F2F2') {
    if ($headerFill === '1B396A' || $headerFill === '1E293B') {
        $headerFill = 'F2F2F2';
    }
    $numCols = max(1, count($headers));
    $colWidth = intval(9360 / $numCols);

    $xml = '<w:tbl>'
         . '<w:tblPr><w:tblW w:w="5000" w:type="pct"/>'
         . '<w:tblBorders>'
         . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
         . '</w:tblBorders>'
         . '<w:tblCellMar><w:top w:w="90" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar>'
         . '</w:tblPr>'
         . '<w:tblGrid>';
    for ($c = 0; $c < $numCols; $c++) {
        $xml .= '<w:gridCol w:w="' . $colWidth . '"/>';
    }
    $xml .= '</w:tblGrid>';

    $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($headers as $h) {
        $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="' . $headerFill . '"/></w:tcPr>'
             . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>'
             . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="21"/><w:szCs w:val="21"/></w:rPr>'
             . '<w:t xml:space="preserve">' . xmlEsc($h) . '</w:t></w:r></w:p></w:tc>';
    }
    $xml .= '</w:tr>';

    $idx = 0;
    foreach ($rows as $row) {
        $fill = 'FFFFFF';
        $xml .= '<w:tr>';
        $colIdx = 0;
        foreach ($row as $cell) {
            $isFirst = ($colIdx === 0);
            $bTag = $isFirst ? '<w:b/>' : '';
            $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/></w:tcPr>'
                 . '<w:p><w:pPr><w:jc w:val="both"/><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>'
                 . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>' . $bTag . '<w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
                 . '<w:t xml:space="preserve">' . xmlEsc($cell) . '</w:t></w:r></w:p></w:tc>';
            $colIdx++;
        }
        $xml .= '</w:tr>';
        $idx++;
    }
    $xml .= '</w:tbl><w:p><w:pPr><w:spacing w:after="140"/></w:pPr></w:p>';
    return $xml;
}

function wImage($rId, $docPrId, $cxEmu, $cyEmu, $caption = '') {
    $xml = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="120" w:after="80"/></w:pPr>'
         . '<w:r><w:drawing>'
         . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
         . '<wp:extent cx="' . $cxEmu . '" cy="' . $cyEmu . '"/>'
         . '<wp:docPr id="' . $docPrId . '" name="Picture ' . $docPrId . '"/>'
         . '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
         . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
         . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
         . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
         . '<pic:nvPicPr><pic:cNvPr id="' . $docPrId . '" name="img' . $docPrId . '.png"/><pic:cNvPicPr/></pic:nvPicPr>'
         . '<pic:blipFill><a:blip r:embed="' . $rId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
         . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cxEmu . '" cy="' . $cyEmu . '"/></a:xfrm>'
         . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
         . '</pic:pic></a:graphicData></a:graphic>'
         . '</wp:inline></w:drawing></w:r></w:p>';
    if ($caption !== '') {
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="40" w:after="160" w:line="240" w:lineRule="auto"/></w:pPr>'
             . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
             . '<w:t xml:space="preserve">' . xmlEsc($caption) . '</w:t></w:r></w:p>';
    }
    return $xml;
}

$body = '';

// ============================================================================
// PORTADA INSTITUCIONAL (ESTRUCTURA FORMAL PRACTICA 3_1_2)
// ============================================================================
$body .= wPara('SECRETARÍA DE EDUCACIÓN PÚBLICA', 'center', true, 24, '000000', 80);
$body .= wEmptyPara();
$body .= wPara('TECNOLÓGICO NACIONAL DE MÉXICO', 'center', true, 24, '000000', 80);
$body .= wEmptyPara();
$body .= wPara('INSTITUTO TECNOLÓGICO DE VERACRUZ.', 'center', true, 24, '000000', 120);
$body .= wEmptyPara();

$logoTecPath = $projectRoot . '/img/1764116533_Tecnologico_Nacional_de_Mexico.svg.png';
if (file_exists($logoTecPath)) {
    $body .= wImage('rIdImgLogoTec', 1, 2000000, 1072727, '');
}

$body .= wEmptyPara();
$body .= wPara('MATERIA:', 'center', true, 24, '000000', 50);
$body .= wEmptyPara();
$body .= wPara('ADMINISTRACIÓN Y TALLER DE BASES DE DATOS', 'center', false, 24, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('CARRERA:', 'center', true, 24, '000000', 50);
$body .= wEmptyPara();
$body .= wPara('INGENIERÍA EN SISTEMAS COMPUTACIONALES', 'center', false, 24, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('PROYECTO:', 'center', true, 24, '000000', 50);
$body .= wEmptyPara();
$body .= wPara('SNACKVER — PLATAFORMA WEB DE ENTREGA Y COORDINACIÓN DE COMIDA UNIVERSITARIA EN TIEMPO REAL', 'center', false, 24, '000000', 50);
$body .= wPara('(REPORTE TÉCNICO DE MODERNIZACIÓN NOSQL EN LA NUBE — UNIDAD 2)', 'center', false, 22, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('ALUMNOS:', 'center', true, 24, '000000', 50);
$body .= wEmptyPara();
$body .= wPara('JUAN VICENTE HERRERA VIVANCO', 'center', false, 24, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('PROFESOR TITULAR:', 'center', true, 24, '000000', 50);
$body .= wEmptyPara();
$body .= wPara('SERGIO RÍOS GONZÁLEZ', 'center', false, 24, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('MOTOR DE BD Y DESPLIEGUE:', 'center', true, 24, '000000', 50);
$body .= wPara('MONGODB ATLAS CLOUD (100% NOSQL) & RENDER DOCKER (HTTPS://SNACKVER.ME)', 'center', false, 22, '000000', 100);
$body .= wEmptyPara();

$body .= wPara('FECHA DE ENTREGA:', 'center', true, 24, '000000', 50);
$body .= wPara('OCTUBRE DE 2026', 'center', false, 24, '000000', 100);

$body .= wPageBreak();

// ============================================================================
// INDICE GENERAL
// ============================================================================
$body .= wHeading1('ÍNDICE GENERAL');
$body .= wPara('Estructura sistemática de temas, requisitos, diagramas de base de datos y vistas programadas con capturas de pantalla reales:');

$body .= wTable(
    ['SECCIÓN', 'TÍTULO DEL APARTADO', 'CONTENIDO TÉCNICO DESARROLLADO'],
    [
        ['Portada', 'Hoja de Presentación Institucional', 'Datos institucionales TecNM / ITV, materia, proyecto, alumno y docente.'],
        ['Índice', 'Índice General', 'Estructura sistemática del reporte técnico.'],
        ['Capítulo 1', '1. Introducción', 'Propósito de Snackver, limitaciones de MySQL y migración NoSQL.'],
        ['Capítulo 2', '2. Contenido: Implementación Técnica de los 8 Requisitos', 'Desarrollo de los 8 rubros de rúbrica y su operación en las vistas.'],
        ['  2.1', 'Requisito 1: Migración de SQL a MongoDB Atlas', '8 colecciones BSON, conector Singleton, comandos mongosh y CLI.'],
        ['  2.2', 'Requisito 2: Servicios de GitHub Student Developer Pack', 'Clúster MongoDB Atlas M0 y dominio snackver.me con SSL.'],
        ['  2.3', 'Requisito 3: Seguridad (Roles RBAC, Bcrypt y 2FA OTP 0 ms)', 'Sesiones por rol, claves Bcrypt, modal 2FA en 0 ms y auditoría.'],
        ['  2.4', 'Requisito 4: Cifrado en Tránsito y en Reposo', 'Tráfico HTTPS TLS 1.3 y cifrado en disco AES-256 en Atlas.'],
        ['  2.5', 'Requisito 5: Hardening de Red y Seguridad del Servidor', 'IP Access List, reglas .htaccess y variables .env protegidas.'],
        ['  2.6', 'Requisito 6: Consultas CRUD Nativas y Agregaciones', 'Operaciones insertOne, updateOne, pipelines de agregación y mongosh.'],
        ['  2.7', 'Requisito 7: Tiempo Real (0 ms), 36 Índices y Cloudinary', 'Sincronización MD5, creación de índices, explain() y Cloudinary.'],
        ['  2.8', 'Requisito 8: Despliegue en la Nube (Docker + Render + 24/7)', 'Dockerfile php:8.2-apache, Render CI/CD y Keep-Alive cron-job.org.'],
        ['Capítulo 3', '3. Diagramas de Documentos BSON (Colecciones Separadas)', '5 diagramas técnicos UML de estructuras documentales BSON.'],
        ['Capítulo 4', '4. Diagramas de Arquitectura de MongoDB y Nube', 'Topología de 3 Nodos en Atlas y Arquitectura Cloud en producción.'],
        ['Capítulo 5', '5. Diagramas de Modelo de Datos NoSQL y Relaciones', '3 flujos lineales: Pedidos, Catálogo y Seguridad + 36 Índices.'],
        ['Capítulo 6', '6. Catálogo de Vistas Programadas con Capturas de Pantalla', 'Documentación de interfaces reales, operación de usuarios y capturas del sistema.'],
        ['  6.1', 'Portada Principal y Buscador de Platillos (index.php)', 'Hero universitario, buscador global, carrusel y reseñas con captura.'],
        ['  6.2', 'Catálogo de Platillos y Menú por Categorías (Productos.php)', 'Cuadrícula responsiva, imágenes Cloudinary y existencias con captura.'],
        ['  6.3', 'Acceso y Verificación 2FA OTP en 0 ms (login.php)', 'Autenticación con Bcrypt, apertura instantánea de modal OTP con captura.'],
        ['  6.4', 'Registro de Cuentas y Validación Institucional (registro.php)', 'Creación de cuentas TecNM, activación y despacho 2FA con captura.'],
        ['  6.5', 'Vitrina Digital y Menú de Tienda (ver_tienda.php)', 'Perfil comercial del estudiante vendedor, platillos y valoraciones con captura.'],
        ['  6.6', 'Carrito de Compras y Control de Existencias (carrito.php)', 'Manipulación atómica de ítems, cálculo dinámico y stock con captura.'],
        ['  6.7', 'Historial de Pedidos y Validación SPEI (MisPedidos.php)', 'Seguimiento por semáforo, registro de transferencia y cancelación con captura.'],
        ['  6.8', 'Dashboard del Vendedor con KPIs en Vivo (dashboard_vendedor.php)', '3 métricas numéricas reactivas y campana de notificaciones con captura.'],
        ['  6.9', 'Inventario y Gestión de Platillos (Misplatillos.php)', 'Catálogo comercial propio, edición y baja de productos con captura.'],
        ['  6.10', 'Despacho de Pedidos en Tiempo Real (pedidos_pendientes.php)', 'Atención de comandas en vivo y transiciones de estado con captura.'],
        ['  6.11', 'Bitácora de Auditoría Forense (historial_usuarios.php)', 'Monitoreo de accesos, direcciones IP, navegadores y eventos con captura.'],
        ['  6.12', 'Módulos Complementarios del Sistema Web (24 Vistas)', 'Resumen de las interfaces auxiliares de administración y soporte.'],
        ['Capítulo 7', '7. Conclusión', 'Evaluación de latencia, cero JOINs, seguridad y alta disponibilidad.']
    ],
    '1B396A'
);

$body .= wPageBreak();

// ============================================================================
// CAPITULO 1: INTRODUCCION
// ============================================================================
$body .= wHeading1('1. INTRODUCCIÓN');
$body .= wPara('Snackver ("Comida entre universitarios") es una plataforma web desarrollada para solucionar la coordinación de pedidos y entregas de alimentos dentro del Instituto Tecnológico de Veracruz. Su propósito central es conectar a los estudiantes que emprenden vendiendo alimentos con sus compañeros y docentes, agilizando la compra de comida en los recesos académicos.');
$body .= wPara('En la Unidad 1 del curso, el sistema operaba sobre una base de datos relacional MySQL local compuesta por 10 tablas normalizadas. Al llevar el proyecto a un entorno en la nube con usuarios concurrentes, este esquema presentó limitaciones evidentes: cuellos de botella y lentitud al requerir múltiples JOINs para armar el historial de compras, un esquema rígido que complicaba cambios rápidos en los platillos, almacenamiento local de fotos que se borraban al reiniciar contenedores Docker en la nube, y la falta de mecanismos modernos de doble factor de autenticación (2FA).');
$body .= wPara('Para la Unidad 2, se realizó una modernización integral sustituyendo por completo el motor relacional por MongoDB Atlas, organizando la información en 8 colecciones documentales NoSQL optimizadas con 36 índices B-Tree. Con esto se eliminó el 100% de las sentencias SQL en el código. Además, se aprovecharon beneficios del GitHub Student Developer Pack (clúster gestionado en la nube con MongoDB Atlas M0 y dominio propio snackver.me con certificado SSL emitido por Namecheap), se integró Cloudinary CDN para la gestión escalable de imágenes, se implementó 2FA OTP instantáneo en 0 ms despachado por HTTPS hacia Google Apps Script, y se desplegó todo el entorno en un contenedor Docker en Render.');

// ============================================================================
// CAPITULO 2: CONTENIDO DE LOS 8 REQUISITOS
// ============================================================================
$body .= wHeading1('2. CONTENIDO: IMPLEMENTACIÓN TÉCNICA DE LOS 8 REQUISITOS');
$body .= wPara('A continuación se describe qué se construyó en cada uno de los ocho requisitos solicitados por la rúbrica y cómo operan dentro del sistema:');

// 2.1
$body .= wHeading2('2.1 Requisito 1: Migración de SQL a MongoDB Atlas');
$body .= wPara('Se transformó la arquitectura de datos de relacional a documental NoSQL para la base de datos de producción sistemawebsnackver:');
$body .= wBullet('1. Reestructuración Documental:', 'Las 10 tablas relacionales de MySQL se consolidaron en 8 colecciones BSON: usuarios, categorias, productos, pedidos, carritos, resenas, notificaciones e historial_usuarios. Se unificaron los tipos de usuario (cliente, vendedor y administrador) dentro de la colección usuarios diferenciados por el atributo "rol". La información del negocio del vendedor se integró como un subdocumento embebido (tienda), y las compras se almacenaron con sus platillos embebidos (items[]), evitando uniones de tablas.');
$body .= wBullet('2. Conector Oficial de MongoDB para PHP:', 'Se instaló la extensión nativa ext-mongodb v2.5 en PHP 8.2 y la librería oficial mongodb/mongodb ^2.5 mediante Composer, permitiendo manejar documentos BSON de forma nativa.');
$body .= wBullet('3. Patrón Singleton de Conexión:', 'En el archivo Back-end/conexion/conexion_mongo.php se implementó la clase ConexionMongo bajo el patrón de diseño Singleton. Esto asegura que exista una sola conexión activa hacia el clúster durante cada petición HTTP, reutilizando sockets y activando compresión de red Snappy y Zlib.');
$body .= wBullet('4. Herramientas de Migración y Carga de Datos:', 'Se desarrollaron scripts ejecutables en consola dentro de la carpeta tools/ (migrar_mysql_a_mongo.php, inicializar_solo_admin.php y seed_mongo_initial_data.php) para migrar los datos relacionales hacia MongoDB y sembrar datos de prueba completos.');
$body .= wBullet('5. Eliminación Total de SQL:', 'Todas las vistas y controladores del proyecto leen y escriben documentos NoSQL mediante el cliente oficial de MongoDB Atlas. Cero sentencias SELECT, INSERT o JOIN en producción.');
$body .= wPara('A continuación se muestra el comando de consola oficial de MongoDB Shell (mongosh) utilizado para conectarse al clúster en la nube con cifrado TLS 1.3, seleccionar la base de datos de producción y verificar el estado general de las colecciones migradas:');

$body .= wCodeBlock([
    '// 1. Conexión segura por consola mongosh hacia el clúster de producción en MongoDB Atlas:',
    'mongosh "mongodb+srv://cluster0.ceqk0dl.mongodb.net/sistemawebsnackver?retryWrites=true&w=majority" --username admin_snackver',
    '',
    '// 2. Selección de la base de datos y comprobación de colecciones migradas:',
    'use sistemawebsnackver',
    'show collections',
    'db.stats()'
]);

// 2.2
$body .= wHeading2('2.2 Requisito 2: Servicios de GitHub Student Developer Pack');
$body .= wPara('Se activaron y configuraron dos servicios incluidos en el paquete de beneficios para estudiantes de GitHub:');
$body .= wBullet('1. Base de Datos en la Nube con MongoDB Atlas:', 'Se aprovisionó el clúster en la nube Cluster0 en la región us-east-1 (AWS), configurado como un Replica Set distribuido de 3 nodos (1 primario y 2 secundarios) con respaldo continuo y cifrado en disco.');
$body .= wBullet('2. Dominio Propio y Certificado SSL con Namecheap:', 'Se registró y vinculó el dominio oficial snackver.me, configurando los registros DNS correspondientes para apuntar al servidor de la aplicación y activando certificados SSL/TLS 1.3.');

// 2.3
$body .= wHeading2('2.3 Requisito 3: Seguridad (Roles RBAC, Bcrypt y 2FA OTP 0 ms)');
$body .= wPara('Se programó un esquema de seguridad por capas para proteger las cuentas y la información de la comunidad:');
$body .= wBullet('1. Control de Acceso Basado en Roles (RBAC):', 'Cada usuario posee un atributo rol ("cliente", "vendedor", "admin") en su documento de MongoDB. Al iniciar sesión, el sistema valida este rol en la sesión del servidor ($_SESSION["rol"]) antes de permitir el acceso a las vistas privadas.');
$body .= wBullet('2. Cifrado de Contraseñas con Bcrypt:', 'Las contraseñas de todos los usuarios se almacenan procesadas con password_hash(PASSWORD_BCRYPT) y se verifican con password_verify(). En ningún punto se guardan contraseñas en texto claro.');
$body .= wBullet('3. Autenticación en Dos Pasos (2FA OTP) en Dos Fases (0 ms):', 'Para evitar que el usuario espere a que el servidor envíe un correo electrónico antes de ver la pantalla de verificación, el flujo se dividió en dos fases: en la Fase 1 (fase=generar), PHP genera un código numérico aleatorio de 6 dígitos con vigencia de 15 minutos, lo guarda en el subdocumento verificacion_otp en MongoDB en ~40 ms y abre la ventana modal (#otpModalOverlay) en la pantalla de inmediato (0 ms). En la Fase 2 (fase=enviar_smtp), el navegador solicita en segundo plano el envío del correo.');
$body .= wBullet('4. Despacho de Correo por HTTPS en Puerto 443:', 'Dado que las plataformas en la nube como Render bloquean los puertos SMTP tradicionales (25, 465, 587) en cuentas gratuitas, se construyó EmailService.php para enviar los correos mediante peticiones HTTPS seguras por el puerto 443 hacia un Web App de Google Apps Script vinculado a Gmail.');
$body .= wBullet('5. Bitácora de Auditoría Forense:', 'Cada inicio de sesión, verificación OTP, cambio de clave o eliminación se registra de forma inmutable en la colección historial_usuarios con la dirección IP, el navegador y la fecha y hora exacta en formato UTC.');

// 2.4
$body .= wHeading2('2.4 Requisito 4: Cifrado en Tránsito (TLS 1.3) y en Reposo (AES-256)');
$body .= wBullet('Cifrado en Tránsito:', 'Toda la comunicación web se realiza forzosamente por HTTPS bajo protocolo TLS 1.3 en https://snackver.me. La conexión entre el servidor web y MongoDB Atlas viaja cifrada mediante sockets TLS/SSL (tls=true), al igual que las comunicaciones con Cloudinary y Google Apps Script.');
$body .= wBullet('Cifrado en Reposo:', 'Los datos de las 8 colecciones documentales se encuentran protegidos en disco en los servidores de MongoDB Atlas bajo el motor WiredTiger mediante cifrado de grado bancario AES-256.');

// 2.5
$body .= wHeading2('2.5 Requisito 5: Hardening de Red y Seguridad del Servidor');
$body .= wBullet('Control de Accesos al Clúster (IP Access List):', 'El acceso a MongoDB Atlas se encuentra restringido a nivel de red y protegido por autenticación segura de usuarios con el mecanismo SCRAM-SHA-256.');
$body .= wBullet('Blindaje con Apache (.htaccess):', 'Se configuraron reglas de servidor que bloquean el acceso directo a archivos sensibles como .env, .git y directorios internos con error 403 Forbidden, deshabilitan la visualización de directorios (Options -Indexes) y activan compresión Gzip (mod_deflate).');
$body .= wBullet('Aislamiento de Variables de Configuración:', 'Las contraseñas de base de datos y llaves de API nunca están escritas dentro del código fuente; en desarrollo se leen desde un archivo .env protegido mediante vlucas/phpdotenv y en producción se configuran como variables de entorno seguras en Render.');

// 2.6
$body .= wHeading2('2.6 Requisito 6: Consultas CRUD Nativas NoSQL y Agregaciones');
$body .= wPara('A continuación se presenta la correspondencia de las operaciones nativas de MongoDB utilizadas en los módulos del sistema:');
$body .= wTable(
    ['OPERACIÓN NoSQL', 'MÉTODOS Y OPERADORES DE MONGODB', 'VISTAS Y MÓDULOS DONDE SE EJECUTA'],
    [
        ['CREATE (Inserción)', 'insertOne() con ObjectId y UTCDateTime', 'registro.php (usuarios), nuevoplatillo.php (productos), checkout.php (pedidos con items[]), categorias.php, resenas.php, HistorialService y NotificacionService.'],
        ['READ (Consulta)', 'find(), findOne(), countDocuments(), Regex, sort', 'index.php, Productos.php, resultados_busqueda.php, BuscarTienda.php, ver_tienda.php, MisPedidos.php, pedidos_pendientes.php, clientes.php, historial_usuarios.php.'],
        ['AGGREGATE (Analítica)', 'aggregate() con $match, $group, $sum, $avg, $facet', 'dashboard_admin.php (métricas globales con $facet), dashboard_vendedor.php (3 KPIs en tiempo real), index.php y ver_tienda.php (promedios de calificación).'],
        ['UPDATE (Actualización)', 'updateOne(), updateMany() con $set, $inc, $push, $pull', 'procesar_pedido.php ($inc en stock), cancelar_pedido_cliente.php ($inc restauración), carrito.php ($push/$pull items[]), editar_platillo.php, MiTienda.php, Miperfil.php.'],
        ['DELETE (Eliminación)', 'deleteOne(), deleteMany() en cascada', 'eliminar_cliente.php (usuario + carrito), eliminar_vendedor.php (vendedor + platillos), Misplatillos.php (baja de platillo).']
    ],
    '1B396A'
);

$body .= wPara('A continuación se presentan los comandos documentales de mongosh implementados en los controladores del Back-end para las operaciones críticas del ciclo comercial, evidenciando la manipulación atómica de arreglos embebidos ($push, $pull) y el control de inventario ($inc):');

$body .= wCodeBlock([
    '// 1. CREATE: Inserción de pedido congelando los platillos en items[] (checkout.php - Cero JOINs)',
    'db.pedidos.insertOne({',
    '  id_pedido_num: 1045,',
    '  id_cliente: ObjectId("6718a910e4b0a1c2d3e4f502"),',
    '  id_vendedor: ObjectId("6718a910e4b0a1c2d3e4f501"),',
    '  items: [',
    '    { id_producto: ObjectId("6718aa00e4b0a1c2d3e4f600"), nombreproducto: "Torta de Cochinita", cantidad: 2, precio_unitario: 45.0, importe: 90.0 }',
    '  ],',
    '  total: 90.0,',
    '  estadopedido: "En preparación",',
    '  metodopago: "Transferencia SPEI",',
    '  fechapedido: new Date()',
    '})'
]);

$body .= wCodeBlock([
    '// 2. UPDATE: Descuento atómico de existencias al confirmar la compra (procesar_pedido.php)',
    'db.productos.updateOne(',
    '  { _id: ObjectId("6718aa00e4b0a1c2d3e4f600"), stock_disponible: { $gte: 2 } },',
    '  { $inc: { stock_disponible: -2 } }',
    ')',
    '',
    '// 3. UPDATE: Agregado atómico de platillo al carrito único del estudiante (carrito.php)',
    'db.carritos.updateOne(',
    '  { id_cliente: ObjectId("6718a910e4b0a1c2d3e4f502") },',
    '  { $push: { items: { id_producto: ObjectId("6718aa00e4b0a1c2d3e4f600"), cantidad: 1, agregado_en: new Date() } } }',
    ')'
]);

$body .= wPara('Asimismo, la plataforma implementa pipelines de agregación nativa (aggregate) para calcular analítica en tiempo real y promedios de calificación comunitaria sin sobrecargar el servidor web:');

$body .= wCodeBlock([
    '// 4. AGGREGATE: Cálculo de promedio de calificación y total de opiniones (index.php / ver_tienda.php)',
    'db.resenas.aggregate([',
    '  { $match: { id_producto: ObjectId("6718aa00e4b0a1c2d3e4f600") } },',
    '  { $group: {',
    '      _id: "$id_producto",',
    '      calificacion_promedio: { $avg: "$calificacion" },',
    '      total_opiniones: { $sum: 1 }',
    '    }',
    '  }',
    '])',
    '',
    '// 5. AGGREGATE: Cálculo de KPIs del Vendedor en vivo (dashboard_vendedor.php)',
    'db.pedidos.aggregate([',
    '  { $match: { id_vendedor: ObjectId("6718a910e4b0a1c2d3e4f501"), estadopedido: { $ne: "Cancelado" } } },',
    '  { $group: {',
    '      _id: "$id_vendedor",',
    '      pedidos_atendidos: { $sum: 1 },',
    '      ingresos_totales: { $sum: "$total" }',
    '    }',
    '  }',
    '])'
]);

// 2.7
$body .= wHeading2('2.7 Requisito 7: Tiempo Real (0 ms), 36 Índices y Cloudinary CDN');
$body .= wBullet('Sincronización Inteligente por Hash MD5:', 'Se programó un motor reactivo en sincronizar_tiempo_real.php y tiempo_real.js que monitorea el estado de la vista cada 2 segundos. El servidor calcula un hash MD5 de los datos; si no hay cambios (status="unchanged"), no se transfiere contenido ni se toca el DOM. Si hay cambios, la vista se refresca de inmediato en 0 ms sin recargar la página.');
$body .= wBullet('Campana de Alertas y Métricas Reactivas:', 'El script notificaciones.js consulta cada 1.5 segundos para mantener actualizados el contador de la campana, la lista de pedidos nuevos y los 3 indicadores numéricos (KPIs) del vendedor.');
$body .= wBullet('Gestión y Optimización de Imágenes con Cloudinary:', 'Al seleccionar una foto de comida, el navegador muestra una vista previa instantánea con URL.createObjectURL y la comprime a través de un elemento HTML5 <canvas> a ~100 KB antes de enviarla. La imagen se guarda en Cloudinary CDN y se entrega optimizada en formatos WebP/AVIF con f_auto,q_auto.');
$body .= wPara('A continuación se detallan los comandos de mongosh empleados para la creación de los índices B-Tree en MongoDB Atlas, así como el comando de diagnóstico explain() que demuestra la sustitución de escaneos completos (COLLSCAN) por búsquedas indexadas (IXSCAN):');

$body .= wCodeBlock([
    '// 1. Creación de índices B-Tree de rendimiento en consola mongosh:',
    'db.usuarios.createIndex({ email: 1 }, { unique: true, name: "uniq_email" })',
    'db.carritos.createIndex({ id_cliente: 1 }, { unique: true, name: "uniq_carrito_cliente" })',
    'db.pedidos.createIndex({ id_vendedor: 1, estadopedido: 1, fechapedido: -1 }, { name: "idx_pedidos_ven_est_fecha" })',
    'db.categorias.createIndex({ estado: 1, nombrecategoria: 1 }, { name: "idx_cat_estado_nombre" })',
    '',
    '// 2. Comprobación del plan de ejecución para verificar el uso del índice (IXSCAN en 0 ms):',
    'db.pedidos.find({',
    '  id_vendedor: ObjectId("6718a910e4b0a1c2d3e4f501"),',
    '  estadopedido: "En preparación"',
    '}).sort({ fechapedido: -1 }).explain("executionStats")'
]);

// 2.8
$body .= wHeading2('2.8 Requisito 8: Despliegue en la Nube (Docker + Render + Keep-Alive 24/7)');
$body .= wBullet('Contenedor Docker para Producción:', 'Se construyó un Dockerfile sobre la imagen oficial php:8.2-apache instalando las extensiones PECL requeridas (mongodb, gd, zip, curl) y configurando los módulos de Apache.');
$body .= wBullet('Ajustes PHP de Producción (snackver.ini):', 'Se configuró output_buffering = 4096 y display_errors = Off en /usr/local/etc/php/conf.d/snackver.ini para asegurar que los controladores de tiempo real siempre entreguen respuestas JSON limpias sin advertencias.');
$body .= wBullet('Repositorio Privado y Despliegue Continuo (CI/CD):', 'El repositorio de código fuente se mantiene en modalidad Privada en GitHub para proteger el desarrollo. Render se conecta directamente mediante su aplicación oficial Render GitHub App y despliega automáticamente cualquier cambio en la rama main.');
$body .= wBullet('Disponibilidad Continua 24/7:', 'Se configuró un monitor programado en cron-job.org que envía una petición HTTP al sitio cada 10 minutos, evitando que la instancia gratuita de Render se suspenda por inactividad.');

$body .= wPageBreak();

// ============================================================================
// CAPITULO 3: DIAGRAMAS DE DOCUMENTOS BSON (COLECCIONES SEPARADAS)
// ============================================================================
$body .= wHeading1('3. DIAGRAMAS DE DOCUMENTOS BSON Y SUBDOCUMENTOS EMBEBIDOS (SEPARADOS POR COLECCIÓN)');
$body .= wPara('A continuación se presentan los diagramas de estructura BSON para cada colección en formato UML técnico puro, con tipografía clara y relaciones directas:');

// 3.1 usuarios
$body .= wHeading2('3.1 Estructura del Documento usuarios y Subdocumentos Embebidos (1:1)');
$body .= wImage('rIdImgDiag3_1', 10, 5600000, 1701960, 'Figura 3.1 — Colección usuarios: Estructura BSON y Subdocumentos Embebidos tienda, verificacion_otp y recuperacion_otp (Embedding 1:1).');
$body .= wPara('La colección usuarios reúne a clientes, vendedores y administradores en una sola estructura. En lugar de tablas separadas, la información de la tienda del alumno y los códigos de seguridad para 2FA se guardan como subdocumentos embebidos dentro de la misma cuenta, permitiendo leer todo el perfil en una sola operación:');

$body .= wCodeBlock([
    '// Documento representativo BSON en la colección "usuarios":',
    '{',
    '  "_id": { "$oid": "6718a910e4b0a1c2d3e4f501" },',
    '  "nombre": "Carlos", "apellido": "Martínez",',
    '  "email": "carlos.vendedor@veracruz.tecnm.mx",',
    '  "password_hash": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",',
    '  "rol": "vendedor", "estado": "activo",',
    '  "tienda": {',
    '    "nombre_negocio": "Tacos y Tortas El Inge",',
    '    "descripcion": "Tortas y tacos junto al Edificio K.",',
    '    "direccion": "Banca 4 frente al Lab. de Cómputo",',
    '    "foto_negocio": "https://res.cloudinary.com/demo/image/upload/f_auto,q_auto/tienda.webp"',
    '  },',
    '  "verificacion_otp": { "codigo": "482910", "expira": { "$date": "2026-10-12T23:30:00Z" } }',
    '}'
]);

// 3.2 pedidos
$body .= wHeading2('3.2 Estructura del Documento pedidos y su Arreglo Embebido items[] (Cero JOINs)');
$body .= wImage('rIdImgDiag3_2', 11, 5600000, 1830769, 'Figura 3.2 — Colección pedidos: Estructura BSON y Arreglo Embebido items[] (Snapshot Histórico 1:N, Cero JOINs).');
$body .= wPara('En la colección pedidos se eliminó la necesidad de tablas secundarias de detalle. Cada pedido contiene un arreglo embebido llamado items[] donde se guardan el nombre, la cantidad y el precio exacto al momento de la compra. Esto garantiza que si el vendedor cambia de precio el platillo en el futuro, los pedidos anteriores no se alteran y el historial se consulta en una sola lectura sin JOINs:');

$body .= wCodeBlock([
    '// Documento representativo BSON en la colección "pedidos":',
    '{',
    '  "_id": { "$oid": "6718b240e4b0a1c2d3e4f888" },',
    '  "id_pedido_num": 1042,',
    '  "id_cliente": { "$oid": "6718a910e4b0a1c2d3e4f502" },',
    '  "id_vendedor": { "$oid": "6718a910e4b0a1c2d3e4f501" },',
    '  "items": [',
    '    { "id_producto": { "$oid": "6718aa00e4b0a1c2d3e4f600" }, "nombreproducto": "Torta Cochinita", "cantidad": 2, "precio_unitario": 45.0, "importe": 90.0 }',
    '  ],',
    '  "total": 90.0, "estadopedido": "En preparación", "metodopago": "Transferencia SPEI"',
    '}'
]);

// 3.3 carritos
$body .= wHeading2('3.3 Estructura del Documento carritos y su Arreglo Embebido items[] (0..N)');
$body .= wImage('rIdImgDiag3_3', 12, 5600000, 1238461, 'Figura 3.3 — Colección carritos: Estructura BSON y Arreglo Embebido items[] (Gestión Dinámica de Platillos).');
$body .= wPara('Cada estudiante cuenta con un único carrito activo garantizado por el índice único uniq_carrito_cliente ({ id_cliente: 1 }). Los platillos seleccionados se agregan o quitan atómicamente dentro del arreglo items[] usando los operadores de MongoDB $push, $pull y $set.');

// 3.4 catalogo
$body .= wHeading2('3.4 Documentos de Catálogo Comercial: productos y categorias');
$body .= wImage('rIdImgDiag3_4', 13, 5600000, 1425454, 'Figura 3.4 — Colecciones de Catálogo: productos (Inventario y Stock Atómico) y categorias (Clasificación del Menú).');
$body .= wPara('La colección productos maneja los platillos publicados por cada vendedor, sus existencias en tiempo real y la foto en Cloudinary. El inventario se descuenta de forma segura con el operador atómico $inc. La colección categorias organiza los tipos de comida con un índice compuesto para mostrar las categorías activas de inmediato.');

// 3.5 auditoria e interaccion
$body .= wHeading2('3.5 Documentos de Interacción y Trazabilidad: resenas, notificaciones e historial_usuarios');
$body .= wImage('rIdImgDiag3_5', 14, 5760000, 1241379, 'Figura 3.5 — Colecciones de Interacción y Auditoría: resenas, notificaciones e historial_usuarios.');
$body .= wPara('La colección resenas permite que los alumnos califiquen a los vendedores con estrellas y comentarios (públicos o anónimos). notificaciones alimenta la campana de alertas en tiempo real, e historial_usuarios registra los eventos de seguridad para auditoría.');

$body .= wPageBreak();

// ============================================================================
// CAPITULO 4: DIAGRAMAS DE ARQUITECTURA
// ============================================================================
$body .= wHeading1('4. DIAGRAMAS DE ARQUITECTURA DE MONGODB Y NUBE');
$body .= wPara('A continuación se ilustra la arquitectura de la base de datos distribuida y el flujo de los servicios en la nube:');

$body .= wHeading2('4.1 Diagrama de Arquitectura Interna del Clúster MongoDB Atlas (Replica Set de 3 Nodos)');
$body .= wImage('rIdImgDiag4_arch1', 20, 5670000, 1800000, 'Figura 4.1 — Arquitectura Interna de MongoDB Atlas: Replica Set de 3 Nodos, Motor WiredTiger y Cifrado AES-256.');
$body .= wBullet('Capa de Aplicación:', 'PHP 8.2 bajo el conector Singleton ConexionMongo, con compresión Snappy/Zlib y confirmación de escritura mayoritaria (w=majority).');
$body .= wBullet('Perímetro de Seguridad:', 'Cadena DNS segura mongodb+srv://, lista de acceso por IP, credenciales SCRAM-SHA-256 y canal cifrado TLS 1.3.');
$body .= wBullet('Replica Set de 3 Nodos:', 'Un nodo Primario que procesa todas las lecturas y escrituras, replicando los cambios mediante el registro Oplog a dos nodos Secundarios con verificación periódica (heartbeat cada 2s) y recuperación automática ante fallas.');
$body .= wBullet('Motor de Almacenamiento WiredTiger:', 'Administra los 36 índices B-Tree en memoria RAM y aplica compresión de disco y cifrado AES-256 en reposo.');

$body .= wHeading2('4.2 Diagrama de Arquitectura Cloud End-to-End en Producción (snackver.me)');
$body .= wImage('rIdImgDiag4_arch2', 21, 5800000, 1581818, 'Figura 4.2 — Arquitectura Cloud End-to-End en Producción (Namecheap DNS/SSL + Render Docker + MongoDB Atlas + Cloudinary + Puente HTTPS 443).');
$body .= wBullet('Acceso Seguro del Usuario:', 'Los estudiantes ingresan mediante el dominio oficial https://snackver.me administrado en Namecheap con certificado SSL TLS 1.3.');
$body .= wBullet('Servidor de Aplicación en la Nube:', 'Contenedor Docker sobre php:8.2-apache en Render Web Services con compresión mod_deflate y reglas de seguridad en .htaccess.');
$body .= wBullet('Servicios Integrados en la Nube:', 'MongoDB Atlas (datos NoSQL), Cloudinary CDN (entrega de imágenes en formatos WebP/AVIF) y Google Apps Script (envío de correos 2FA por HTTPS en puerto 443).');
$body .= wBullet('Disponibilidad 24/7:', 'Monitor en cron-job.org que envía una petición cada 10 minutos para mantener el servidor web activo continuamente.');

$body .= wPageBreak();

// ============================================================================
// CAPITULO 5: MODELO DE DATOS NoSQL Y RELACIONES (3 FLUJOS SEPARADOS)
// ============================================================================
$body .= wHeading1('5. DIAGRAMAS DE MODELO DE DATOS NoSQL Y RELACIONES (3 MÓDULOS SEPARADOS)');
$body .= wPara('El modelo de datos NoSQL se divide en tres flujos funcionales para facilitar su comprensión, complementados por la tabla comparativa de migración y el catálogo de 36 índices:');

// 5.1 Flujo Transaccional
$body .= wHeading2('5.1 Módulo Transaccional: Flujo de Compras, Carrito y Pedidos (Cero JOINs)');
$body .= wImage('rIdImgDiag5_1', 30, 5800000, 996875, 'Figura 5.1 — Módulo Transaccional NoSQL: Flujo de Compras entre usuarios, carritos, pedidos y productos.');
$body .= wBullet('Relación 1:1 Cliente a Carrito:', 'Garantizada por el índice único uniq_carrito_cliente ({ id_cliente: 1 }), evitando carritos duplicados.');
$body .= wBullet('Checkout Inmutable:', 'Al confirmar la compra, los productos del carrito se copian directamente dentro del arreglo items[] en pedidos, congelando los precios sin depender de consultas posteriores a productos.');
$body .= wBullet('Descuento Atómico de Existencias ($inc):', 'El stock de los alimentos se descuenta de inmediato con el operador $inc: { stock_disponible: -cantidad }. Si el alumno cancela el pedido, el sistema devuelve las existencias automáticamente con el valor positivo.');

// 5.2 Flujo Catálogo y Reseñas
$body .= wHeading2('5.2 Módulo de Catálogo y Valoraciones: Categorías, Platillos y Reseñas');
$body .= wImage('rIdImgDiag5_2', 31, 5760000, 1620000, 'Figura 5.2 — Módulo de Catálogo y Reseñas: Clasificación, Publicación de Alimentos y Evaluación Comunitaria.');
$body .= wBullet('Clasificación:', 'Cada producto hace referencia a su id_categoria, permitiendo clasificar los platillos en el menú.');
$body .= wBullet('Publicación del Vendedor:', 'Los platillos se vinculan mediante id_vendedor con el usuario correspondiente y guardan la URL segura de Cloudinary.');
$body .= wBullet('Opiniones y Calificaciones:', 'La colección resenas vincula al cliente, al vendedor y al platillo, permitiendo calcular el promedio de estrellas mediante pipelines de agregación ($avg).');

// 5.3 Flujo Seguridad y Alertas
$body .= wHeading2('5.3 Módulo de Seguridad y Alertas: Auditoría Forense y Notificaciones en Tiempo Real');
$body .= wImage('rIdImgDiag5_3', 32, 5700000, 1567500, 'Figura 5.3 — Módulo de Seguridad y Alertas: Auditoría Forense y Notificaciones Reactivas.');
$body .= wBullet('Auditoría Forense:', 'Cada acción importante (inicio de sesión, cambio de clave, validación 2FA o eliminación) se guarda en historial_usuarios con la dirección IP y el navegador.');
$body .= wBullet('Alertas al Instante:', 'Cuando cambia el estado de un pedido, se inserta una alerta en notificaciones y el script notificaciones.js actualiza la campana del usuario en 1.5 segundos.');

// 5.4 Comparativa MySQL vs MongoDB
$body .= wHeading2('5.4 Comparativa de Transformación: Modelo Relacional (MySQL) vs Modelo de Datos NoSQL (MongoDB)');
$body .= wTable(
    ['MODELO ANTERIOR (10 TABLAS MYSQL)', 'MODELO ACTUAL (8 COLECCIONES MONGODB)', 'TRANSFORMACIÓN Y BENEFICIO NOSQL OBTENIDO'],
    [
        ['cliente + vendedor + Admin', '1. usuarios', 'Unificación de cuentas con discriminador "rol" y subdocumentos embebidos tienda y OTP.'],
        ['categoriaproducto', '2. categorias', 'Clasificación ágil referenciada por id_categoria con índice compuesto por estado y nombre.'],
        ['producto', '3. productos', 'Catálogo con imágenes en Cloudinary CDN y control de existencias atómico con $inc.'],
        ['pedido + detallepedido', '4. pedidos', 'Arreglo embebido items[] dentro de cada pedido. Cero JOINs y precios congelados.'],
        ['carrito + detallecarrito', '5. carritos', 'Arreglo embebido items[] en un único documento por alumno (uniq_carrito_cliente).'],
        ['resena', '6. resenas', 'Calificaciones con soporte para comentarios públicos o anónimos (es_anonimo).'],
        ['notificaciones', '7. notificaciones', 'Alertas de compras indexadas por destinatario y estado de lectura.'],
        ['(No existía en MySQL)', '8. historial_usuarios', 'Bitácora de seguridad forense con IP, navegador y fecha UTC.']
    ],
    '1E293B'
);

// 5.5 Catálogo Completo de 36 Índices
$body .= wHeading2('5.5 Catálogo Completo de los 36 Índices en MongoDB Atlas');
$body .= wPara('Para garantizar respuestas inmediatas y evitar escaneos completos de colecciones (COLLSCAN), se configuraron los siguientes 36 índices B-Tree en MongoDB Atlas:');

$body .= wTable(
    ['#', 'COLECCIÓN', 'NOMBRE DEL ÍNDICE', 'CLAVES BSON', 'TIPO', 'CONSULTA O VISTA QUE ACELERA'],
    [
        ['1', 'usuarios', 'uniq_email', '{ email: 1 }', 'Único', 'Login instantáneo y correos no duplicados.'],
        ['2', 'usuarios', 'idx_rol', '{ rol: 1 }', 'Simple', 'Filtrado por rol (cliente/vendedor/admin).'],
        ['3', 'usuarios', 'idx_usuarios_rol_estado', '{ rol: 1, estado: 1 }', 'Compuesto', 'Paneles de administración y tiendas activas.'],
        ['4', 'categorias', 'idx_nombrecategoria', '{ nombrecategoria: 1 }', 'Simple', 'Búsqueda por nombre de categoría.'],
        ['5', 'categorias', 'idx_cat_estado_nombre', '{ estado: 1, nombrecategoria: 1 }', 'Compuesto', 'Categorías activas ordenadas en index.php.'],
        ['6', 'productos', 'idx_vendedor', '{ id_vendedor: 1 }', 'Simple', 'Catálogo propio del vendedor en Misplatillos.php.'],
        ['7', 'productos', 'idx_categoria', '{ id_categoria: 1 }', 'Simple', 'Filtrado por categoría en Productos.php.'],
        ['8', 'productos', 'idx_activo', '{ activo: 1 }', 'Simple', 'Exclusión de platillos inactivos.'],
        ['9', 'productos', 'idx_prod_vendedor_stock', '{ id_vendedor: 1, stock_disponible: 1 }', 'Compuesto', 'Inventario y existencias por vendedor.'],
        ['10', 'productos', 'idx_prod_cat_stock', '{ id_categoria: 1, stock_disponible: 1 }', 'Compuesto', 'Platillos disponibles por categoría.'],
        ['11', 'productos', 'idx_prod_estado', '{ estado: 1 }', 'Simple', 'Estado en consultas de administración.'],
        ['12', 'productos', 'idx_prod_estado_vendedor', '{ estado: 1, id_vendedor: 1 }', 'Compuesto', 'Platillos por vendedor y estado.'],
        ['13', 'pedidos', 'idx_pedidos_cliente', '{ id_cliente: 1 }', 'Simple', 'Historial de compras del estudiante.'],
        ['14', 'pedidos', 'idx_pedidos_vendedor', '{ id_vendedor: 1 }', 'Simple', 'Bandeja de pedidos del vendedor.'],
        ['15', 'pedidos', 'idx_pedidos_fecha', '{ fechapedido: -1 }', 'Simple', 'Orden cronológico global de órdenes.'],
        ['16', 'pedidos', 'idx_pedidos_cli_notif', '{ id_cliente: 1, notificado_cliente: 1 }', 'Compuesto', 'Sondeo reactivo de estado para cliente.'],
        ['17', 'pedidos', 'idx_pedidos_ven_notif', '{ id_vendedor: 1, notificado_vendedor: 1 }', 'Compuesto', 'Detección reactiva de nuevos pedidos.'],
        ['18', 'pedidos', 'idx_pedidos_cli_fecha', '{ id_cliente: 1, fechapedido: -1 }', 'Compuesto', 'Historial del cliente en MisPedidos.php.'],
        ['19', 'pedidos', 'idx_pedidos_ven_estado', '{ id_vendedor: 1, estadopedido: 1 }', 'Compuesto', 'KPI de pedidos pendientes del vendedor.'],
        ['20', 'pedidos', 'idx_pedidos_estado', '{ estado: 1 }', 'Simple', 'Agregaciones en dashboard_admin.php.'],
        ['21', 'pedidos', 'idx_pedidos_ven_fecha', '{ id_vendedor: 1, fechapedido: -1 }', 'Compuesto', 'Órdenes recientes en dashboard_vendedor.php.'],
        ['22', 'pedidos', 'idx_pedidos_ven_est_fecha', '{ id_vendedor: 1, estadopedido: 1, fechapedido: -1 }', 'Compuesto', 'Filtro en 0 ms en pedidos_pendientes.php.'],
        ['23', 'pedidos', 'idx_pedidos_cli_est_fecha', '{ id_cliente: 1, estadopedido: 1, fechapedido: -1 }', 'Compuesto', 'Filtro en 0 ms en MisPedidos.php.'],
        ['24', 'carritos', 'uniq_carrito_cliente', '{ id_cliente: 1 }', 'Único', 'Un único carrito activo por estudiante.'],
        ['25', 'carritos', 'idx_carritos_cliente', '{ id_cliente: 1 }', 'Simple', 'Lectura y actualización en carrito.php.'],
        ['26', 'resenas', 'idx_resena_producto', '{ id_producto: 1 }', 'Simple', 'Promedios y opiniones por platillo.'],
        ['27', 'resenas', 'idx_resena_cliente', '{ id_cliente: 1 }', 'Simple', 'Reseñas emitidas en resenas.php.'],
        ['28', 'resenas', 'idx_resenas_fecha', '{ fecharesena: -1 }', 'Simple', 'Carrusel de reseñas en index.php.'],
        ['29', 'historial_usuarios', 'idx_historial_fecha', '{ fecha: -1 }', 'Simple', 'Auditoría cronológica descendente.'],
        ['30', 'historial_usuarios', 'idx_historial_usuario_fecha', '{ id_usuario: 1, fecha: -1 }', 'Compuesto', 'Auditoría por usuario específico.'],
        ['31', 'historial_usuarios', 'idx_historial_accion_fecha', '{ accion: 1, fecha: -1 }', 'Compuesto', 'Filtro por acción (login, 2FA, delete).'],
        ['32', 'notificaciones', 'id_usuario_1', '{ id_usuario: 1 }', 'Simple', 'Recuperación de alertas por usuario.'],
        ['33', 'notificaciones', 'fecha_-1', '{ fecha: -1 }', 'Simple', 'Orden cronológico descendente.'],
        ['34', 'notificaciones', 'idx_notif_usr_tipo_fecha', '{ id_usuario: 1, tipo_usuario: 1, fecha: -1 }', 'Compuesto', 'Campana de alertas sin ordenamiento en RAM.'],
        ['35', 'notificaciones', 'idx_notif_usrstr_tipo_fecha', '{ id_usuario_str: 1, tipo_usuario: 1, fecha: -1 }', 'Compuesto', 'Búsquedas por ID de usuario en texto.'],
        ['36', 'notificaciones', 'idx_notif_usr_leida', '{ id_usuario: 1, tipo_usuario: 1, leida: 1 }', 'Compuesto', 'Conteo inmediato de badge rojo no leído.']
    ],
    '1B396A'
);

$body .= wPageBreak();

// ============================================================================
// CAPITULO 6: CATALOGO DE VISTAS PROGRAMADAS CON CAPTURAS DE PANTALLA REALES
// ============================================================================
$body .= wHeading1('6. CATÁLOGO DE VISTAS PROGRAMADAS DEL SISTEMA (FRONT-END Y BACK-END EN PRODUCCIÓN — SUSTITUCIÓN DE VISTAS DE FIGMA)');
$body .= wPara('A continuación se documentan las vistas reales del sistema Snackver programadas en producción (https://snackver.me). En cada apartado se explica qué se realizó en la pantalla, qué información observa el usuario, cómo interactúa y qué operaciones en MongoDB Atlas se ejecutan en segundo plano, acompañado de su respectiva captura de pantalla:');

// 6.1 Portada Principal (index.php)
$body .= wHeading2('6.1 Portada Principal y Buscador Global de Platillos (index.php)');
$body .= wPara('Se programó la página de inicio institucional de Snackver como el centro de descubrimiento de alimentos para la comunidad del campus. La interfaz cuenta con una cabecera hero con llamada a la acción, un buscador global en vivo que permite buscar alimentos por nombre o ingrediente, un carrusel dinámico de categorías gastronómicas y una sección interactiva de reseñas recientes dejadas por estudiantes.');
$body .= wPara('En esta vista, el estudiante puede escribir cualquier término en el buscador para ver sugerencias inmediatas, hacer clic en una categoría para filtrar el menú, o explorar la vitrina de platillos destacados. Detrás de escena, la página consulta las colecciones categorias (con el índice idx_cat_estado_nombre) y productos, y ejecuta un pipeline de agregación sobre resenas junto con usuarios para promediar las estrellas y mostrar el nombre del comensal. Además, el motor diferencial en tiempo real monitorea cambios en el catálogo cada 2 segundos.');
$body .= wImage('rIdVista1', 101, 5100000, 3267000, 'Figura 6.1 — Portada Principal de Snackver (index.php): Hero universitario, buscador en vivo, categorías y carrusel de reseñas.');

// 6.2 Catálogo por Categorías (Productos.php)
$body .= wHeading2('6.2 Catálogo de Platillos y Menú por Categorías (Front-end/Productos.php)');
$body .= wPara('Se construyó una cuadrícula comercial totalmente responsiva donde los estudiantes pueden explorar la oferta gastronómica disponible en el campus. Cada tarjeta de platillo muestra la fotografía del alimento optimizada desde Cloudinary CDN, el nombre comercial, el precio en pesos mexicanos, el número de existencias disponibles y la tienda del vendedor que lo prepara.');
$body .= wPara('El alumno puede seleccionar la cantidad deseada y hacer clic en el botón de agregar al carrito, visualizando una confirmación visual instantánea. A nivel de base de datos, esta pantalla ejecuta una consulta con filtro indexado sobre la colección productos usando el índice compuesto idx_prod_cat_stock ({ id_categoria: 1, stock_disponible: 1 }), descartando platillos agotados o inactivos y vinculando la información comercial del vendedor.');
$body .= wImage('rIdVista2', 102, 5100000, 3267000, 'Figura 6.2 — Catálogo de Platillos (Productos.php): Cuadrícula responsiva de alimentos con fotos en Cloudinary CDN y control de existencias.');

// 6.3 Acceso de Usuarios y Modal 2FA OTP (login.php)
$body .= wHeading2('6.3 Acceso de Usuarios y Modal de Verificación 2FA OTP en 0 ms (Front-end/login.php)');
$body .= wPara('Se desarrolló el módulo de inicio de sesión con soporte de seguridad de doble factor de autenticación (2FA). La pantalla solicita el correo electrónico institucional y la contraseña del usuario. Si la cuenta requiere verificación por código OTP, la interfaz despliega en 0 ms la ventana modal flotante (#otpModalOverlay) sobre el formulario, solicitando los 6 dígitos numéricos sin recargar la página.');
$body .= wPara('Cuando el usuario presiona "Iniciar Sesión", el servidor busca el correo en la colección usuarios mediante el índice único uniq_email y verifica la contraseña utilizando password_verify() contra el hash Bcrypt. Si es correcto, el servidor genera un código OTP de 6 dígitos que guarda en el subdocumento verificacion_otp en ~40 ms y responde al navegador para abrir la modal de inmediato (0 ms); al mismo tiempo, despacha en segundo plano el correo electrónico mediante HTTPS hacia Google Apps Script. El evento de inicio de sesión queda registrado en historial_usuarios.');
$body .= wImage('rIdVista3', 103, 5100000, 3267000, 'Figura 6.3 — Acceso al Sistema y Modal 2FA OTP (login.php): Autenticación con Bcrypt y apertura inmediata de verificación en 0 ms.');

// 6.4 Registro de Usuarios (registro.php)
$body .= wHeading2('6.4 Registro de Cuentas y Validación Institucional (Front-end/registro.php)');
$body .= wPara('Se implementó el formulario de registro donde los alumnos pueden darse de alta como Clientes o como Vendedores universitarios. El formulario valida en el cliente que el correo pertenezca al dominio educativo del TecNM y que las contraseñas coincidan. En caso de elegir el perfil de Vendedor, la pantalla despliega campos adicionales para capturar el nombre de su negocio, descripción y punto de entrega dentro de la institución.');
$body .= wPara('Al enviar el formulario, el sistema comprueba que el correo no exista en usuarios, cifra la contraseña con Bcrypt y genera el documento del usuario insertando el subdocumento embebido tienda (si es vendedor) y el subdocumento verificacion_otp con el código de activación. La modal de verificación de 6 dígitos se abre en 0 ms y el usuario recibe el correo de bienvenida.');
$body .= wImage('rIdVista4', 104, 5100000, 3267000, 'Figura 6.4 — Registro de Estudiantes y Vendedores (registro.php): Alta de perfil con roles RBAC y activación de cuenta por correo.');

// 6.5 Vitrina Digital del Vendedor (ver_tienda.php)
$body .= wHeading2('6.5 Vitrina Digital y Menú de la Tienda Estudiantil (Back-end/cliente/ver_tienda.php)');
$body .= wPara('Se diseñó la vista de escaparate digital para cada puesto o negocio de comida administrado por estudiantes. La interfaz presenta la portada y foto del negocio, el nombre comercial, la ubicación física de entrega (por ejemplo, "Banca 4 frente al Lab. de Cómputo"), el promedio de satisfacción obtenido en estrellas y la lista completa de platillos que ofrece.');
$body .= wPara('El comensal puede consultar el menú específico de este vendedor y añadir productos al carrito de forma directa. En MongoDB Atlas, esta vista lee el subdocumento tienda dentro del usuario vendedor, consulta la colección productos filtrando por el índice idx_vendedor, y ejecuta una agregación con el operador $avg sobre resenas para calcular la calificación promedio en tiempo real.');
$body .= wImage('rIdVista5', 105, 5100000, 3267000, 'Figura 6.5 — Vitrina Comercial del Vendedor (ver_tienda.php): Perfil comercial del estudiante, ubicación de entrega y menú exclusivo.');

// 6.6 Carrito de Compras (carrito.php)
$body .= wHeading2('6.6 Carrito de Compras y Control de Existencias (Back-end/cliente/carrito.php)');
$body .= wPara('Se programó el módulo de gestión del carrito de compras para los alumnos. La vista muestra la lista de alimentos seleccionados, la foto miniatura, el precio unitario, controles para incrementar (+) o decrementar (-) la cantidad, el importe por renglón y el cálculo dinámico del total a pagar, además de validar que no se exceda el inventario disponible.');
$body .= wPara('El alumno puede ajustar las cantidades o eliminar platillos con un clic. En la base de datos, cada alumno posee un único documento en la colección carritos indexado por uniq_carrito_cliente. Las modificaciones se aplican directamente en el arreglo embebido items[] mediante operaciones $push, $pull y $set, garantizando manipulación atómica de los productos sin bloqueos.');
$body .= wImage('rIdVista6', 106, 5100000, 3267000, 'Figura 6.6 — Carrito de Compras (carrito.php): Gestión dinámica de pedidos, cálculo de importes y validación de existencias.');

// 6.7 Mis Pedidos (MisPedidos.php)
$body .= wHeading2('6.7 Historial de Pedidos, Comprobantes SPEI y Cancelaciones (Back-end/cliente/MisPedidos.php)');
$body .= wPara('Se construyó el centro de seguimiento de pedidos del estudiante comensal. La interfaz organiza las compras con indicadores visuales por semáforo de color según su estado ("En preparación", "Listo para entrega", "Entregado" o "Cancelado"). Permite registrar el número de autorización o comprobante en caso de pagos por transferencia SPEI, y cuenta con un botón para cancelar pedidos pendientes.');
$body .= wPara('La consulta se resuelve en una sola lectura sin uniones (Cero JOINs) gracias a que cada documento de la colección pedidos contiene el arreglo items[] con todos los platillos y precios congelados. Si el alumno cancela un pedido en preparación, el sistema actualiza el estado a "Cancelado" y ejecuta una operación atómica $inc en la colección productos para devolver de inmediato las existencias al inventario del vendedor.');
$body .= wImage('rIdVista7', 107, 5100000, 3267000, 'Figura 6.7 — Historial de Pedidos del Estudiante (MisPedidos.php): Seguimiento por semáforo, registro de SPEI y cancelación con devolución de stock.');

// 6.8 Dashboard del Vendedor (dashboard_vendedor.php)
$body .= wHeading2('6.8 Panel de Control del Vendedor con KPIs en Vivo (Back-end/vendedor/dashboard_vendedor.php)');
$body .= wPara('Se implementó el panel de mando para el estudiante emprendedor. En la parte superior muestra tres tarjetas con indicadores numéricos clave (KPIs) en tiempo real: Total de Pedidos del Día, Ingresos Acumulados ($ MXN) y Platillos Activos en Menú. También incluye una campana de notificaciones con contador rojo y una lista de los pedidos recibidos recientemente.');
$body .= wPara('El script notificaciones.js consulta cada 1.5 segundos en segundo plano. Si ingresa una nueva orden, el contador de la campana se actualiza y los tres números de los KPIs se recalculan en vivo sin necesidad de que el vendedor recargue la página. En Atlas, esto se logra mediante consultas indexadas con idx_pedidos_ven_estado y agregaciones sobre pedidos.');
$body .= wImage('rIdVista8', 108, 5100000, 3267000, 'Figura 6.8 — Dashboard del Vendedor (dashboard_vendedor.php): 3 KPIs numéricos en vivo, campana de alertas y bandeja de pedidos.');

// 6.9 Inventario de Platillos (Misplatillos.php)
$body .= wHeading2('6.9 Inventario de Platillos y Control de Disponibilidad (Back-end/vendedor/Misplatillos.php)');
$body .= wPara('Se programó la pantalla de administración de catálogo e inventario para los vendedores. La vista lista todos los alimentos publicados por el usuario con su imagen de Cloudinary, categoría, precio, stock disponible y estado (activo o inactivo). Cuenta con botones directos para agregar un nuevo platillo, editar la información o dar de baja un producto.');
$body .= wPara('Cuando el vendedor añade o modifica un platillo, el formulario procesa la imagen en el cliente mediante un <canvas> de HTML5 para comprimirla a ~100 KB antes de subirla a Cloudinary vía CloudinaryService.php. La base de datos guarda la URL segura HTTPS y gestiona el inventario en la colección productos con el índice idx_prod_vendedor_stock.');
$body .= wImage('rIdVista9', 109, 5100000, 3267000, 'Figura 6.9 — Inventario y Catálogo del Vendedor (Misplatillos.php): Control de platillos, precios, stock y sincronización de fotos.');

// 6.10 Despacho de Pedidos (pedidos_pendientes.php)
$body .= wHeading2('6.10 Despacho y Gestión de Pedidos en Tiempo Real (Back-end/vendedor/pedidos_pendientes.php)');
$body .= wPara('Se desarrolló la bandeja de despacho de comandas para que el vendedor gestione la preparación de comida durante los recesos. La pantalla muestra los pedidos clasificados por pestañas ("Pendientes", "En preparación", "Listos", "Entregados"), con botones rápidos de acción para avanzar el pedido al siguiente estado con un solo clic.');
$body .= wPara('Al cambiar el estado de un pedido (por ejemplo, a "Listo para entrega"), el servidor actualiza el campo estadopedido en la colección pedidos y genera automáticamente un documento en notificaciones. El cliente que espera su comida recibe la alerta sonora y visual en su dispositivo en 0 ms. El filtro entre pestañas se ejecuta de forma instantánea mediante el índice compuesto idx_pedidos_ven_est_fecha.');
$body .= wImage('rIdVista10', 110, 5100000, 3267000, 'Figura 6.10 — Despacho de Pedidos en Vivo (pedidos_pendientes.php): Gestión de órdenes con avance de estados y alertas automáticas al alumno.');

// 6.11 Bitácora de Auditoría Forense (historial_usuarios.php)
$body .= wHeading2('6.11 Bitácora de Auditoría Forense y Monitoreo de Accesos (Back-end/admin/historial_usuarios.php)');
$body .= wPara('Se implementó el panel de seguridad y auditoría para el administrador del sistema. La interfaz presenta una tabla cronológica con el registro detallado de cada evento de seguridad ocurrido en la plataforma: inicios de sesión, registros nuevos, solicitudes de código 2FA OTP, cambios de clave y bajas de usuarios.');
$body .= wPara('Cada fila muestra el nombre del usuario, su correo electrónico, el rol, la acción ejecutada, la dirección IP desde donde se conectó, el navegador y sistema operativo (User-Agent) y la fecha y hora exacta en formato UTC. En MongoDB Atlas, esta vista consulta la colección historial_usuarios mediante los índices idx_historial_fecha e idx_historial_accion_fecha, permitiendo una rápida auditoría ante cualquier incidente de seguridad.');
$body .= wImage('rIdVista11', 111, 5100000, 3267000, 'Figura 6.11 — Bitácora de Auditoría Forense (historial_usuarios.php): Registro inmutable de eventos de seguridad con direcciones IP y marcas de tiempo.');

// 6.12 Resumen de las 24 Vistas Programadas
$body .= wHeading2('6.12 Módulos Complementarios del Sistema Web (Resumen de las 24 Vistas Programadas)');
$body .= wPara('Además de las 11 vistas principales documentadas con captura de pantalla, Snackver cuenta con 13 interfaces adicionales completamente programadas y conectadas a MongoDB Atlas en producción, conformando el ecosistema de 24 vistas del proyecto:');

$body .= wTable(
    ['MÓDULO DEL SISTEMA', 'VISTAS COMPLEMENTARIAS PROGRAMADAS', 'OPERACIÓN Y PROPÓSITO TÉCNICO'],
    [
        ['Módulo Público y Ayuda', 'Front-end/footer/contacto.php, preguntas-clientes.php, preguntas-vendedores.php, guia-cliente.php, guia-vendedor.php, terminos.php', '6 páginas de soporte, políticas universitarias y guías paso a paso para el uso correcto de la plataforma.'],
        ['Módulo de Seguridad', 'recuperacion_password.php, cambiar_password.php, verificar_codigo.php', 'Flujo de recuperación de contraseñas mediante código OTP en modal flotante (0 ms) y validación en Atlas.'],
        ['Módulo Cliente', 'BuscarTienda.php, checkout.php, Miperfil.php, resenas.php', 'Directorio de tiendas con buscador, confirmación de pedido con descuento atómico, perfil y formulario de reseñas.'],
        ['Módulo Vendedor', 'nuevoplatillo.php, editar_platillo.php, MiTienda.php', 'Formularios con compresión <canvas> para alta y edición de platillos, y configuración del puesto del alumno.'],
        ['Módulo Administrador', 'dashboard_admin.php, clientes.php, vendedores.php, categorias.php', 'Métricas analíticas globales con $facet, gestión y baja en cascada de usuarios, y catálogo de categorías.']
    ],
    '1B396A'
);

// ============================================================================
// CAPITULO 7: CONCLUSION
// ============================================================================
$body .= wHeading1('7. CONCLUSIÓN');
$body .= wPara('La modernización integral de Snackver desarrollada en la Unidad 2 demuestra los beneficios técnicos de migrar desde una arquitectura relacional local (MySQL) hacia un entorno NoSQL en la nube con MongoDB Atlas:');
$body .= wBullet('1. Eliminación de JOINs y Agilidad Documental:', 'El modelo de 8 colecciones BSON con subdocumentos embebidos (tienda, OTP e items[] en pedidos y carritos) erradicó por completo las uniones de tablas costosas. Las consultas de compras y perfiles se resuelven en una sola lectura, reduciendo el tráfico de red y mejorando notablemente el tiempo de respuesta.');
$body .= wBullet('2. Experiencia en Tiempo Real (0 ms):', 'El motor diferencial por hash MD5 (2s), las alertas reactivas de la campana (1.5s), la compresión de fotografías en el cliente mediante canvas de HTML5 y el sistema de verificación 2FA OTP en dos fases permitieron una plataforma ágil e instantánea que opera sin recargas molestas de página.');
$body .= wBullet('3. Seguridad Multicapa de Grado Comercial:', 'La combinación de roles RBAC, encriptación de claves con Bcrypt, 2FA OTP despachado de forma segura por HTTPS 443 hacia Google Apps Script, cifrado en tránsito (TLS 1.3) y en reposo (AES-256), y la bitácora inmutable en historial_usuarios ofrecen protección integral para la comunidad del campus.');
$body .= wBullet('4. Infraestructura en la Nube Escalable y Continua:', 'La integración de MongoDB Atlas, el dominio oficial snackver.me con SSL en Namecheap, el contenedor Docker en Render y el monitoreo automatizado de disponibilidad en cron-job.org consolidan una plataforma robusta, moderna y disponible 24/7 para el Instituto Tecnológico de Veracruz.');

$footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:p>'
    . '<w:pPr>'
    . '<w:jc w:val="right"/>'
    . '<w:spacing w:after="0" w:line="240" w:lineRule="auto"/>'
    . '<w:rPr>'
    . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
    . '<w:b/>'
    . '<w:color w:val="000000"/>'
    . '<w:sz w:val="24"/><w:szCs w:val="24"/>'
    . '</w:rPr>'
    . '</w:pPr>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:t xml:space="preserve">Página </w:t>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:fldChar w:fldCharType="begin"/>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:instrText>PAGE   \* MERGEFORMAT</w:instrText>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:fldChar w:fldCharType="separate"/>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:t xml:space="preserve"> | </w:t>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:fldChar w:fldCharType="begin"/>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:instrText>NUMPAGES  \* Arabic  \* MERGEFORMAT</w:instrText>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:fldChar w:fldCharType="separate"/>'
    . '</w:r>'
    . '<w:r>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:fldChar w:fldCharType="end"/>'
    . '</w:r>'
    . '</w:p>'
    . '</w:ftr>';

$sectPr = '<w:sectPr>'
    . '<w:footerReference w:type="default" r:id="rIdFooter1"/>'
    . '<w:titlePg/>'
    . '<w:pgSz w:w="12240" w:h="15840"/>'
    . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="567" w:footer="567" w:gutter="0"/>'
    . '</w:sectPr>';

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
    . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
    . '<w:body>' . $body . $sectPr . '</w:body></w:document>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Default Extension="png" ContentType="image/png"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
    . '</Types>';

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$docRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '<Relationship Id="rIdFooter1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
    // 10 Diagramas
    . '<Relationship Id="rIdImgDiag3_1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag1_1_doc_usuarios.png"/>'
    . '<Relationship Id="rIdImgDiag3_2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag1_2_doc_pedidos.png"/>'
    . '<Relationship Id="rIdImgDiag3_3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag1_3_doc_carritos.png"/>'
    . '<Relationship Id="rIdImgDiag3_4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag1_4_doc_catalogo.png"/>'
    . '<Relationship Id="rIdImgDiag3_5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag1_5_doc_interaccion_auditoria.png"/>'
    . '<Relationship Id="rIdImgDiag4_arch1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag2_arquitectura_mongo_cluster.png"/>'
    . '<Relationship Id="rIdImgDiag4_arch2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag3_arquitectura_cloud.png"/>'
    . '<Relationship Id="rIdImgDiag5_1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag4_1_flujo_pedidos.png"/>'
    . '<Relationship Id="rIdImgDiag5_2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag4_2_flujo_catalogo_resenas.png"/>'
    . '<Relationship Id="rIdImgDiag5_3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/diag4_3_flujo_seguridad_auditoria.png"/>'
    // 11 Capturas de Vistas Reales
    . '<Relationship Id="rIdVista1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista1_portada_index.png"/>'
    . '<Relationship Id="rIdVista2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista2_catalogo_productos.png"/>'
    . '<Relationship Id="rIdVista3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista3_login_otp.png"/>'
    . '<Relationship Id="rIdVista4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista4_registro.png"/>'
    . '<Relationship Id="rIdVista5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista5_ver_tienda.png"/>'
    . '<Relationship Id="rIdVista6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista6_carrito.png"/>'
    . '<Relationship Id="rIdVista7" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista7_mis_pedidos.png"/>'
    . '<Relationship Id="rIdVista8" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista8_dashboard_vendedor.png"/>'
    . '<Relationship Id="rIdVista9" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista9_mis_platillos.png"/>'
    . '<Relationship Id="rIdVista10" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista10_pedidos_pendientes.png"/>'
    . '<Relationship Id="rIdVista11" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/vista11_historial_usuarios.png"/>';

if (file_exists($logoTecPath)) {
    $docRelsXml .= '<Relationship Id="rIdImgLogoTec" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo_tecnm.png"/>';
}
$docRelsXml .= '</Relationships>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
    . '<w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:sz w:val="24"/><w:szCs w:val="24"/><w:color w:val="000000"/></w:rPr>'
    . '<w:pPr><w:jc w:val="both"/><w:spacing w:line="360" w:lineRule="auto" w:after="160"/></w:pPr>'
    . '</w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading1">'
    . '<w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr>'
    . '<w:pPr><w:jc w:val="center"/><w:spacing w:before="280" w:after="140" w:line="360" w:lineRule="auto"/></w:pPr>'
    . '</w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading2">'
    . '<w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr>'
    . '<w:pPr><w:jc w:val="left"/><w:spacing w:before="220" w:after="90" w:line="360" w:lineRule="auto"/></w:pPr>'
    . '</w:style>'
    . '<w:style w:type="paragraph" w:styleId="Heading3">'
    . '<w:name w:val="heading 3"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/>'
    . '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="000000"/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr>'
    . '<w:pPr><w:jc w:val="left"/><w:spacing w:before="160" w:after="60" w:line="360" w:lineRule="auto"/></w:pPr>'
    . '</w:style>'
    . '</w:styles>';

$targetDocx1 = $projectRoot . '/documentacion/DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx';
$targetDocx2 = $projectRoot . '/DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx';

$imagesMap = [
    // 10 Diagramas técnicos UML
    'media/diag1_1_doc_usuarios.png'              => $diagramsDir . '/diag1_1_doc_usuarios.png',
    'media/diag1_2_doc_pedidos.png'               => $diagramsDir . '/diag1_2_doc_pedidos.png',
    'media/diag1_3_doc_carritos.png'              => $diagramsDir . '/diag1_3_doc_carritos.png',
    'media/diag1_4_doc_catalogo.png'              => $diagramsDir . '/diag1_4_doc_catalogo.png',
    'media/diag1_5_doc_interaccion_auditoria.png' => $diagramsDir . '/diag1_5_doc_interaccion_auditoria.png',
    'media/diag2_arquitectura_mongo_cluster.png'  => $diagramsDir . '/diag2_arquitectura_mongo_cluster.png',
    'media/diag3_arquitectura_cloud.png'          => $diagramsDir . '/diag3_arquitectura_cloud.png',
    'media/diag4_1_flujo_pedidos.png'             => $diagramsDir . '/diag4_1_flujo_pedidos.png',
    'media/diag4_2_flujo_catalogo_resenas.png'    => $diagramsDir . '/diag4_2_flujo_catalogo_resenas.png',
    'media/diag4_3_flujo_seguridad_auditoria.png' => $diagramsDir . '/diag4_3_flujo_seguridad_auditoria.png',
    // 11 Capturas de pantalla reales de las vistas
    'media/vista1_portada_index.png'              => $vistasDir . '/vista1_portada_index.png',
    'media/vista2_catalogo_productos.png'         => $vistasDir . '/vista2_catalogo_productos.png',
    'media/vista3_login_otp.png'                  => $vistasDir . '/vista3_login_otp.png',
    'media/vista4_registro.png'                   => $vistasDir . '/vista4_registro.png',
    'media/vista5_ver_tienda.png'                 => $vistasDir . '/vista5_ver_tienda.png',
    'media/vista6_carrito.png'                    => $vistasDir . '/vista6_carrito.png',
    'media/vista7_mis_pedidos.png'                => $vistasDir . '/vista7_mis_pedidos.png',
    'media/vista8_dashboard_vendedor.png'         => $vistasDir . '/vista8_dashboard_vendedor.png',
    'media/vista9_mis_platillos.png'              => $vistasDir . '/vista9_mis_platillos.png',
    'media/vista10_pedidos_pendientes.png'        => $vistasDir . '/vista10_pedidos_pendientes.png',
    'media/vista11_historial_usuarios.png'        => $vistasDir . '/vista11_historial_usuarios.png'
];

foreach ([$targetDocx1, $targetDocx2] as $docxFile) {
    if (file_exists($docxFile)) {
        @unlink($docxFile);
    }
    $zip = new ZipArchive();
    if ($zip->open($docxFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die("Error al crear archivo DOCX: $docxFile\n");
    }
    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $relsXml);
    $zip->addFromString('word/document.xml', $documentXml);
    $zip->addFromString('word/styles.xml', $stylesXml);
    $zip->addFromString('word/footer1.xml', $footerXml);
    $zip->addFromString('word/_rels/document.xml.rels', $docRelsXml);

    foreach ($imagesMap as $zipEntry => $localFile) {
        if (!file_exists($localFile)) {
            die("Falta la imagen requerida: $localFile\n");
        }
        $zip->addFile($localFile, 'word/' . $zipEntry);
    }

    if (file_exists($logoTecPath)) {
        $zip->addFile($logoTecPath, 'word/media/logo_tecnm.png');
    }
    $zip->close();
    echo "Archivo DOCX creado exitosamente: $docxFile (" . filesize($docxFile) . " bytes)\n";
}
