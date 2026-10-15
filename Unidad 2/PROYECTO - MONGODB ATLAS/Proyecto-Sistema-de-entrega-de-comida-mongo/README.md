# 🍔 Snackver — Sistema Web de Entrega de Comida Universitaria

> *"Comida entre universitarios — Hecha por estudiantes, para estudiantes"*

Plataforma web de comercio electrónico y coordinación de entrega de alimentos dentro del campus universitario. Permite que estudiantes emprendedores registren sus tiendas digitales, gestionen pedidos en tiempo real y que otros estudiantes exploren el menú del campus, ordenen comida y coordinen entregas rápidas y seguras.

---

## 🌟 Características Principales

- **Arquitectura 100% NoSQL en la Nube:** Operación completa sobre **MongoDB Atlas** con documentos embebidos y réplicas automáticas, eliminando cuellos de botella relacionales y tablas intermedias.
- **Servicios Oficiales del GitHub Student Developer Pack:**
  - 🍃 **MongoDB Atlas:** Cluster NoSQL gestionado en la nube con cifrado en reposo AES-256 (\$0 con Student Pack).
  - 🌐 **Namecheap:** Dominio universitario personalizado (`.me`) y Certificado SSL TLS 1.3 (\$0 con Student Pack).
- **Seguridad Multicapa & 2FA Instantáneo (0 ms):** Hashing de contraseñas con `bcrypt` y verificación obligatoria en dos pasos mediante códigos OTP de 6 dígitos enviados por correo electrónico desde la cuenta oficial de Gmail mediante puente HTTPS (Puerto 443 compatible con Render Free Tier) y respaldo SMTPS directo (Puerto 465 SSL en local). La apertura de la ventana modal OTP ocurre en **0 ms** desacoplando la generación en MongoDB del despacho de correo en segundo plano.
- **Almacenamiento y Compresión de Imágenes en la Nube:** Previsualización instantánea en **0 ms** (`URL.createObjectURL`), compresión automática en el navegador (`<canvas>`) e integración nativa con **Cloudinary** (`/upload/f_auto,q_auto/`) para entrega ultrarrápida por HTTPS.
- **Sincronización Global en Tiempo Real (0 ms, Cero Recargas):**
  - Motor diferencial (`Back-end/conexion/sincronizar_tiempo_real.php` + `Front-end/js/tiempo_real.js`) con comparación de hash MD5 cada 2 segundos y al enfocar la pestaña (`focus` / `visibilitychange`), actualizando el DOM sin parpadeos ni `location.reload()` únicamente cuando cambia algún dato en MongoDB Atlas.
- **Módulos Independientes Actualizados en Vivo:**
  - 🛒 **Cliente:** Exploración por categorías con precarga predictiva (`InstantClick`), búsqueda de tiendas en vivo sin recargar, carrito interactivo sincronizado entre pestañas, cancelación y pago SPEI de pedidos en 0 ms y publicación AJAX de reseñas (públicas o anónimas).
  - 🏪 **Vendedor:** Dashboard con 3 KPIs en tiempo real (`#kpi-pendientes`, `#kpi-total-general`, `#kpi-total-productos`), catálogo de platillos con stock en vivo, gestión reactiva del ciclo de vida de pedidos y filtro instantáneo en 0 ms.
  - 🛡️ **Administrador:** Panel de métricas globales en vivo, gestión de usuarios (clientes/vendedores) y categorías en 0 ms sin recargar la página, y bitácora de auditoría `historial_usuarios.php` 100% adaptable (responsive card-table en móviles).
- **Rendimiento y Reducción de Latencia:**
  - Compresión Wire nativa (`snappy,zlib`) en la conexión del driver MongoDB con Atlas en AWS.
  - 36 índices simples, únicos y compuestos en las 8 colecciones de Atlas, eliminando escaneos en memoria.
  - Polling inteligente bajo demanda (`with_orders=0`) y sincronización diferencial por hash MD5 (`status: unchanged`).
  - Liberación temprana de bloqueos de sesión PHP (`session_write_close`) para navegación paralela sin congelamientos.
  - Proyecciones quirúrgicas (`projection`) en todas las consultas y caché estático de recursos (`?v=2.3` + `preconnect`).
- **Despliegue en Producción 24/7:** En vivo en **[https://snackver.me](https://snackver.me)** y **[https://www.snackver.me](https://www.snackver.me)** mediante contenedor **Docker** en **Render**, dominio y DNS en **Namecheap**, y Keep-Alive activo en **cron-job.org**.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 8.2 (Extensiones: `mongodb`, `gd`, `zip`, `openssl`, `curl`)
- **Base de Datos:** MongoDB Atlas (Cluster NoSQL con TLS 1.3, réplicas y compresión wire `snappy,zlib`)
- **Servidor Web:** Apache 2.4 (`mod_rewrite`, `mod_deflate`, `mod_expires`, `mod_headers`)
- **Gestión de Dependencias:** Composer (`mongodb/mongodb`, `cloudinary/cloudinary_php`, `phpmailer/phpmailer`, `vlucas/phpdotenv`)
- **Servicio de Correo:** Gmail Oficial mediante Puente HTTPS (Puerto 443 Google Apps Script para la nube) + `PHPMailer` SMTPS directo (Puerto 465 SSL en local)
- **Frontend:** HTML5 semántico, CSS3 modular y JavaScript moderno (Fetch / AJAX)

---

## 🚀 Guía de Instalación y Ejecución Paso a Paso

A continuación se detallan los pasos para clonar, configurar y ejecutar el proyecto localmente desde cero:

### 1. Prerrequisitos del Sistema
- **PHP 8.2 o superior** (incluido en XAMPP reciente o instalado de forma independiente).
- **Extensiones de PHP requeridas:** `mongodb`, `gd`, `zip`, `openssl`, `curl`, `mbstring`.
- **Composer 2.x** (para gestionar librerías oficiales de PHP).
- **Git** (para clonar el repositorio).
- **Base de Datos:** Clúster activo en MongoDB Atlas (o URI de conexión compatible).
- **Servidor Web:** Apache (vía XAMPP), Servidor Embebido de PHP o Docker.

---

### 2. Clonar el Repositorio
Abre tu terminal en el directorio raíz de tu servidor local (ej. `C:\xampp\htdocs\` en Windows):
```bash
cd C:\xampp\htdocs
git clone https://github.com/juanito2255/juanito2255.github.io.git Proyecto-Sistema-de-entrega-de-comida-mongo
cd Proyecto-Sistema-de-entrega-de-comida-mongo
```

---

### 3. Instalar Dependencias de Backend (Composer)
El núcleo de librerías oficiales (`mongodb/mongodb`, `phpmailer/phpmailer`, `cloudinary/cloudinary_php`, `vlucas/phpdotenv`) reside en `Back-end/`:
```bash
cd Back-end
composer install --no-interaction
cd ..
```

---

### 4. Configurar Variables de Entorno (`.env`)
Copia la plantilla de ejemplo y ajusta las credenciales en `Back-end/.env`:
```bash
cp Back-end/.env.example Back-end/.env
```
Abre `Back-end/.env` con tu editor preferido y define tus credenciales:
- `MONGO_URI`: Cadena segura `mongodb+srv://...` provista por MongoDB Atlas.
- `MONGO_DB_NAME`: `sistemawebsnackver`.
- `SMTP_USER` y `SMTP_PASS`: Cuenta y Contraseña de Aplicación de 16 caracteres de Gmail (para despacho de códigos 2FA OTP).
- `CLOUDINARY_*`: Nombre de nube, API Key y API Secret de tu cuenta de Cloudinary (para subida y optimización de fotos de platillos y tiendas).

---

### 5. Habilitar el Driver Nativo de MongoDB (`ext-mongodb`)
- **En Windows (XAMPP):**
  1. El proyecto ya incluye el archivo binario portable en `tools/drivers/php_mongodb.dll`.
  2. Cópialo al directorio de extensiones de tu PHP (ej. `C:\xampp\php\ext\php_mongodb.dll`).
  3. Abre tu archivo `C:\xampp\php\php.ini` y añade o descomenta las siguientes líneas:
     ```ini
     extension=mongodb
     extension=gd
     ```
- **En Linux / macOS:**
  ```bash
  sudo pecl install mongodb
  # Agrega 'extension=mongodb.so' a tu archivo php.ini
  ```

---

### 6. Diagnóstico y Verificación Automatizada (1 Clic)
El proyecto incluye un script de diagnóstico que valida automáticamente la versión de PHP, extensiones, carga del `.env`, conexión TLS 1.3 con MongoDB Atlas y sincronización de las 8 colecciones:
```bash
php tools/verificar_sistema.php
```
> ✅ Deberás obtener un resultado de **100% de componentes operativos** y **8/8 colecciones sincronizadas**.

---

### 7. Inicializar o Sembrar Datos en MongoDB Atlas (Opcional)
- **Para pruebas inmediatas con catálogo y usuarios de demostración (Recomendado):**
  ```bash
  php tools/seed_mongo_initial_data.php
  ```
  *(Puebla las 5 categorías oficiales, platillos de prueba, cuenta de vendedor con tienda digital, órdenes activas y reseñas).*
- **Para un inicio limpio únicamente con el Administrador:**
  ```bash
  php tools/inicializar_solo_admin.php
  ```
- **Para asegurar los 36 índices B-Tree de rendimiento:**
  ```bash
  php tools/optimizar_indices_atlas.php
  ```

---

### 8. Métodos de Ejecución del Sistema

#### Opción A: Mediante XAMPP (Recomendado en Windows)
1. Inicia el módulo **Apache** en el Panel de Control de XAMPP.
2. Abre tu navegador web e ingresa a:
   ```
   http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/
   ```

#### Opción B: Mediante Servidor Embebido de PHP (Rápido / CLI)
Desde la raíz del repositorio puedes iniciar un servidor de desarrollo en un comando:
```bash
php -S localhost:8000
```
Ingresa desde tu navegador a `http://localhost:8000/`.

#### Opción C: Mediante Contenedor Docker Oficial
El repositorio incluye un `Dockerfile` optimizado sobre `php:8.2-apache` con extensiones PECL compiladas, Composer y reglas de Apache:
```bash
# 1. Construir la imagen Docker
docker build -t snackver-app .

# 2. Levantar el contenedor en el puerto 8080
docker run -d -p 8080:80 --name snackver snackver-app
```
Ingresa desde tu navegador a `http://localhost:8080/`.

---

### 9. Cuentas de Demostración para Pruebas
| Rol | Correo Electrónico | Contraseña | Características |
|---|---|---|---|
| **Administrador** | `root@root.com` | `root` | Acceso al panel administrativo, gestión de usuarios, auditoría forense e inventario global. |
| **Vendedor** | `vendedor@snackver.com` *(o sembrado con seed)* | `123456` | Dashboard con 3 KPIs en vivo, despacho de pedidos y catálogo de platillos. |
| **Cliente** | Registro vía formulario | Libre | Registro en `Front-end/registro.php` con verificación OTP de 6 dígitos enviada por correo y gestión de compras. |

---

## ☁️ Despliegue en la Nube en Vivo (Render + Namecheap + MongoDB Atlas)

Arquitectura 100% gratuita y activa en producción 24/7:
1. **Servidor Cloud:** Web Service en **Render** (`https://juanito2255-github-io.onrender.com`) ejecutando el `Dockerfile` oficial con **PHP 8.2 + Apache + Composer + PECL mongodb**.
2. **Dominio Propio con SSL:** **[https://snackver.me](https://snackver.me)** y **[https://www.snackver.me](https://www.snackver.me)** de **Namecheap** (GitHub Student Pack) vinculados en Render con certificado SSL automático (HTTPS con candado verde).
3. **Base de Datos NoSQL:** **MongoDB Atlas** conectado de forma transparente por TLS 1.3.
4. **Keep-Alive 24/7:** Monitoreo activo con **cron-job.org** (ping cada 10 min a `https://snackver.me/`) para evitar el modo reposo del nivel gratuito.
> 📘 **Guía paso a paso detallada:** Consulta [Guía de Despliegue en Producción (Render + Namecheap)](documentacion/DESPLIEGUE_RENDER_NAMECHEAP.md).

---

## 🌐 Despliegue en Namecheap (cPanel)

Para subir el proyecto a un hosting compartido de Namecheap con dominio universitario (`.me`):
1. **Versión de PHP:** En tu cPanel ve a **Select PHP Version** y elige **PHP 8.2**.
2. **Extensiones:** En la pestaña **Extensions**, activa **`mongodb`**, **`gd`**, **`zip`**, **`fileinfo`** y **`openssl`**.
3. **Subida:** Comprime los archivos en un `.zip` y extráelos en el directorio `public_html/`.
4. **Variables de Entorno:** Configura `public_html/Back-end/.env` con tus credenciales seguras.
5. **Firewall Atlas:** En MongoDB Atlas ve a **Network Access** y habilita la regla `0.0.0.0/0` para autorizar las conexiones desde el hosting.

---

## 📁 Documentación Adicional

- 📕 **[Documento Técnico Oficial — NoSQL (PDF)](Documento%20Tecnico%20-%20Nosql.pdf)**: Reporte académico y de ingeniería completo (30 páginas formalmente maquetadas) con Hoja de Presentación (TecNM / Instituto Tecnológico de Veracruz), Índice de Contenido, Introducción, Desarrollo técnico paso a paso de los 8 requisitos, 12 Diagramas BSON y Arquitectura Cloud, Catálogo Completo de los 36 Índices en MongoDB Atlas, Catálogo de las 24 Vistas Programadas con 27 capturas reales del sistema en producción (`snackver.me`), y Conclusiones.
- 📄 **[Documento Técnico Editable (Word `.docx`)](Documento%20Tecnico%20-%20Nosql.docx)** *(también disponible en [`documentacion/DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx`](documentacion/DOCUMENTO_TECNICO_SNACKVER_UNIDAD2.docx))*: Versión editable en formato Microsoft Word del reporte entregable de la Unidad 2.
- 🚀 [Guía de Despliegue en Producción (Render + Namecheap + MongoDB Atlas)](documentacion/DESPLIEGUE_RENDER_NAMECHEAP.md): Despliegue 100% gratuito en la nube con dominio propio, SSL, keep-alive y gestión de encendido/pausa (*Resume / Suspend*).
- 📘 [Documentación Técnica Detallada (Markdown)](documentacion/DOCUMENTACION_TECNICA.md): Arquitectura interna, especificación técnica de endpoints y diagramas en código Mermaid.
- 📊 [Diagramas de Base de Datos y Esquemas NoSQL](documentacion/DIAGRAMAS_BD.md): Mapeo relacional vs NoSQL, arquitectura de réplicas y definición formal de colecciones BSON.
- 🔧 [Catálogo de Scripts y Herramientas](tools/README.md): Catálogo de 14 utilidades CLI para migración, diagnóstico, sembrado de datos y pruebas.
- 📋 [Bitácora Cronológica de Cambios](CONTEXTO_Y_CAMBIOS.md): Historial completo de intervenciones, auditorías y estado del sistema.
- 📜 [Reglas de Desarrollo](REGLAS.md): Directivas y buenas prácticas del repositorio.
