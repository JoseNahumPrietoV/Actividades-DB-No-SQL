<?php
namespace Snackver\Servicios;

require_once dirname(__DIR__) . '/conexion/conexion_mongo.php';

use MongoConexion;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Exception;

class NotificacionService
{
    /**
     * Normaliza un identificador de usuario para búsqueda flexible en MongoDB.
     */
    private static function normalizarFiltroUsuario($idUsuario)
    {
        $filtro = [];
        $idStr = (string)$idUsuario;

        if (is_string($idUsuario) && preg_match('/^[a-f\d]{24}$/i', $idUsuario)) {
            $filtro['$or'] = [
                ['id_usuario' => new ObjectId($idUsuario)],
                ['id_usuario_str' => $idStr],
                ['id_usuario' => $idStr]
            ];
        } elseif ($idUsuario instanceof ObjectId) {
            $filtro['$or'] = [
                ['id_usuario' => $idUsuario],
                ['id_usuario_str' => (string)$idUsuario],
                ['id_usuario' => (string)$idUsuario]
            ];
        } else {
            $filtro['$or'] = [
                ['id_usuario' => (int)$idUsuario],
                ['id_usuario_str' => $idStr],
                ['id_usuario' => $idStr]
            ];
        }

        return $filtro;
    }

    /**
     * Crea y guarda una notificación en la colección 'notificaciones' de MongoDB Atlas.
     * Incorpora control de idempotencia y validación por fecha y pedido para no duplicar notificaciones.
     */
    public static function crearNotificacion($idUsuario, $tipoUsuario, $mensaje, $idPedido = null, $urlDestino = null)
    {
        // Normalización flexible de parámetros si se invirtieron tipo_usuario y mensaje
        if ($tipoUsuario !== 'cliente' && $tipoUsuario !== 'vendedor' && ($mensaje === 'cliente' || $mensaje === 'vendedor' || $mensaje === 'pedido' || $mensaje === 'admin')) {
            $temp = $tipoUsuario;
            $tipoUsuario = ($mensaje === 'pedido') ? 'cliente' : $mensaje;
            $mensaje = $temp;
        }

        if (empty($idUsuario) || empty($mensaje)) {
            return false;
        }

        try {
            $mongoDb = MongoConexion::getConexion();
            $colNotificaciones = $mongoDb->selectCollection('notificaciones');

            $idUsuarioObj = $idUsuario;
            if (is_string($idUsuario) && preg_match('/^[a-f\d]{24}$/i', $idUsuario)) {
                $idUsuarioObj = new ObjectId($idUsuario);
            }

            // --- Control de Idempotencia y Deduplicación con Fecha ---
            $mensajeLimpio = trim($mensaje);
            $filtroExistente = [
                '$and' => [
                    self::normalizarFiltroUsuario($idUsuario),
                    ['tipo_usuario' => $tipoUsuario],
                    ['mensaje' => $mensajeLimpio]
                ]
            ];

            if (!empty($idPedido)) {
                $filtroExistente['$and'][] = [
                    '$or' => [
                        ['id_pedido' => (string)$idPedido],
                        ['id_pedido' => (int)$idPedido]
                    ]
                ];
            } else {
                // Si es un mensaje general sin id de pedido, validar si se generó en los últimos 5 minutos
                $cincoMinutosAtras = new UTCDateTime((time() - 300) * 1000);
                $filtroExistente['$and'][] = ['fecha' => ['$gte' => $cincoMinutosAtras]];
            }

            $yaExiste = $colNotificaciones->findOne($filtroExistente);
            if ($yaExiste) {
                // Ya existe esta notificación registrada para este evento; no duplicar
                return true;
            }

            $doc = [
                'id_usuario'     => $idUsuarioObj,
                'id_usuario_str' => (string)$idUsuario,
                'tipo_usuario'   => $tipoUsuario, // 'cliente' | 'vendedor'
                'id_pedido'      => $idPedido !== null ? (string)$idPedido : null,
                'mensaje'        => $mensajeLimpio,
                'url_destino'    => $urlDestino,
                'leida'          => false,
                'fecha'          => new UTCDateTime()
            ];

            $colNotificaciones->insertOne($doc);
            return true;
        } catch (Exception $e) {
            error_log("Error en NotificacionService::crearNotificacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las notificaciones del usuario ordenadas por fecha descendente.
     * Soporta filtrado por tipo de usuario (cliente / vendedor) para evitar mezclas en sesiones locales.
     * Deduplica en memoria cualquier registro repetido histórico.
     */
    public static function obtenerNotificaciones($idUsuario, $limite = 20, $tipoUsuario = null)
    {
        if (empty($idUsuario)) {
            return ['notificaciones' => [], 'total_no_leidas' => 0];
        }

        try {
            $mongoDb = MongoConexion::getConexion();
            $colNotificaciones = $mongoDb->selectCollection('notificaciones');

            $filtroUser = self::normalizarFiltroUsuario($idUsuario);
            if (!empty($tipoUsuario)) {
                $filtro = [
                    '$and' => [
                        $filtroUser,
                        ['tipo_usuario' => $tipoUsuario]
                    ]
                ];
            } else {
                $filtro = $filtroUser;
            }

            $cursor = $colNotificaciones->find(
                $filtro,
                [
                    'sort'  => ['fecha' => -1],
                    'limit' => (int)$limite
                ]
            );

            $notificaciones = [];
            $clavesVistas = [];
            $noLeidas = 0;
            $ahoraTimestamp = time();

            foreach ($cursor as $doc) {
                $msg = trim($doc['mensaje'] ?? '');
                $idPed = (string)($doc['id_pedido'] ?? '');

                // Clave única para deduplicar eventos idénticos en el renderizado
                $claveUnica = ($idPed !== '') ? ($idPed . '|' . $msg) : ((string)$doc['_id']);
                if (isset($clavesVistas[$claveUnica])) {
                    continue; // Omitir duplicado histórico
                }
                $clavesVistas[$claveUnica] = true;

                $leida = !empty($doc['leida']);
                if (!$leida) {
                    $noLeidas++;
                }

                $fechaTimestamp = $ahoraTimestamp;
                if (isset($doc['fecha']) && $doc['fecha'] instanceof UTCDateTime) {
                    $fechaTimestamp = $doc['fecha']->toDateTime()->getTimestamp();
                }

                $difSegundos = max(0, $ahoraTimestamp - $fechaTimestamp);
                if ($difSegundos < 60) {
                    $tiempoRelativo = 'Hace un momento';
                } elseif ($difSegundos < 3600) {
                    $mins = floor($difSegundos / 60);
                    $tiempoRelativo = "Hace $mins min";
                } elseif ($difSegundos < 86400) {
                    $horas = floor($difSegundos / 3600);
                    $tiempoRelativo = "Hace $horas h";
                } else {
                    $dias = floor($difSegundos / 86400);
                    $tiempoRelativo = "Hace $dias d";
                }

                $notificaciones[] = [
                    'id'              => (string)$doc['_id'],
                    'id_pedido'       => $idPed,
                    'mensaje'         => $msg,
                    'url_destino'     => $doc['url_destino'] ?? '',
                    'leida'           => $leida,
                    'fecha'           => date('Y-m-d H:i:s', $fechaTimestamp),
                    'tiempo_relativo' => $tiempoRelativo
                ];
            }

            return [
                'notificaciones'   => $notificaciones,
                'total_no_leidas'  => $noLeidas
            ];
        } catch (Exception $e) {
            error_log("Error en NotificacionService::obtenerNotificaciones: " . $e->getMessage());
            return ['notificaciones' => [], 'total_no_leidas' => 0];
        }
    }

    /**
     * Sincroniza pedidos pendientes de notificación para clientes creando notificaciones en tiempo real (sin #id).
     */
    public static function sincronizarPedidosCliente($idUsuario)
    {
        if (empty($idUsuario)) return;
        try {
            $mongoDb = MongoConexion::getConexion();
            $colPedidos = $mongoDb->selectCollection('pedidos');

            $idStr = (string)$idUsuario;
            $filtroCliente = [];
            if (preg_match('/^[a-f\d]{24}$/i', $idStr)) {
                $filtroCliente['$or'] = [
                    ['id_cliente' => new ObjectId($idStr)],
                    ['id_cliente' => $idStr],
                    ['id_cliente' => (int)$idUsuario]
                ];
            } else {
                $filtroCliente['$or'] = [
                    ['id_cliente' => (int)$idUsuario],
                    ['id_cliente' => $idStr]
                ];
            }

            $filtro = [
                '$and' => [
                    $filtroCliente,
                    [
                        '$or' => [
                            ['notificado_cliente' => 0],
                            ['notificado_cliente' => false],
                            ['notificado_cliente' => ['$exists' => false]]
                        ]
                    ],
                    [
                        'estadopedido' => [
                            '$in' => ['En preparación', 'Listo', 'Entregado', 'Rechazado', 'Cancelado']
                        ]
                    ]
                ]
            ];

            $cursor = $colPedidos->find($filtro);
            $idsActualizar = [];

            foreach ($cursor as $row) {
                $idPedidoStr = (string)($row['id_pedido_num'] ?? $row['_id']);
                $estado = $row['estadopedido'] ?? '';
                $mensaje = "";

                switch ($estado) {
                    case 'En preparación':
                        $mensaje = "👨‍🍳 ¡Tu pedido se está preparando!";
                        break;
                    case 'Listo':
                        $mensaje = "✅ ¡Tu pedido está LISTO! Puedes pasar por él.";
                        break;
                    case 'Entregado':
                        $mensaje = "🎉 ¡Pedido entregado! Gracias por tu compra.";
                        break;
                    case 'Rechazado':
                        $mensaje = "❌ Lo sentimos, tu pedido fue rechazado.";
                        break;
                    case 'Cancelado':
                        $mensaje = "⚠️ Tu pedido ha sido cancelado.";
                        break;
                }

                if (!empty($mensaje)) {
                    self::crearNotificacion(
                        $idUsuario,
                        'cliente',
                        $mensaje,
                        $idPedidoStr,
                        'MisPedidos.php'
                    );
                    $idsActualizar[] = $row['_id'];
                }
            }

            if (!empty($idsActualizar)) {
                $colPedidos->updateMany(
                    ['_id' => ['$in' => $idsActualizar]],
                    ['$set' => ['notificado_cliente' => 1]]
                );
            }
        } catch (Exception $e) {
            error_log("Error en sincronizarPedidosCliente: " . $e->getMessage());
        }
    }

    /**
     * Sincroniza pedidos pendientes de notificación para vendedores creando notificaciones en tiempo real (sin #id).
     */
    public static function sincronizarPedidosVendedor($idUsuario)
    {
        if (empty($idUsuario)) return;
        try {
            $mongoDb = MongoConexion::getConexion();
            $colPedidos = $mongoDb->selectCollection('pedidos');

            $idStr = (string)$idUsuario;
            $filtroVendedor = [];
            if (preg_match('/^[a-f\d]{24}$/i', $idStr)) {
                $filtroVendedor['$or'] = [
                    ['id_vendedor' => new ObjectId($idStr)],
                    ['id_vendedor' => $idStr],
                    ['id_vendedor' => (int)$idUsuario]
                ];
            } else {
                $filtroVendedor['$or'] = [
                    ['id_vendedor' => (int)$idUsuario],
                    ['id_vendedor' => $idStr]
                ];
            }

            $filtro = [
                '$and' => [
                    $filtroVendedor,
                    [
                        '$or' => [
                            ['notificado_vendedor' => 0],
                            ['notificado_vendedor' => false],
                            ['notificado_vendedor' => ['$exists' => false]]
                        ]
                    ],
                    [
                        'estadopedido' => [
                            '$in' => ['Pendiente', 'Cancelado', 'En preparación']
                        ]
                    ]
                ]
            ];

            $cursor = $colPedidos->find($filtro);
            $idsActualizar = [];

            foreach ($cursor as $row) {
                $idPedidoStr = (string)($row['id_pedido_num'] ?? $row['_id']);
                $estado = $row['estadopedido'] ?? 'Pendiente';
                $total  = number_format((float)($row['total'] ?? 0), 2);
                $mensaje = "";

                switch ($estado) {
                    case 'Pendiente':
                        $mensaje = "🔔 ¡Nuevo pedido recibido! - $$total";
                        break;
                    case 'Cancelado':
                        $mensaje = "⚠️ El cliente ha cancelado su pedido.";
                        break;
                    case 'En preparación':
                        $mensaje = "💰 ¡Pago confirmado! El pedido se encuentra en preparación.";
                        break;
                }

                if (!empty($mensaje)) {
                    self::crearNotificacion(
                        $idUsuario,
                        'vendedor',
                        $mensaje,
                        $idPedidoStr,
                        '../vendedor/Gestion_platillos.php'
                    );
                    $idsActualizar[] = $row['_id'];
                }
            }

            if (!empty($idsActualizar)) {
                $colPedidos->updateMany(
                    ['_id' => ['$in' => $idsActualizar]],
                    ['$set' => ['notificado_vendedor' => 1]]
                );
            }
        } catch (Exception $e) {
            error_log("Error en sincronizarPedidosVendedor: " . $e->getMessage());
        }
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas (filtrando opcionalmente por rol).
     */
    public static function marcarTodasLeidas($idUsuario, $tipoUsuario = null)
    {
        if (empty($idUsuario)) {
            return false;
        }

        try {
            $mongoDb = MongoConexion::getConexion();
            $colNotificaciones = $mongoDb->selectCollection('notificaciones');

            $filtroUser = self::normalizarFiltroUsuario($idUsuario);
            if (!empty($tipoUsuario)) {
                $filtro = [
                    '$and' => [
                        $filtroUser,
                        ['tipo_usuario' => $tipoUsuario]
                    ]
                ];
            } else {
                $filtro = $filtroUser;
            }

            $colNotificaciones->updateMany(
                $filtro,
                ['$set' => ['leida' => true]]
            );

            return true;
        } catch (Exception $e) {
            error_log("Error en NotificacionService::marcarTodasLeidas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca una notificación específica como leída.
     */
    public static function marcarLeida($idNotificacion, $idUsuario)
    {
        if (empty($idNotificacion)) {
            return false;
        }

        try {
            $mongoDb = MongoConexion::getConexion();
            $colNotificaciones = $mongoDb->selectCollection('notificaciones');

            $filtro = [
                '_id' => new ObjectId($idNotificacion)
            ];

            $colNotificaciones->updateOne(
                $filtro,
                ['$set' => ['leida' => true]]
            );

            return true;
        } catch (Exception $e) {
            error_log("Error en NotificacionService::marcarLeida: " . $e->getMessage());
            return false;
        }
    }
}
