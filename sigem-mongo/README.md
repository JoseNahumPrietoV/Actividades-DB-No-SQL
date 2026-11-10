# SIGEM — Sistema de Gestión de Equipos y Materiales

Sistema integral para el control de inventario, gestión de préstamos, rentas y seguimiento de mantenimiento para el equipamiento del TecNM Campus Veracruz. El sistema centraliza la operación de activos y proporciona interfaces distintas para administradores y prestadores de servicio social.

## Stack Tecnológico

- **Framework:** Laravel 12
- **Admin Panel:** Filament 3.3
- **Roles & Permisos:** Spatie Laravel Permission
- **Auditoría:** Spatie ActivityLog
- **Estilos:** Tailwind CSS 4
- **Base de Datos:** MySQL 8

## Requisitos Previos

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL 8

## Instalación

1. Clona el repositorio:
   ```bash
   git clone [repo]
   ```
2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```
3. Copia el archivo de entorno y configura tu base de datos:
   ```bash
   cp .env.example .env
   ```
4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```
5. Ejecuta las migraciones:
   ```bash
   php artisan migrate
   ```
6. Siembra la base de datos (Roles, Permisos y Usuarios):
   ```bash
   php artisan db:seed
   ```
7. Instala y compila los assets del frontend:
   ```bash
   npm install
   npm run build
   ```
8. Inicia el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

## Usuarios de Prueba

El sistema cuenta con dos paneles separados utilizando el sistema de autenticación nativo de Filament. Inicia sesión en las siguientes URLs:

- **Administrador:** 
  - URL de acceso: `http://localhost:8000/admin/login`
  - Correo: `admin@tecnm.edu.mx` 
  - Contraseña: `admin123` 
- **Servicio Social:** 
  - URL de acceso: `http://localhost:8000/servicio-social/login`
  - Correo: `servicio@tecnm.edu.mx` 
  - Contraseña: `servicio123` 

## Estructura del Proyecto

| Directorio | Descripción |
|---|---|
| `app/Models/` | Modelos Eloquent. Los modelos de **catálogos utilizan Soft Deletes** para evitar pérdida de registros. |
| `app/Enums/` | Contiene `RoleEnum` que centraliza los nombres de roles y permisos (evita *magic strings*). |
| `app/Policies/` | Políticas estrictas de acceso que limitan acciones según permisos de Spatie. |
| `app/Filament/Resources/` | Resources del panel Admin (Acceso total). |
| `app/Filament/ServicioSocial/Resources/` | Resources del panel Servicio Social. |
| `app/Observers/` | Observers puros para lógica de negocio (control de inventario, sincronización de estados). |
| `resources/views/` | Vistas Blade para landing page y hooks visuales de autenticación. |
| `database/seeders/` | Seeders responsables de cargar roles, permisos iniciales y usuarios de prueba. |

## Módulos del Sistema

### Panel Admin (Acceso Total)
1. Inventario
2. Solicitudes (Préstamos/Rentas)
3. Mantenimiento
4. Departamentos
5. Áreas
6. Materiales
7. Marcas de Material
8. Tipos de Material
9. Unidades de Medida
10. Proveedores
11. Receptores
12. Usuarios

*(La bitácora de sistema ahora se maneja invisiblemente en todo el proyecto a través de Spatie ActivityLog).*

### Panel Servicio Social (Operaciones con Supervisión)
1. Inventario
2. Solicitudes
3. Mantenimiento
4. Materiales
5. Receptores
6. Marcas de Material
7. Tipos de Material
8. Unidades de Medida
9. Áreas

## Roles y Permisos (Spatie)

La autorización del sistema utiliza las mejores prácticas de **Spatie Permission**:
- Las verificaciones en el código se realizan contra permisos específicos (usando `hasPermissionTo()`) en conjunto con un `RoleEnum`.
- **Administrador:** Cuenta con permisos totales y el permiso global de panel `access_admin_panel`. Autoriza solicitudes y edita históricos libremente.
- **Servicio Social:** Cuenta con un subconjunto de permisos y el permiso global `access_servicio_social_panel`. Sus permisos se enfocan en registro rápido y visualización. Su actividad es auditada por `Spatie ActivityLog`.

## Cambios importantes para el equipo

### Nuevas URLs de acceso
- Administración: /admin/login
- Servicio Social: /servicio-social/login
- La URL unificada /login ya no existe

### Convención para nuevos módulos
Si creas un módulo nuevo:
1. Crea el permiso en database/seeders/RolesAndPermissionsSeeder.php
2. Agrega el permiso al enum app/Enums/RoleEnum.php
3. Usa hasPermissionTo() en Policies, NO hasRole()
4. Asigna el permiso al rol correspondiente

### Lógica de inventario
El control de stock (increment/decrement) vive centralizado en los Observers:
- app/Observers/InventarioObserver.php
- app/Observers/SolicitudObserver.php

NO hagas restas o sumas manuales de stock en controladores o Resources. Los Observers se encargan automáticamente.

### Deploy en Railway
- Pre-deploy: migrate + filament cache + icons cache
- APP_DEBUG: false en producción
- Seeder: php artisan db:seed --class=RolesAndPermissionsSeeder
