# Reglas de Desarrollo y Trabajo para el Asistente AI (Antigravity)

Este archivo contiene las directivas obligatorias que el asistente de Inteligencia Artificial DEBE respetar en cada interacción con este repositorio.

---

### 0. REGLA FIJA INMUTABLE: CONGELACIÓN DE DISEÑO Y CERO MODIFICACIONES NO SOLICITADAS
> **"PROHIBIDO CAMBIAR DISEÑO, MAQUETACIÓN, ESTILOS O CÓDIGO HASTA QUE EL USUARIO LO PIDA EXPLÍCITAMENTE EN EL PROMPT."**
- **Congelación total de diseño:** El diseño visual actual de todas las pantallas (cliente, vendedor, admin, login, correos electrónicos, tarjetas, tablas y botones) queda totalmente congelado y fijado.
- **Cero cambios visuales o estéticos por iniciativa propia:** NO modificar diseño, HTML, CSS, estilos inline, maquetación, colores, márgenes, tipografías ni distribución a menos que el usuario lo pida textual y explícitamente en su mensaje.
- **Cero modificaciones colaterales:** NO cambiar absolutamente nada del sistema (lógica, base de datos, estructura) a menos que haya una indicación directa en el prompt. Si el usuario pide algo específico, se debe intervenir de forma quirúrgica respetando todo lo demás tal y como está.

---

### 1. Directiva Principal: Estricto Alcance de Peticiones
> **"Solo haz lo que se te pida en la petición."**
- **NO realices cambios adicionales** que no hayan sido expresamente solicitados por el usuario.
- **NO modifiques archivos, refactorices código ni apliques correcciones automáticas** por iniciativa propia hasta que el usuario te diga explícitamente: *"modificar"*, *"arreglar"*, *"haz el cambio"* o apruebe un plan.
- Si detectas una mejora, advertencia o error en el código, **notifícalo en tu respuesta y consúltalo**, pero NO toques el código sin autorización previa.

---

### 2. Aprobación Previa de Cambios
- Toda modificación que altere la base de datos, arquitectura, autenticación o lógica existente debe presentarse primero en una propuesta o plan.
- Solo tras la confirmación del usuario se procederá a editar o crear los archivos de código correspondientes.

---

### 3. Organización y Separación de Herramientas
- Las herramientas externas, scripts de apoyo, configuraciones de skills (Graphify, etc.) deben mantenerse en **carpetas separadas** dedicadas (por ejemplo `tools/`, `docs/`, `documentacion/`) para no mezclar la configuración con el código de producción de `Front-end` y `Back-end`.

---

### 4. Registro y Mantenimiento de Contexto
- Cualquier cambio, implementación o avance autorizado debe quedar registrado de inmediato en el archivo general `CONTEXTO_Y_CAMBIOS.md`.
- La documentación técnica y diagramas de base de datos deben mantenerse actualizados en la carpeta `documentacion/`.

---

### 5. Directiva de Verificación Integral ("verifica")
- Cuando el usuario solicite palabras clave como: *"verifica"*, *"comprueba"*, *"está funcionando todo"*, *"revisa el sistema"* o tras desplegar en una computadora nueva:
  - El asistente AI ejecutará de inmediato en segundo plano el verificador integral oficial del proyecto:
    - Ejecución en segundo plano: `php tools/verificar_sistema.php`.
  - Debe validarse el 100% de los componentes del sistema:
    1. Versión de PHP y extensiones nativas (`mongodb`, `gd`, `zip`, `openssl`, `curl`, `mbstring`).
    2. Conexión en vivo a MongoDB Atlas con cifrado TLS 1.3, latencia y sincronización de las 8 colecciones (`usuarios`, `productos`, `pedidos`, `carritos`, `categorias`, `resenas`, `historial_usuarios`, `notificaciones`).
    3. Variables de entorno seguras en `Back-end/.env`.
    4. Configuración de stubs en `.vscode/settings.json` (garantizando 0 líneas rojas de error en el editor).
    5. Respuesta del servidor Apache (`http://localhost/.../index.php`) con código HTTP 200 sin advertencias ni errores fatales.
  - El informe debe presentarse de manera clara, visual y estructurada con el estado de cada componente.
