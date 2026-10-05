# Creación de un Clúster con Atlas CLI

Sergio Emanuel Soberano Paredes
22020838
28 de septiembre del 2026

---

## Instalación de Atlas CLI

Para instalar Atlas CLI podemos obtener la imagen oficial de Docker y usarla directamente
```bash
docker pull mongodb/atlas
```
Posteriormente, creamos un volumen para persistir la configuración de Atlas CLI
```bash
docker volume create atlas-cli-config
```
Y para ejecutar Atlas CLI usamos el siguiente comando:
```bash
docker run --rm -it -v atlas-cli-config:/root/.config mongodb/atlas bash
```
Es recomendable crear un alias para ejecutar este comando
```bash
alias atlas='docker run --rm -it -v atlas-cli-config:/root/.config mongodb/atlas bash'
```

---

## Crear un Clúster

Una vez dentro de Atlas CLI debemos autentificarnos, para lo cual ejecutamos el comando
```bash
atlas auth login
```
Y copiar el código que vemos en la terminal para insertarlo en la ventana del navegador que nos indica. Una vez autentificados creamos un proyecto
```bash
atlas projects create Example
```
Y copiamos el ID del proyecto, pues lo necesitaremos para crear nuestro clúster con el siguiente comando
```bash
atlas cluster create Cluster-Example \
--projectId 12345 \
--provider AWS \
--region US_EAST_1 \
--tier M0
```
De esta forma ya tenemos un clúster creado