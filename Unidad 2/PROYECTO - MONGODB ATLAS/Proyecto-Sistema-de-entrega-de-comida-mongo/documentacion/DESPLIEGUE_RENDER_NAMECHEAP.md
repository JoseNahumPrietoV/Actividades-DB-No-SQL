# 🚀 Guía de Despliegue en Producción: Render + Namecheap + MongoDB Atlas

> **Snackver — Sistema Web de Entrega de Comida Universitaria**  
> *Documento técnico de despliegue en la nube (100% Gratuito, Sin Tarjeta de Crédito, Con Dominio Universitario y SSL).*

---

## 📋 Arquitectura del Despliegue

| Componente | Servicio | Rol en el Proyecto | Beneficio / Costo |
|---|---|---|---|
| **Dominio Personalizado** | **Namecheap** (`snackver.me`) | Dirección oficial del sistema | **GitHub Student Pack** (\$0) |
| **Base de Datos NoSQL** | **MongoDB Atlas** | Cluster cloud M0 (TLS 1.3, réplicas) | **GitHub Student Pack** (\$0) |
| **Servidor / Hosting Web** | **Render.com** (Web Service Docker) | Servidor Apache + PHP 8.2 + `mongodb` | Plan Free permanente (\$0) |
| **Imágenes en la Nube** | **Cloudinary** | Almacenamiento y optimización de platillos | Nivel gratuito (\$0) |
| **Autenticación 2FA (OTP)**| **Gmail SMTP** | Envío de códigos de verificación de 6 dígitos | Gratuito con Contraseña de Aplicación |
| **Keep-Alive (Anti-Reposo)** | **cron-job.org** / **UptimeRobot** | Pings cada 10 min para evitar que el servidor se duerma | 100% Gratuito (\$0) |

---

## 🛠️ Paso 1: Archivos de Configuración para Render (Docker)

Render construirá y ejecutará la aplicación mediante un contenedor Docker estándar de PHP 8.2 con Apache y la extensión nativa de MongoDB.

### 1.1. Archivo `Dockerfile` (En la raíz del proyecto)
Crea o verifica que exista el archivo `Dockerfile` en la raíz (`/Dockerfile`):

```dockerfile
FROM php:8.2-apache

# 1. Instalar dependencias del sistema y librerías de compilación
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install gd zip \
    && a2enmod rewrite headers deflate expires \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Habilitar AllowOverride All en Apache y configurar PHP para producción (output_buffering y JSON limpio)
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && printf "output_buffering = 4096\ndisplay_errors = Off\nlog_errors = On\ndate.timezone = America/Mexico_City\n" > /usr/local/etc/php/conf.d/snackver.ini

# 4. Copiar el código fuente del proyecto al directorio web de Apache
COPY . /var/www/html/

# 5. Instalar dependencias de PHP (MongoDB SDK, PHPMailer, Cloudinary, Dotenv) en Back-end/vendor
RUN composer install --working-dir=/var/www/html/Back-end --no-dev --optimize-autoloader --no-interaction

# 6. Asegurar permisos correctos para el usuario de Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 7. Puerto estándar HTTP
EXPOSE 80

CMD ["apache2-foreground"]
```

### 1.2. Archivo `.dockerignore` (En la raíz del proyecto)
Para evitar subir archivos pesados e innecesarios al contenedor:

```dockerignore
.git
.vscode
snackver_alwaysdata.zip
tools/drivers/php_mongodb.dll
servidor - codigos de backup.pdf
documentacion/legacy_mysql
```

---

## 🐙 Paso 2: Subir el Proyecto a GitHub

Render se conecta directamente a tu repositorio de GitHub para compilar y desplegar automáticamente en cada cambio.

1. Abre tu terminal de **PowerShell** en la carpeta del proyecto:
   ```powershell
   cd C:\xampp\htdocs\Proyecto-Sistema-de-entrega-de-comida-mongo
   ```
2. Inicializa el repositorio Git (si aún no lo has hecho):
   ```powershell
   git init
   git add .
   git commit -m "feat: preparar proyecto para despliegue en Render con Docker"
   git branch -M main
   ```
### 2.1. Blindaje con Repositorio Privado y Acceso Exclusivo a Render (GitHub App)
Para proteger el código fuente, reportes y esquemas del proyecto manteniendo el despliegue automático:
1. **Configurar el Repositorio como Privado en GitHub:**
   - Entra a tu repositorio en GitHub $\rightarrow$ pestaña **Settings**.
   - Baja a la sección **Danger Zone** $\rightarrow$ **Change repository visibility** $\rightarrow$ Selecciona **Change to private**.
2. **Conceder Permisos Delegados a Render:**
   - En GitHub, ve a tu foto de perfil (arriba a la derecha) $\rightarrow$ **Settings** $\rightarrow$ **Applications** $\rightarrow$ pestaña **Installed GitHub Apps**.
   - Localiza la aplicación **Render** y haz clic en **Configure**.
   - En la sección **Repository access**, selecciona **"All repositories"** o añade explícitamente tu repositorio (`juanito2255/juanito2255.github.io`) y haz clic en **Save**.
   - Render podrá clonar, compilar y desplegar automáticamente cada commit sin que tu código sea visible públicamente en internet.

---

## ☁️ Paso 3: Crear el Web Service en Render

1. Entra a **[render.com](https://render.com/)** e inicia sesión haciendo clic en **GitHub**.
2. En tu Dashboard principal, haz clic en el botón azul **"New +"** y selecciona **"Web Service"**.
3. Selecciona la opción **"Build and deploy from a Git repository"** y elige tu repositorio de GitHub.
4. Completa la configuración básica:
   * **Name:** `snackver` (o el nombre que prefieras).
   * **Region:** `Ohio (US East)` o `Oregon (US West)` (la más cercana).
   * **Branch:** `main`.
   * **Runtime:** Selecciona **Docker** (Render detectará automáticamente tu `Dockerfile`).
   * **Instance Type:** Selecciona **Free (\$0 / month)**.

### 3.1. Configurar las Variables de Entorno en Render
En esa misma pantalla de creación, baja a la sección **"Environment Variables"** y añade tus claves de producción (copiadas de tu `Back-end/.env`):

| Clave (Key) | Valor (Value) |
|---|---|
| `APP_ENV` | `production` |
| `APP_URL` | `https://snackver.me` |
| `APP_DOMAIN` | `snackver.me` |
| `MONGO_URI` | `mongodb+srv://<usuario>:<password>@cluster72966.ceqk0dl.mongodb.net/sistemawebsnackver?authSource=admin&retryWrites=true&w=majority` *(Copia de tu `Back-end/.env`)* |
| `MONGO_DB_NAME` | `sistemawebsnackver` |
| `SMTP_HOST` | `smtp.gmail.com` |
| `SMTP_PORT` | `465` |
| `SMTP_SECURE` | `ssl` |
| `SMTP_USER` | `juanvicenteherreravivanco.ver1@gmail.com` |
| `SMTP_PASS` | `<TU_SMTP_PASS_DE_16_LETRAS>` *(Copia de tu `Back-end/.env`)* |
| `SMTP_FROM_EMAIL` | `juanvicenteherreravivanco.ver1@gmail.com` |
| `SMTP_FROM_NAME` | `Snackver` |
| `CLOUDINARY_CLOUD_NAME` | `nchcocqu` |
| `CLOUDINARY_API_KEY` | `266499623648763` |
| `CLOUDINARY_API_SECRET` | `<TU_CLOUDINARY_API_SECRET>` *(Copia de tu `Back-end/.env`)* |
| `CLOUDINARY_UPLOAD_FOLDER` | `snackver_platillos` |

5. Haz clic en **"Create Web Service"**.
6. Render comenzará a compilar el Dockerfile. En 3 a 5 minutos verás el mensaje verde: **"Your service is live"**. Render te dará una URL temporal como `https://snackver.onrender.com`.

---

## 🌐 Paso 4: Conectar tu Dominio de Namecheap (`snackver.me`)

Para que la dirección de tu página sea tu dominio oficial de Namecheap con certificado SSL automático:

### 4.1. En Render:
1. Dentro de tu Web Service en Render, ve a la pestaña **"Settings"** en el menú izquierdo.
2. Baja hasta la sección **"Custom Domains"** y haz clic en **"Add Custom Domain"**.
3. Escribe tu dominio: **`snackver.me`** y haz clic en *Save*.
4. Agrega también: **`www.snackver.me`** y haz clic en *Save*.
5. Render te mostrará los registros DNS exactos que debes poner en Namecheap (normalmente un registro **A** o **ANAME** para `@`, y un **CNAME** para `www` apuntando a `tu-servicio.onrender.com`).

### 4.2. En Namecheap:
1. Entra a tu cuenta de Namecheap y ve a **Domain List** > **Manage** en `snackver.me`.
2. En la pestaña **Domain**, asegúrate de que **NAMESERVERS** esté en **Namecheap BasicDNS** (haz clic en la palomita verde ✓ para guardar).
3. Ve a la pestaña **Advanced DNS** y en la tabla **Host Records**:
   * Elimina cualquier registro antiguo de *Parking Page*, *URL Redirect Record* o validación previa (`_jcat... dcv.ssl.com.`).
   * Asegúrate de que los botones de la columna **HTTPS** estén apagados en gris (**`HTTPS OFF`**), ya que Render gestiona su propio certificado SSL.
   * Agrega estos **2 registros exactos**:
     * **Tipo:** `A Record` | **Host:** `@` | **Value:** `216.24.57.1` | **HTTPS:** `HTTPS OFF`
     * **Tipo:** `CNAME Record` | **Host:** `www` | **Value:** `juanito2255-github-io.onrender.com.` | **HTTPS:** `HTTPS OFF`
4. Haz clic en la palomita verde ✓ para guardar.
5. **Verificación y SSL Automático en Render:** Regresa a Render > **Settings** > **Custom Domains**, haz clic en **"Verify"** junto a `snackver.me` y `www.snackver.me`. En pocos minutos Render emitirá automáticamente el certificado SSL oficial (Let's Encrypt) con candado verde HTTPS.

---

## ⚡ Paso 5: Evitar el Modo Reposo (Keep-Alive con cron-job.org)

Render duerme las aplicaciones gratuitas si pasan 15 minutos sin visitas. Para que tu aplicación **esté despierta 24/7 y responda en milisegundos en todo momento**:

1. Entra a **[cron-job.org](https://cron-job.org/)** y regístrate gratis con tu correo.
2. En tu panel, haz clic en **"Create Cronjob"** (Crear tarea cron).
3. Configura los siguientes campos:
   * **Title:** `Snackver Keep-Alive`
   * **URL:** `https://snackver.me/`
   * **Execution schedule:** Selecciona **"Every 10 minutes"** (Cada 10 minutos).
4. Haz clic en **Create** (Crear).
5. **¡Listo!** El bot visitará tu página en segundo plano cada 10 minutos, el temporizador de Render nunca llegará a cero y tu página estará siempre despierta y ultrarrápida.

---

## ✉️ Paso 6: Envío de Correos OTP 2FA en Render Free Tier (Puente HTTPS Puerto 443)

El plan gratuito de Render bloquea por seguridad los puertos SMTP salientes tradicionales (`25`, `465` y `587`), pero permite el 100% del tráfico por el **Puerto `443` (HTTPS)**.
* Por ello, [`Back-end/servicios/EmailService.php`](../Back-end/servicios/EmailService.php) incorpora el método `enviarViaHttpsRelay()`, el cual envía la plantilla HTML corporativa por `HTTPS (Puerto 443)` hacia un puente oficial de **Google Apps Script** (`script.google.com`, configurado con acceso *"Cualquier persona"* / *Anyone*) conectado directamente a la cuenta de Gmail institucional (`juanvicenteherreravivanco.ver1@gmail.com`).
* Si se ejecuta en un entorno local sin puente, realiza fallback automático a `PHPMailer` por el puerto `465 (SMTPS)`.

---

## 🔄 Paso 7: Cómo Actualizar el Código o Pausar/Reanudar el Hosting (`Suspend` / `Resume`)

* **Para subir un cambio de código a `https://snackver.me`:**
  1. Sube los cambios a GitHub (`git add .`, `git commit -m "..."`, `git push origin main`).
  2. Entra a tu servicio en Render y haz clic arriba a la derecha en **"Manual Deploy" $\rightarrow$ "Deploy latest commit"**.
* **Para Pausar temporalmente el servidor (`Suspend Web Service`):**  
  * En el panel de tu Web Service en Render, ve a **Settings** y haz clic en **"Suspend Web Service"**.
  * **¿Qué hace?** Apaga manualmente el contenedor Docker (`503 Service Unavailable`), deja de consumir horas del plan gratuito (750 h/mes) y **no** se despierta aunque `cron-job.org` o un visitante entren al dominio.
  * **¿Qué conserva?** Tus datos en **MongoDB Atlas**, variables de entorno, dominio `snackver.me` y certificado SSL permanecen 100% intactos.
* **Para Reanudar el servidor (`Resume Web Service`):**  
  * En esa misma sección de **Settings**, haz clic en **"Resume Web Service"**. Render vuelve a levantar el contenedor en ~1 minuto y `https://snackver.me` regresa en línea de inmediato.

