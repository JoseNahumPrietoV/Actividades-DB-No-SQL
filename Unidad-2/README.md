# TechnoGames - Migración a MongoDB Atlas

Este proyecto es la evolución de una tienda de videojuegos llamada **TechnoGames**. Originalmente construida con una arquitectura tradicional (PHP + MySQL), ha sido modernizada para utilizar **MongoDB Atlas (NoSQL)** como motor de base de datos principal, con despliegue en la nube.

## Descripción del Proyecto

El objetivo principal fue migrar un modelo de datos relacional a un modelo documental. Para ello, se rediseñó el esquema, se adaptó el backend y se reescribieron las consultas para aprovechar la flexibilidad y escalabilidad de MongoDB.

### Tecnologías Utilizadas
*   **Backend:** PHP 8.4
*   **Base de Datos:** MongoDB Atlas (Cluster M0 - Nivel Gratuito)
*   **Driver de Conexión:** `mongodb/mongodb` (vía Composer)
*   **Servidor Web:** Apache (Dockerizado)
*   **Despliegue (Deploy):** Railway
*   **Control de Versiones:** Git / GitHub

##  Arquitectura y Esquema

Se migraron 9 tablas relacionales a 7 colecciones documentales. Se aplicaron técnicas de desnormalización selectiva y embebido de datos para optimizar las consultas.

| Tabla Original (MySQL) | Colección Nueva (MongoDB) | Estrategia Aplicada |
| :--- | :--- | :--- |
| `usuarios` | `usuarios` | 1 documento por usuario, con estadísticas embebidas. |
| `juegos` | `juegos` | Requisitos transformados de texto a objeto estructurado. |
| `carrito` / `biblioteca` / `wishlist` | `carritos` / `bibliotecas` / `wishlists` | 1 documento por usuario con un array de ítems/juegos. |
| `ventas` + `orders` + `order_items` | `ordenes` | Se unificaron en un solo documento con ítems embebidos. |
| `reseñas` | `resenas` | Colección separada (relación 1:N con los juegos). |

## Características de Seguridad Implementadas
*   **Cifrado en Tránsito:** Conexión a Atlas forzada mediante TLS 1.2+.
*   **Control de Acceso Basado en Roles (RBAC):** 3 usuarios creados en Atlas con permisos específicos (`readWriteAnyDatabase`, `readWrite`, `read`).
*   **Firewall:** Lista de IPs permitidas (IP Access List) configurada en MongoDB Atlas.
*   **Autenticación:** Contraseñas hasheadas con Bcrypt (Coste 12).

##  Despliegue en Producción

La aplicación está desplegada y funcional en Railway, con HTTPS automático y variables de entorno inyectadas de forma segura.

 **URL del Proyecto en Vivo:** [https://technogames-production.up.railway.app](https://technogames-production.up.railway.app)

##  Estructura del Repositorio (Unidad 2)

*   `config/`: Archivos de configuración (Singleton de conexión a MongoDB, helpers).
*   `docs/`: Documentación técnica (Informe PDF, diagramas).
*   `migration/`: Scripts de migración ETL (`migrate.php`) y validación cruzada (`validate.php`).
*   `*.php`: Archivos de la lógica del negocio (Backend y Vistas).
*   `Dockerfile` / `railway.toml`: Configuración para el despliegue en la nube.

##  Instalación y Configuración Local (Opcional)

Si deseas correr este proyecto localmente:

1. Clona este repositorio.
2. Instala las dependencias de PHP:
   ```bash
   composer install
