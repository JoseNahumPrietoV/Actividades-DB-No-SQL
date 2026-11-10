/**
 * ==============================================================================
 * PROYECTO: SIGEM — TECNM CAMPUS VERACRUZ
 * SCRIPT MAESTRO DE INICIALIZACIÓN Y VALIDACIÓN NoSQL (MongoDB Shell / mongosh)
 * ARCHIVO: database/mongo/init_database.js
 * ==============================================================================
 * Descripción:
 * Este script automatiza la configuración completa de la base de datos NoSQL
 * en MongoDB Atlas o local. Incluye:
 *   1. Creación de colecciones con reglas estrictas de validación ($jsonSchema).
 *   2. Creación de índices optimizados (únicos, simples y compuestos).
 *   3. Operaciones CRUD documentadas con operadores atómicos ($set, $inc, etc.).
 *   4. Pipelines de agregación avanzados ($lookup, $group, $match, $project).
 *
 * Modo de Ejecución:
 *   mongosh "<MONGO_URI>" init_database.js
 * O dentro de mongosh interactivo:
 *   load("database/mongo/init_database.js")
 * ==============================================================================
 */

// ------------------------------------------------------------------------------
// 0. SELECCIÓN DE BASE DE DATOS
// ------------------------------------------------------------------------------
const DB_NAME = "sigem_tecnm";
const targetDb = db.getSiblingDB(DB_NAME);

print("\n==================================================================");
print("🚀 [SIGEM] Iniciando inicialización de base de datos NoSQL: " + DB_NAME);
print("==================================================================\n");

// ------------------------------------------------------------------------------
// 1. CREACIÓN DE COLECCIONES CON VALIDACIÓN DE ESQUEMA ($jsonSchema)
// ------------------------------------------------------------------------------
print("📋 PASO 1: Configurando colecciones y reglas de validación ($jsonSchema)...");

// Helper para crear colección de manera idempotente con validador
function crearColeccionConValidador(nombreColeccion, schemaDef) {
    const coleccionesExistentes = targetDb.getCollectionNames();
    if (coleccionesExistentes.includes(nombreColeccion)) {
        print(`   ⚠️  La colección '${nombreColeccion}' ya existe. Aplicando validador vía collMod...`);
        targetDb.runCommand({
            collMod: nombreColeccion,
            validator: { $jsonSchema: schemaDef },
            validationLevel: "moderate",
            validationAction: "error"
        });
    } else {
        print(`   ✨ Creando colección '${nombreColeccion}' con esquema estricto...`);
        targetDb.createCollection(nombreColeccion, {
            validator: { $jsonSchema: schemaDef },
            validationLevel: "moderate",
            validationAction: "error"
        });
    }
}

// 1.1 Esquema para Colección: USERS
const userSchema = {
    bsonType: "object",
    required: ["name", "email", "password", "tipo_usuario"],
    properties: {
        _id: { bsonType: "objectId" },
        name: {
            bsonType: "string",
            description: "Nombre completo del usuario — Requerido (String)"
        },
        email: {
            bsonType: "string",
            pattern: "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$",
            description: "Correo electrónico institucional o válido — Requerido con patrón regex"
        },
        password: {
            bsonType: "string",
            description: "Hash de contraseña bcrypt — Requerido"
        },
        tipo_usuario: {
            enum: ["Administrador", "Servicio Social", "Docente", "Personal"],
            description: "Rol del usuario en el sistema SIGEM — Enum requerido"
        },
        activo: {
            bsonType: "bool",
            description: "Estado de acceso al sistema (true/false)"
        },
        email_verified_at: { bsonType: ["date", "null"] },
        remember_token: { bsonType: ["string", "null"] },
        created_at: { bsonType: ["date", "null"] },
        updated_at: { bsonType: ["date", "null"] }
    }
};
crearColeccionConValidador("users", userSchema);

// 1.2 Esquema para Colección: MATERIAL (Catálogo Maestro)
const materialSchema = {
    bsonType: "object",
    required: ["nombre", "stock_actual", "stock_minimo", "requiere_control_individual"],
    properties: {
        _id: { bsonType: "objectId" },
        nombre: {
            bsonType: "string",
            description: "Nombre del material o equipo — Requerido"
        },
        descripcion: { bsonType: ["string", "null"] },
        modelo: { bsonType: ["string", "null"] },
        id_unidad: {
            bsonType: ["objectId", "string", "null"],
            description: "Referencia a unidad_medida._id"
        },
        id_marca: {
            bsonType: ["objectId", "string", "null"],
            description: "Referencia a marca_material._id"
        },
        id_tipodematerial: {
            bsonType: ["objectId", "string", "null"],
            description: "Referencia a tipo_material._id"
        },
        requiere_control_individual: {
            bsonType: ["bool", "int", "number"],
            description: "Si requiere número de serie individual (true/false o 1/0)"
        },
        stock_actual: {
            bsonType: ["int", "long", "number", "double"],
            minimum: 0,
            description: "Existencia actual no negativa"
        },
        stock_minimo: {
            bsonType: ["int", "long", "number", "double"],
            minimum: 0,
            description: "Punto de reorden / stock de alerta"
        },
        deleted_at: { bsonType: ["date", "null"] },
        created_at: { bsonType: ["date", "null"] },
        updated_at: { bsonType: ["date", "null"] }
    }
};
crearColeccionConValidador("material", materialSchema);

// 1.3 Esquema para Colección: INVENTARIO (Activos individuales con número de serie)
const inventarioSchema = {
    bsonType: "object",
    required: ["num_serie", "id_producto", "estado", "tipo_propiedad"],
    properties: {
        _id: { bsonType: "objectId" },
        num_serie: {
            bsonType: "string",
            description: "Número de serie único de placa o fábrica — Requerido"
        },
        id_producto: {
            bsonType: ["objectId", "string"],
            description: "Referencia obligatoria a material._id"
        },
        id_usuario: {
            bsonType: ["objectId", "string", "null"],
            description: "Usuario custodio asignado (users._id)"
        },
        id_proveedor: {
            bsonType: ["objectId", "string", "null"],
            description: "Proveedor de adquisición (proveedor._id)"
        },
        estado: {
            enum: ["Disponible", "Asignado", "En Mantenimiento", "Dañado", "Baja"],
            description: "Estado operativo del activo — Enum estricto"
        },
        estado_registro: {
            enum: ["Pendiente", "Aprobado", "Rechazado"],
            description: "Flujo de aprobación inicial"
        },
        tipo_propiedad: {
            enum: ["Propio", "Renta", "Comodato", "Donación"],
            description: "Modalidad de tenencia del activo"
        },
        ubicacion_fisica: {
            bsonType: "string",
            description: "Aula, laboratorio o almacén donde se resguarda"
        },
        fecha_registro: { bsonType: ["date", "null"] },
        fecha_factura: { bsonType: ["date", "null"] },
        num_factura: { bsonType: ["string", "null"] },
        fecha_baja: { bsonType: ["date", "null"] },
        garantia_fecha_fin: { bsonType: ["date", "null"] },
        garantia_estado: {
            enum: ["Vigente", "Vencida", "No Aplica", null]
        },
        aprobado: { bsonType: "bool" },
        aprobado_por: { bsonType: ["objectId", "string", "null"] },
        fecha_aprobacion: { bsonType: ["date", "null"] },
        deleted_at: { bsonType: ["date", "null"] },
        created_at: { bsonType: ["date", "null"] },
        updated_at: { bsonType: ["date", "null"] }
    }
};
crearColeccionConValidador("inventario", inventarioSchema);

// 1.4 Esquema para Colección: SOLICITUD (Préstamos y Movimientos)
const solicitudSchema = {
    bsonType: "object",
    required: ["fecha_solicitud", "id_usuario", "id_receptor", "tipo_movimiento", "estado"],
    properties: {
        _id: { bsonType: "objectId" },
        fecha_solicitud: {
            bsonType: "date",
            description: "Fecha y hora en que se levantó la solicitud"
        },
        id_usuario: {
            bsonType: ["objectId", "string"],
            description: "Usuario solicitante / prestador (users._id)"
        },
        id_receptor: {
            bsonType: ["objectId", "string"],
            description: "Docente, alumno o departamento que recibe (receptor._id)"
        },
        tipo_movimiento: {
            enum: ["Préstamo", "Renta", "Asignación Permanente"],
            description: "Tipo de trámite"
        },
        estado: {
            enum: ["Pendiente", "Autorizado", "Rechazado", "Finalizado", "Cancelado"],
            description: "Estado del ciclo de vida de la solicitud"
        },
        observaciones: { bsonType: ["string", "null"] },
        fecha_autorizacion: { bsonType: ["date", "null"] },
        autorizado_por: { bsonType: ["objectId", "string", "null"] },
        fecha_devolucion_estimada: { bsonType: ["date", "null"] },
        fecha_devolucion_real: { bsonType: ["date", "null"] },
        // Detalle de materiales solicitados embebidos para alta velocidad NoSQL
        detalles: {
            bsonType: "array",
            description: "Lista de items solicitados embebidos en el documento",
            items: {
                bsonType: "object",
                required: ["id_material", "cantidad"],
                properties: {
                    id_material: { bsonType: ["objectId", "string"] },
                    id_inventario: { bsonType: ["objectId", "string", "null"] },
                    cantidad: { bsonType: ["int", "long", "number"], minimum: 1 },
                    observaciones: { bsonType: ["string", "null"] }
                }
            }
        },
        created_at: { bsonType: ["date", "null"] },
        updated_at: { bsonType: ["date", "null"] }
    }
};
crearColeccionConValidador("solicitud", solicitudSchema);

// ------------------------------------------------------------------------------
// 2. CREACIÓN DE ÍNDICES OPTIMIZADOS (createIndex)
// ------------------------------------------------------------------------------
print("\n⚡ PASO 2: Creando índices primarios, únicos y compuestos...");

// Índices en users
targetDb.users.createIndex({ email: 1 }, { unique: true, name: "uniq_users_email" });
targetDb.users.createIndex({ tipo_usuario: 1, activo: 1 }, { name: "idx_users_tipo_activo" });
print("   ✔ Índices en 'users': email (único), [tipo_usuario, activo] (compuesto).");

// Índices en inventario
targetDb.inventario.createIndex({ num_serie: 1 }, { unique: true, name: "uniq_inventario_num_serie" });
targetDb.inventario.createIndex({ estado: 1 }, { name: "idx_inventario_estado" });
targetDb.inventario.createIndex({ id_producto: 1, estado: 1 }, { name: "idx_inventario_prod_estado" });
targetDb.inventario.createIndex({ ubicacion_fisica: 1 }, { name: "idx_inventario_ubicacion" });
print("   ✔ Índices en 'inventario': num_serie (único), estado, [id_producto, estado], ubicacion_fisica.");

// Índices en material
targetDb.material.createIndex({ nombre: 1 }, { name: "idx_material_nombre" });
targetDb.material.createIndex({ stock_actual: 1, stock_minimo: 1 }, { name: "idx_material_stock_alerta" });
print("   ✔ Índices en 'material': nombre, [stock_actual, stock_minimo] (alerta reorden).");

// Índices en solicitud
targetDb.solicitud.createIndex({ fecha_solicitud: -1 }, { name: "idx_solicitud_fecha_desc" });
targetDb.solicitud.createIndex({ estado: 1, fecha_solicitud: -1 }, { name: "idx_solicitud_estado_fecha" });
targetDb.solicitud.createIndex({ id_usuario: 1 }, { name: "idx_solicitud_id_usuario" });
targetDb.solicitud.createIndex({ id_receptor: 1 }, { name: "idx_solicitud_id_receptor" });
print("   ✔ Índices en 'solicitud': fecha_solicitud desc, [estado, fecha], id_usuario, id_receptor.");

// ------------------------------------------------------------------------------
// 3. OPERACIONES CRUD COMPLETAS Y DOCUMENTADAS
// ------------------------------------------------------------------------------
print("\n📝 PASO 3: Ejecutando demostración de operaciones CRUD documentadas...");

// 3.0 IDEMPOTENCIA: Limpieza preventiva de registros previos de prueba
print("   🧹 Limpiando registros previos de prueba para asegurar idempotencia...");
targetDb.users.deleteMany({ email: { $in: ["admin.demo@veracruz.tecnm.mx", "servicio.demo@veracruz.tecnm.mx"] } });
targetDb.inventario.deleteMany({ num_serie: { $in: ["TEC-VER-LAP-001", "TEC-VER-LAP-002", "TEC-VER-PROY-001", "TEMP-DELETE-999"] } });
targetDb.material.deleteMany({ nombre: { $in: ["Laptop Dell Latitude 5420", "Videoproyector Epson PowerLite X49", "Cable UTP Cat 6 (Bobina)", "Conectores RJ45 (Bolsa 100pz)"] } });
targetDb.solicitud.deleteMany({ observaciones: /demostrativo|clase magistral|Arquitectura de Computadoras/i });

// 3.1 CREATE: Inserción de documentos de prueba
const idAdmin = new ObjectId();
const idServicio = new ObjectId();
const idReceptor = new ObjectId();
const idMaterialLaptop = new ObjectId();
const idMaterialProyector = new ObjectId();

// Inserción en users
targetDb.users.insertMany([
    {
        _id: idAdmin,
        name: "Administrador General TecNM",
        email: "admin.demo@veracruz.tecnm.mx",
        password: "$2y$12$K1dZexampleHashedPasswordBcryptSample1234567890ABCDE",
        tipo_usuario: "Administrador",
        activo: true,
        created_at: new Date(),
        updated_at: new Date()
    },
    {
        _id: idServicio,
        name: "Estudiante Servicio Social",
        email: "servicio.demo@veracruz.tecnm.mx",
        password: "$2y$12$K1dZexampleHashedPasswordBcryptSample1234567890ABCDE",
        tipo_usuario: "Servicio Social",
        activo: true,
        created_at: new Date(),
        updated_at: new Date()
    }
]);

// Inserción en material
targetDb.material.insertMany([
    {
        _id: idMaterialLaptop,
        nombre: "Laptop Dell Latitude 5420",
        descripcion: "Intel Core i5 11va Gen, 16GB RAM, SSD 512GB",
        modelo: "Latitude 5420",
        requiere_control_individual: true,
        stock_actual: 5,
        stock_minimo: 2,
        created_at: new Date(),
        updated_at: new Date()
    },
    {
        _id: idMaterialProyector,
        nombre: "Videoproyector Epson PowerLite X49",
        descripcion: "3600 Lúmenes, HDMI, Resolución XGA",
        modelo: "PowerLite X49",
        requiere_control_individual: true,
        stock_actual: 3,
        stock_minimo: 1,
        created_at: new Date(),
        updated_at: new Date()
    }
]);

// Inserción en inventario
targetDb.inventario.insertMany([
    {
        _id: new ObjectId(),
        num_serie: "TEC-VER-LAP-001",
        id_producto: idMaterialLaptop,
        id_usuario: idAdmin,
        estado: "Disponible",
        estado_registro: "Aprobado",
        tipo_propiedad: "Propio",
        ubicacion_fisica: "Centro de Cómputo - Estante A1",
        fecha_registro: new Date(),
        aprobado: true,
        created_at: new Date(),
        updated_at: new Date()
    },
    {
        _id: new ObjectId(),
        num_serie: "TEC-VER-LAP-002",
        id_producto: idMaterialLaptop,
        id_usuario: idAdmin,
        estado: "Disponible",
        estado_registro: "Aprobado",
        tipo_propiedad: "Propio",
        ubicacion_fisica: "Centro de Cómputo - Estante A1",
        fecha_registro: new Date(),
        aprobado: true,
        created_at: new Date(),
        updated_at: new Date()
    },
    {
        _id: new ObjectId(),
        num_serie: "TEC-VER-PROY-001",
        id_producto: idMaterialProyector,
        id_usuario: idAdmin,
        estado: "Disponible",
        estado_registro: "Aprobado",
        tipo_propiedad: "Propio",
        ubicacion_fisica: "Audiovisual Edificio E",
        fecha_registro: new Date(),
        aprobado: true,
        created_at: new Date(),
        updated_at: new Date()
    },
    // Activo para prueba de borrado físico
    {
        _id: new ObjectId(),
        num_serie: "TEMP-DELETE-999",
        id_producto: idMaterialLaptop,
        id_usuario: idAdmin,
        estado: "Baja",
        estado_registro: "Rechazado",
        tipo_propiedad: "Donación",
        ubicacion_fisica: "Almacén Transitorio",
        fecha_registro: new Date(),
        aprobado: false,
        created_at: new Date(),
        updated_at: new Date()
    }
]);

// Inserción en solicitud con items embebidos
const idSolicitudDemo = new ObjectId();
targetDb.solicitud.insertOne({
    _id: idSolicitudDemo,
    fecha_solicitud: new Date(),
    id_usuario: idServicio,
    id_receptor: idReceptor,
    tipo_movimiento: "Préstamo",
    estado: "Pendiente",
    observaciones: "Préstamo para clase magistral de Arquitectura de Computadoras",
    fecha_devolucion_estimada: new Date(Date.now() + 24 * 60 * 60 * 1000), // Mañana
    fecha_devolucion_real: null,
    detalles: [
        {
            id_material: idMaterialLaptop,
            cantidad: 1,
            observaciones: "Con cargador original y maletín"
        },
        {
            id_material: idMaterialProyector,
            cantidad: 1,
            observaciones: "Incluye cable HDMI y control remoto"
        }
    ],
    created_at: new Date(),
    updated_at: new Date()
});

print("   [CREATE] Documentos de prueba insertados en users, material, inventario y solicitud.");

// 3.2 READ: Consultas con filtros y proyecciones
const inventarioDisponible = targetDb.inventario.find(
    { estado: "Disponible", tipo_propiedad: "Propio" },
    { num_serie: 1, ubicacion_fisica: 1, id_producto: 1, _id: 0 }
).toArray();
print(`   [READ] Activos disponibles encontrados: ${inventarioDisponible.length}`);

// 3.3 UPDATE: Actualizaciones atómicas con $set, $inc y $currentDate
// Actualizar estado del activo 'TEC-VER-LAP-001' a 'Asignado'
targetDb.inventario.updateOne(
    { num_serie: "TEC-VER-LAP-001" },
    {
        $set: {
            estado: "Asignado",
            ubicacion_fisica: "Laboratorio de Sistemas - Cubículo 4"
        },
        $currentDate: { updated_at: true }
    }
);

// Decrementar stock atómicamente con $inc en 'material'
targetDb.material.updateOne(
    { _id: idMaterialLaptop },
    {
        $inc: { stock_actual: -1 },
        $currentDate: { updated_at: true }
    }
);
print("   [UPDATE] Activo asignado ($set) y stock decrementado atómicamente ($inc).");

// 3.4 DELETE: Borrado Lógico vs Borrado Físico
// Borrado Lógico (Soft Delete) - Recomendado para auditoría
targetDb.inventario.updateOne(
    { num_serie: "TEC-VER-LAP-002" },
    {
        $set: {
            deleted_at: new Date(),
            estado: "Baja"
        }
    }
);
print("   [DELETE - Soft] Activo 'TEC-VER-LAP-002' marcado como baja lógica (deleted_at).");

// Borrado Físico (Hard Delete) - Purga de registros de prueba
const deleteResult = targetDb.inventario.deleteOne({ num_serie: "TEMP-DELETE-999" });
print(`   [DELETE - Hard] Documento temporal purgado físicamente. Eliminados: ${deleteResult.deletedCount}`);

// ------------------------------------------------------------------------------
// 4. PIPELINES DE AGREGACIÓN AVANZADOS
// ------------------------------------------------------------------------------
print("\n📊 PASO 4: Ejecución de Pipelines de Agregación NoSQL...");

// 4.1 PIPELINE 1: $lookup relacional multidocumento
// Cruza la solicitud con la colección 'users' para obtener los datos del solicitante
// y formatea el reporte de préstamos con proyección limpia.
print("   --- Consulta Agregada 1: $lookup de Solicitud con Usuario Solicitante ---");
const reporteSolicitudes = targetDb.solicitud.aggregate([
    {
        $match: { estado: "Pendiente" }
    },
    {
        $lookup: {
            from: "users",
            localField: "id_usuario",
            foreignField: "_id",
            as: "usuario_solicitante"
        }
    },
    {
        $unwind: {
            path: "$usuario_solicitante",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $project: {
            _id: 1,
            tipo_movimiento: 1,
            estado: 1,
            fecha_solicitud: 1,
            observaciones: 1,
            total_items: { $size: { $ifNull: ["$detalles", []] } },
            solicitante_nombre: "$usuario_solicitante.name",
            solicitante_email: "$usuario_solicitante.email",
            solicitante_rol: "$usuario_solicitante.tipo_usuario"
        }
    }
]).toArray();

printjson(reporteSolicitudes);

// 4.2 PIPELINE 2: $match, $group y cálculo de métricas de inventario por estado
print("\n   --- Consulta Agregada 2: Métricas de Inventario ($group y $match) ---");
const metricasInventario = targetDb.inventario.aggregate([
    // Excluir eliminados lógicamente
    {
        $match: {
            deleted_at: null
        }
    },
    // Agrupar por estado operativo y tipo de tenencia
    {
        $group: {
            _id: {
                estado: "$estado",
                propiedad: "$tipo_propiedad"
            },
            total_activos: { $sum: 1 },
            ubicaciones_distintas: { $addToSet: "$ubicacion_fisica" }
        }
    },
    {
        $project: {
            _id: 0,
            estado_operativo: "$_id.estado",
            tipo_propiedad: "$_id.propiedad",
            total_activos: 1,
            cantidad_ubicaciones: { $size: "$ubicaciones_distintas" }
        }
    },
    {
        $sort: { total_activos: -1 }
    }
]).toArray();

printjson(metricasInventario);

// 4.3 PIPELINE 3 (Extra): Control de Reabastecimiento de Stock ($expr y sanitización de tipos)
print("\n   --- Consulta Agregada 3: Detección de Materiales con Stock Crítico ---");
const stockCritico = targetDb.material.aggregate([
    {
        $match: {
            deleted_at: null,
            $expr: {
                $lte: [
                    { $convert: { input: "$stock_actual", to: "int", onError: 0, onNull: 0 } },
                    { $convert: { input: "$stock_minimo", to: "int", onError: 0, onNull: 0 } }
                ]
            }
        }
    },
    {
        $project: {
            _id: 1,
            nombre: 1,
            modelo: 1,
            stock_actual: { $convert: { input: "$stock_actual", to: "int", onError: 0, onNull: 0 } },
            stock_minimo: { $convert: { input: "$stock_minimo", to: "int", onError: 0, onNull: 0 } },
            deficit: {
                $subtract: [
                    { $convert: { input: "$stock_minimo", to: "int", onError: 0, onNull: 0 } },
                    { $convert: { input: "$stock_actual", to: "int", onError: 0, onNull: 0 } }
                ]
            }
        }
    }
]).toArray();

printjson(stockCritico);

print("\n==================================================================");
print("✅ [SIGEM] Script maestro finalizado exitosamente.");
print("   Base de Datos: " + DB_NAME);
print("   Colecciones creadas con Schema Validation: users, material, inventario, solicitud");
print("   Índices y pipelines de agregación verificados.");
print("==================================================================\n");
