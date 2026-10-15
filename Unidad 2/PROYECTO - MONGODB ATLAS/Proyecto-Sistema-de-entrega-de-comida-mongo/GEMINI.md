# Reglas de Proyecto - Snackver (Antigravity)

## DIRECTIVA OBLIGATORIA INMUTABLE: CONGELACIÓN DE DISEÑO Y COMPORTAMIENTO
> **"PROHIBIDO CAMBIAR DISEÑO, MAQUETACIÓN, ESTILOS O CÓDIGO HASTA QUE EL USUARIO LO PIDA EXPLÍCITAMENTE EN EL PROMPT."**

1. **Congelación total de diseño:**
   - El diseño visual de todas las vistas (cliente, vendedor, admin, login, correos de verificación OTP, tarjetas, tablas y botones) está 100% definido y congelado.
   - **NO modificar** diseño, maquetación, HTML, CSS, clases, estilos inline, paleta de colores, márgenes, alineaciones ni tipografías a menos que el usuario lo solicite de manera textual y explícita en su prompt.

2. **Cero cambios colaterales ni por iniciativa propia:**
   - **NO alterar** código, lógica de negocio, esquemas de bases de datos ni archivos que no hayan sido expresamente pedidos.
   - Cualquier intervención técnica debe ser estrictamente quirúrgica y ceñida a la petición del usuario.

3. **Aprobación previa obligatoria (/plan):**
   - Siempre presentar una propuesta o plan antes de realizar cambios estructurales, y esperar la confirmación explícita del usuario (`proceed`) antes de editar archivos.

4. **Registro de cambios:**
   - Registrar cualquier cambio autorizado en `CONTEXTO_Y_CAMBIOS.md`.
