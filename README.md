# Actividades

Actividades de la materia Base de Datos NoSQL.

## Estructura

- `practicas/`: prácticas 1 a 3 (MongoDB con Podman, cluster en Atlas e instalación de OpenCode).
- `U3/`: unidad 3, bases de datos de grafos con Neo4j.
  - `compose.yml`: contenedor de Neo4j 5 Community.
  - `practica-02-roles-rbac.pdf`: práctica 2, roles integrados y RBAC en Neo4j.

## U3: Neo4j

### Comandos

```bash
podman-compose up -d   # levanta el contenedor en segundo plano
podman-compose down    # detiene y elimina el contenedor
```

### Conexión

Abre http://localhost:7474 en el navegador y conéctate con:

- Connect URL: `neo4j://localhost:7687`
- Usuario: `neo4j`
- Contraseña: `password`

Para practicar, escribe `:play movies` y sigue la guía.

> La edición Community no incluye roles ni RBAC. La práctica 2 usa un contenedor aparte con la imagen `neo4j:5-enterprise` y la licencia de evaluación.
