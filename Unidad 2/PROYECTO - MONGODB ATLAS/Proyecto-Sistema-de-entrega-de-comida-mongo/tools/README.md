# Herramientas y Scripts Auxiliares - Snackver

Esta carpeta alberga scripts de diagnóstico, automatización de base de datos NoSQL, pruebas unitarias y utilidades de mantenimiento, manteniéndolas completamente aisladas de los directorios de producción (`Front-end` y `Back-end`).

---

## 1. Catálogo de Scripts Disponibles

| Script / Archivo | Propósito | Comando de Ejecución |
|---|---|---|
| **`verificar_sistema.php`** | Diagnóstico integral del 100% de los componentes (PHP, extensiones, Atlas TLS, colecciones, .env, Apache e IDE). Ejecutado por la IA en segundo plano. | `php tools/verificar_sistema.php` |
| **`tools/drivers/`** | Repositorio local de drivers binarios portables (`php_mongodb.dll` v2.5.3 TS x64) para configuración sin internet en cualquier PC. | N/A (Uso del asistente/entorno) |
| **`inicializar_solo_admin.php`** | Limpia las 8 colecciones operativas y deja la base de datos en estado base con únicamente el Administrador inicial (`root@root.com`). | `php tools/inicializar_solo_admin.php` |
| **`limpiar_datos_atlas.php`** | Vacía al 100% todas las colecciones en MongoDB Atlas (útil para reiniciar pruebas desde cero). | `php tools/limpiar_datos_atlas.php` |
| **`seed_mongo_initial_data.php`** | Puebla MongoDB Atlas con datos reales de prueba (categorías, platillos, vendedor, cliente, pedidos activos y reseñas). | `php tools/seed_mongo_initial_data.php` |
| **`test_mongo_connection.php`** | Diagnóstico rápido de conexión con MongoDB Atlas, midiendo latencia y validando TLS 1.3. | `php tools/test_mongo_connection.php` |
| **`test_smtp_email.php`** | Prueba de despacho de correos 2FA (código OTP de 6 dígitos) vía Puente HTTPS (Puerto 443) y respaldo Gmail SMTPS (Puerto 465) con la plantilla corporativa oficial. | `php tools/test_smtp_email.php [correo_destino]` |
| **`test_cloudinary_upload.php`** | Prueba de subida de imágenes a la nube con Cloudinary SDK y validación de URL HTTPS devuelta. | `php tools/test_cloudinary_upload.php` |
| **`optimizar_indices_atlas.php`** | Crea y asegura de forma idempotente los índices compuestos en MongoDB Atlas para acelerar notificaciones y consultas de pedidos. | `php tools/optimizar_indices_atlas.php` |
| **`test_optimizaciones_integral.php`** | Suite integral de validación de las 6 capas de optimización: índices Atlas, proyecciones, sondeo con `with_orders=0` y liberación de sesiones. | `php tools/test_optimizaciones_integral.php` |
| **`test_notificaciones_y_resenas.php`** | Prueba unitaria y funcional de `NotificacionService` (deduplicación, tiempo real) y reseñas de platillos. | `php tools/test_notificaciones_y_resenas.php` |
| **`test_historial_y_eliminacion.php`** | Prueba unitaria y de integración para `HistorialService` y el ciclo de eliminación con auditoría en Atlas. | `php tools/test_historial_y_eliminacion.php` |
| **`test_app_flow.php`** | Prueba de flujo completo de navegación, carrusel de reseñas y agregaciones de catálogo NoSQL. | `php tools/test_app_flow.php` |
| **`agregar_categorias_y_resenas.php`** | Inserta de forma idempotente las 5 categorías oficiales y 4 reseñas de muestra en MongoDB Atlas sin alterar otras colecciones. | `php tools/agregar_categorias_y_resenas.php` |
| **`test_atlas_sync_lifecycle.php`** | Prueba de ciclo completo de sincronización e invalidación instantánea entre MongoDB Atlas y las vistas del sistema. | `php tools/test_atlas_sync_lifecycle.php` |
| **`migrar_mysql_a_mongo.php`** | Script de migración asistida para transformar volcados relacionales de MySQL hacia las colecciones de MongoDB Atlas. | `php tools/migrar_mysql_a_mongo.php` |
| **`generar_diagramas_sobrios.php`** | Genera programáticamente los 10 diagramas técnicos sobrios en PNG de alta resolución con GD, espaciado perfecto y separación de colecciones. | `php tools/generar_diagramas_sobrios.php` |
| **`generar_documento_tecnico_completo.php`** | Construye y compila el documento técnico oficial de la Unidad 2 en formato `.docx` (en raíz y `documentacion/`) con los 10 diagramas y OpenXML nativo. | `php tools/generar_documento_tecnico_completo.php` |

---

## 2. Buenas Prácticas y Aislamiento

1. **Aislamiento de Producción:** Ningún archivo de este directorio debe ser referenciado o invocado directamente desde las vistas públicas del cliente (`Front-end/`) ni desde los controladores finales (`Back-end/`).
2. **Uso de Variables de Entorno:** Todos los scripts cargan automáticamente la configuración desde `Back-end/.env`. No requieren ingresar credenciales en duro (*hardcoded*).
3. **Ejecución vía CLI:** Los scripts están optimizados para ejecutarse en terminal (PowerShell, Bash o CMD) mostrando mensajes claros de estado y salida formateada.
