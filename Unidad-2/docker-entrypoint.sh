#!/bin/bash
set -e

# Railway inyecta módulos MPM conflictivos. Los desactivamos antes de iniciar Apache.
# Eliminamos los enlaces simbólicos que Railway añade.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*

# Desactivamos los módulos de forma segura y activamos solo el que necesitamos (prefork).
a2dismod mpm_event mpm_worker > /dev/null 2>&1 || true
a2enmod mpm_prefork > /dev/null 2>&1 || true

# Verificamos que no haya errores de configuración en Apache.
apache2ctl -t

# Si la variable PORT de Railway está definida, la usamos. Si no, usamos el 80.
if [ -n "$PORT" ]; then
    echo "Configurando Apache para escuchar en el puerto: $PORT"
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf
else
    echo "No se encontró la variable PORT, usando el puerto 80 por defecto."
fi

# Ejecuta el comando que se pase como argumento (apache2-foreground).
exec "$@"
