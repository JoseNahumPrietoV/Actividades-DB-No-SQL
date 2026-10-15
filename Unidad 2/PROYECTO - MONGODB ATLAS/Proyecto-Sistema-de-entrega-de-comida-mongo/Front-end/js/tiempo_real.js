/**
 * SNACKVER - MOTOR DE SINCRONIZACIÓN DIFERENCIAL EN TIEMPO REAL (0ms)
 * Actualiza únicamente las ventanas que no estaban cubiertas por notificaciones.js,
 * comparando un hash MD5 para tocar el DOM solo cuando existen cambios reales en Atlas.
 */
(function () {
    'use strict';

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const path = window.location.pathname;
        let endpointUrl = '';
        let vistaActual = '';

        // Determinar ruta relativa hacia Back-end/conexion/sincronizar_tiempo_real.php
        if (path.includes('/Back-end/admin/') || path.includes('/Back-end/vendedor/') || path.includes('/Back-end/cliente/')) {
            endpointUrl = '../conexion/sincronizar_tiempo_real.php';
        } else if (path.includes('/Front-end/')) {
            endpointUrl = '../Back-end/conexion/sincronizar_tiempo_real.php';
        } else {
            endpointUrl = 'Back-end/conexion/sincronizar_tiempo_real.php';
        }

        // Identificar qué vista activa requiere sincronización
        if (path.endsWith('/index.php') || path.endsWith('/') || (!path.includes('/Front-end/') && !path.includes('/Back-end/'))) {
            if (document.querySelector('.categories-grid') || document.querySelector('.carousel-track')) {
                vistaActual = 'portada';
            }
        } else if (path.includes('/Front-end/Productos.php') || path.includes('/Front-end/resultados_busqueda.php')) {
            vistaActual = 'productos_publicos';
        } else if (path.includes('/Back-end/admin/dashboard_admin.php')) {
            vistaActual = 'admin_dashboard';
        } else if (path.includes('/Back-end/admin/clientes.php')) {
            vistaActual = 'admin_clientes';
        } else if (path.includes('/Back-end/admin/vendedores.php')) {
            vistaActual = 'admin_vendedores';
        } else if (path.includes('/Back-end/admin/categorias.php')) {
            vistaActual = 'admin_categorias';
        } else if (path.includes('/Back-end/admin/historial_usuarios.php')) {
            vistaActual = 'admin_historial';
        } else if (path.includes('/Back-end/vendedor/Misplatillos.php')) {
            vistaActual = 'vendedor_platillos';
        } else if (path.includes('/Back-end/cliente/BuscarTienda.php')) {
            vistaActual = 'cliente_tiendas';
        } else if (path.includes('/Back-end/cliente/ver_tienda.php')) {
            vistaActual = 'cliente_ver_tienda';
        } else if (path.includes('/Back-end/cliente/carrito.php')) {
            vistaActual = 'cliente_carrito';
        } else if (path.includes('/Back-end/cliente/resenas.php')) {
            vistaActual = 'cliente_resenas';
        }

        if (!vistaActual) return;

        let ultimoHash = '';
        let peticionEnCurso = false;

        function construirQueryParams() {
            const params = new URLSearchParams({
                vista: vistaActual,
                hash: ultimoHash,
                t: Date.now()
            });
            const urlParams = new URLSearchParams(window.location.search);

            if (vistaActual === 'productos_publicos') {
                if (urlParams.get('id_categoria')) params.set('id_categoria', urlParams.get('id_categoria'));
                if (urlParams.get('busqueda')) params.set('busqueda', urlParams.get('busqueda'));
            } else if (vistaActual === 'cliente_tiendas') {
                const inputBusq = document.querySelector('input[name="busqueda"]');
                const val = inputBusq ? inputBusq.value.trim() : (urlParams.get('busqueda') || '');
                if (val) params.set('busqueda', val);
            } else if (vistaActual === 'cliente_ver_tienda') {
                if (urlParams.get('id')) params.set('id_tienda', urlParams.get('id'));
            }
            return params;
        }

        function sincronizarVista(forzar = false) {
            if (peticionEnCurso) return;
            if (forzar) ultimoHash = '';
            peticionEnCurso = true;

            const params = construirQueryParams();
            fetch(endpointUrl + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.ok ? res.json() : null)
                .then(resp => {
                    if (!resp || resp.status !== 'changed' || !resp.data) {
                        if (resp && resp.hash) ultimoHash = resp.hash;
                        return;
                    }
                    const esPrimeraCarga = (ultimoHash === '');
                    ultimoHash = resp.hash || '';
                    aplicarCambiosDOM(vistaActual, resp.data, esPrimeraCarga);
                })
                .catch(() => {
                    // Silencioso ante cortes de red momentáneos
                })
                .finally(() => {
                    peticionEnCurso = false;
                });
        }

        function aplicarCambiosDOM(vista, data, esPrimeraCarga) {
            switch (vista) {
                // -----------------------------------------------------------------
                // 1. PORTADA (index.php)
                // -----------------------------------------------------------------
                case 'portada': {
                    const gridCat = document.querySelector('.categories-grid');
                    if (gridCat && Array.isArray(data.categorias)) {
                        let htmlCat = '';
                        data.categorias.forEach(c => {
                            htmlCat += `
                                <a href="Front-end/Productos.php?id_categoria=${escapeHtml(c.idcategoria)}" class="category-link">
                                    <div class="category-card">
                                        <div class="category-icon">
                                            <i class="fas fa-store"></i>
                                        </div>
                                        <h3>${escapeHtml(c.nombrecategoria)}</h3>
                                    </div>
                                </a>`;
                        });
                        if (!esPrimeraCarga || gridCat.children.length !== data.categorias.length) {
                            gridCat.innerHTML = htmlCat;
                        }
                    }

                    const trackRes = document.querySelector('.carousel-track');
                    if (trackRes && Array.isArray(data.resenas)) {
                        if (data.resenas.length === 0) {
                            trackRes.innerHTML = "<div style='text-align:center; width:100%; padding: 20px;'>Aún no hay reseñas disponibles.</div>";
                        } else {
                            let htmlRes = '';
                            data.resenas.forEach(r => {
                                const cal = Math.max(0, Math.min(5, parseInt(r.calificacion, 10) || 5));
                                const estrellas = '<i class="fas fa-star" style="color: #f1c40f;"></i>'.repeat(cal)
                                    + '<i class="far fa-star" style="color: #ccc;"></i>'.repeat(5 - cal);
                                htmlRes += `
                                    <div class="carousel-card">
                                        <div class="resena-header">
                                            <div class="producto-info">
                                                <h3 title="${escapeHtml(r.producto)}">${escapeHtml(r.producto)}</h3>
                                                <small><i class="fas fa-store"></i> ${escapeHtml(r.vendedor)}</small>
                                            </div>
                                            <div class="calificacion">${estrellas}</div>
                                        </div>
                                        <p class="comentario">"${escapeHtml(r.comentario)}"</p>
                                        <div class="info-autor">
                                            <strong>${escapeHtml(r.nombreCompleto)}</strong> <br>
                                            <small>${escapeHtml(r.fecha)}</small>
                                        </div>
                                    </div>`;
                            });
                            if (!esPrimeraCarga) {
                                trackRes.innerHTML = htmlRes;
                            }
                        }
                    }
                    break;
                }

                // -----------------------------------------------------------------
                // 2. PRODUCTOS PÚBLICOS (Productos.php y resultados_busqueda.php)
                // -----------------------------------------------------------------
                case 'productos_publicos': {
                    if (esPrimeraCarga) break;
                    const cardCont = document.querySelector('.featured .dashboard-card');
                    if (!cardCont) break;
                    const esBusqueda = path.includes('resultados_busqueda.php');
                    const items = data.items || [];

                    if (!esBusqueda && data.nombre_categoria) {
                        const h2 = cardCont.querySelector('.section-title');
                        if (h2) h2.textContent = data.nombre_categoria;
                    }

                    if (!esBusqueda && data.categoria_activa === false) {
                        const h2Html = cardCont.querySelector('.section-title') ? cardCont.querySelector('.section-title').outerHTML : '';
                        cardCont.innerHTML = h2Html + `
                            <div style="text-align: center; padding: 50px; color: #777;">
                                <p>Esta categoría ya no está disponible o ha sido retirada del catálogo.</p>
                                <a href="../index.php" class="btn-primary" style="display:inline-block; margin-top:10px; padding:10px 20px; text-decoration:none;">Ver otras categorías</a>
                            </div>`;
                        break;
                    }

                    if (items.length === 0) {
                        const h2Html = cardCont.querySelector('.section-title') ? cardCont.querySelector('.section-title').outerHTML : '';
                        if (esBusqueda) {
                            cardCont.innerHTML = h2Html + `
                                <div style="text-align: center; padding: 50px; color: #777;">
                                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; color: #ccc;"></i>
                                    <h3>No encontramos lo que buscas :(</h3>
                                    <p>Intenta buscar "Tacos", "Hamburguesa" o "Pizza".</p>
                                    <a href="../index.php" class="btn-ver" style="display:inline-block; margin-top:15px;">Regresar al inicio</a>
                                </div>`;
                        } else {
                            cardCont.innerHTML = h2Html + `
                                <div style="text-align: center; padding: 50px; color: #777;">
                                    <p>No hay productos disponibles en esta categoría por el momento.</p>
                                    <a href="../index.php" class="btn-primary" style="display:inline-block; margin-top:10px; padding:10px 20px; text-decoration:none;">Ver otras categorías</a>
                                </div>`;
                        }
                        break;
                    }

                    let grid = cardCont.querySelector('.products-grid');
                    if (!grid) {
                        const h2Html = cardCont.querySelector('.section-title') ? cardCont.querySelector('.section-title').outerHTML : '';
                        cardCont.innerHTML = h2Html + '<div class="products-grid"></div>';
                        grid = cardCont.querySelector('.products-grid');
                    }

                    let htmlProd = '';
                    items.forEach(item => {
                        const descHtml = esBusqueda
                            ? `<p class="item-desc" style="font-size: 0.85em; color: #666; margin-bottom: 10px;">${escapeHtml((item.desc || '').substring(0, 60))}...</p>`
                            : '';
                        htmlProd += `
                            <div class="product-card">
                                <div class="item-image">
                                    <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" onerror="this.src='../img/default-food.png'">
                                </div>
                                <div class="item-content">
                                    <h3 class="item-title">${escapeHtml(item.name)}</h3>
                                    <div style="margin-bottom: 10px;">
                                        <span class="item-seller" style="color: #2c3e50; font-weight: bold; font-size: 0.9em; display: block;">
                                            <i class="fas fa-store"></i> ${escapeHtml(item.seller)}
                                        </span>
                                        <span style="color: #7f8c8d; font-size: 0.85em; display: block; margin-top: 3px;">
                                            ${escapeHtml(item.personal_name)}
                                        </span>
                                    </div>
                                    ${descHtml}
                                    <div class="item-meta" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid #eee; padding-top: 10px;">
                                        <div>
                                            <span class="item-price" style="color: #27ae60; font-weight: bold; font-size: 1.1em;">
                                                ${escapeHtml(item.price)}
                                            </span>
                                            <span class="stock-badge">
                                                <i class="fas fa-box-open"></i> ${escapeHtml(item.stock)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    });
                    grid.innerHTML = htmlProd;
                    break;
                }

                // -----------------------------------------------------------------
                // 3. ADMIN DASHBOARD (dashboard_admin.php)
                // -----------------------------------------------------------------
                case 'admin_dashboard': {
                    const elVentas = document.getElementById('kpi-admin-ventas');
                    const elCli    = document.getElementById('kpi-admin-clientes');
                    const elVen    = document.getElementById('kpi-admin-vendedores');
                    if (elVentas && data.total_ventas_fmt !== undefined) elVentas.textContent = data.total_ventas_fmt;
                    if (elCli && data.total_clientes !== undefined) elCli.textContent = data.total_clientes;
                    if (elVen && data.total_vendedores !== undefined) elVen.textContent = data.total_vendedores;
                    break;
                }

                // -----------------------------------------------------------------
                // 4. ADMIN CLIENTES (clientes.php)
                // -----------------------------------------------------------------
                case 'admin_clientes': {
                    if (esPrimeraCarga) break;
                    const tbody = document.querySelector('#tablaClientes tbody');
                    if (!tbody || !Array.isArray(data.clientes)) break;

                    let htmlFilas = '';
                    data.clientes.forEach(row => {
                        const id = escapeHtml(row.id);
                        const nombre = escapeHtml(row.nombre);
                        const email = escapeHtml(row.email);
                        const tel = escapeHtml(row.telefono);
                        const estado = row.estado || 'activo';
                        const fecha = escapeHtml(row.fecharegistro);

                        let badgeHtml = '';
                        if (estado === 'activo') {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-active"><i class="fas fa-check-circle"></i> Activo (2FA)</span>`;
                        } else if (estado === 'pendiente_verificacion') {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-pending"><i class="fas fa-clock"></i> Pendiente (2FA)</span>`;
                        } else {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-inactive"><i class="fas fa-ban"></i> Inactivo</span>`;
                        }

                        const btnEstadoHtml = (estado === 'activo')
                            ? `<button class="btn-action" style="background-color:#f59e0b;" onclick="cambiarEstado('${id}', 'inactivo')" title="Inactivar"><i class="fas fa-ban"></i></button>`
                            : `<button class="btn-action btn-success" onclick="cambiarEstado('${id}', 'activo')" title="Activar"><i class="fas fa-check"></i></button>`;

                        htmlFilas += `
                            <tr id="fila-${id}" class="fila-cliente"
                                data-nombre="${escapeHtml((row.nombre || '').toLowerCase())}"
                                data-email="${escapeHtml((row.email || '').toLowerCase())}"
                                data-telefono="${escapeHtml((row.telefono || '').toLowerCase())}"
                                data-estado="${escapeHtml(estado)}">
                                <td><div style="font-weight:bold;">${nombre}</div></td>
                                <td>${email}</td>
                                <td>${tel ? tel : '<span style="color:#94a3b8;">Sin teléfono</span>'}</td>
                                <td style="font-size:0.9em; color:#475569;">${fecha}</td>
                                <td style="text-align:center; white-space:nowrap;">${badgeHtml}</td>
                                <td>
                                    <div class="columna-acciones">
                                        <a href="editar_cliente.php?id=${id}" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        ${btnEstadoHtml}
                                        <button class="btn-action btn-danger" onclick="eliminarCliente('${id}')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>`;
                    });

                    htmlFilas += `
                        <tr id="sinResultadosInicial" style="${data.clientes.length === 0 ? '' : 'display:none;'}">
                            <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                <i class="fas fa-users" style="font-size:2em; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                No hay clientes registrados en el sistema.
                            </td>
                        </tr>
                        <tr id="filaSinCoincidencias" style="display:none;">
                            <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                <i class="fas fa-search" style="font-size:1.5em; margin-bottom:8px; display:block;"></i>
                                No se encontraron clientes que coincidan con la búsqueda o filtro seleccionado.
                            </td>
                        </tr>`;

                    tbody.innerHTML = htmlFilas;
                    if (typeof window.filtrarTabla === 'function') window.filtrarTabla();
                    break;
                }

                // -----------------------------------------------------------------
                // 5. ADMIN VENDEDORES (vendedores.php)
                // -----------------------------------------------------------------
                case 'admin_vendedores': {
                    if (esPrimeraCarga) break;
                    const tbody = document.querySelector('#tablaVendedores tbody');
                    if (!tbody || !Array.isArray(data.vendedores)) break;

                    let htmlFilas = '';
                    data.vendedores.forEach(row => {
                        const id = escapeHtml(row.id);
                        const negocio = escapeHtml(row.negocio);
                        const nombre = escapeHtml(row.nombre);
                        const email = escapeHtml(row.email);
                        const tel = escapeHtml(row.telefono);
                        const estado = row.estado || 'activo';
                        const fecha = escapeHtml(row.fecharegistro);

                        let badgeHtml = '';
                        if (estado === 'activo') {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-active"><i class="fas fa-check-circle"></i> Activo (2FA)</span>`;
                        } else if (estado === 'pendiente_verificacion') {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-pending"><i class="fas fa-clock"></i> Pendiente (2FA)</span>`;
                        } else {
                            badgeHtml = `<span id="badge-${id}" class="badge badge-inactive"><i class="fas fa-ban"></i> Inactivo</span>`;
                        }

                        const btnEstadoHtml = (estado === 'activo')
                            ? `<button class="btn-action" style="background-color:#f59e0b;" onclick="cambiarEstadoVendedor('${id}', 'inactivo')" title="Inactivar"><i class="fas fa-ban"></i></button>`
                            : `<button class="btn-action btn-success" onclick="cambiarEstadoVendedor('${id}', 'activo')" title="Activar"><i class="fas fa-check"></i></button>`;

                        htmlFilas += `
                            <tr id="fila-${id}" class="fila-vendedor"
                                data-negocio="${escapeHtml((row.negocio || '').toLowerCase())}"
                                data-nombre="${escapeHtml((row.nombre || '').toLowerCase())}"
                                data-email="${escapeHtml((row.email || '').toLowerCase())}"
                                data-telefono="${escapeHtml((row.telefono || '').toLowerCase())}"
                                data-estado="${escapeHtml(estado)}">
                                <td><div style="font-weight:bold; color:#1A4F8B;"><i class="fas fa-store-alt" style="margin-right:4px;"></i> ${negocio}</div></td>
                                <td>${nombre}</td>
                                <td style="font-size: 0.9em;">
                                    <div><i class="fas fa-envelope" style="color:#64748b;"></i> ${email}</div>
                                    <div><i class="fas fa-phone" style="color:#64748b;"></i> ${tel ? tel : '<span style="color:#94a3b8;">Sin teléfono</span>'}</div>
                                </td>
                                <td style="font-size:0.9em; color:#475569;">${fecha}</td>
                                <td style="text-align:center; white-space:nowrap;">${badgeHtml}</td>
                                <td>
                                    <div class="columna-acciones">
                                        <a href="editar_vendedor.php?id=${id}" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        ${btnEstadoHtml}
                                        <button class="btn-action btn-danger" onclick="eliminarVendedor('${id}')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>`;
                    });

                    htmlFilas += `
                        <tr id="sinResultadosInicial" style="${data.vendedores.length === 0 ? '' : 'display:none;'}">
                            <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                <i class="fas fa-store" style="font-size:2em; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                No hay vendedores registrados en el sistema.
                            </td>
                        </tr>
                        <tr id="filaSinCoincidencias" style="display:none;">
                            <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                <i class="fas fa-search" style="font-size:1.5em; margin-bottom:8px; display:block;"></i>
                                No se encontraron vendedores que coincidan con la búsqueda o filtro seleccionado.
                            </td>
                        </tr>`;

                    tbody.innerHTML = htmlFilas;
                    if (typeof window.filtrarTabla === 'function') window.filtrarTabla();
                    break;
                }

                // -----------------------------------------------------------------
                // 6. ADMIN CATEGORÍAS (categorias.php)
                // -----------------------------------------------------------------
                case 'admin_categorias': {
                    if (esPrimeraCarga) break;
                    const tbody = document.querySelector('.table-container table tbody');
                    if (!tbody || !Array.isArray(data.categorias)) break;

                    if (data.categorias.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No hay categorías registradas.</td></tr>';
                        break;
                    }

                    let htmlCat = '';
                    data.categorias.forEach(row => {
                        const estado = row.estado || 'activo';
                        const estCap = estado.charAt(0).toUpperCase() + estado.slice(1);
                        htmlCat += `
                            <tr>
                                <td><strong>${escapeHtml(row.nombrecategoria)}</strong></td>
                                <td>${escapeHtml(row.descripcion)}</td>
                                <td><span class="badge badge-${escapeHtml(estado)}">${escapeHtml(estCap)}</span></td>
                                <td>
                                    <a href="editar_categoria.php?id=${escapeHtml(row.id)}" class="btn-action btn-edit" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; white-space: nowrap;">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </td>
                            </tr>`;
                    });
                    tbody.innerHTML = htmlCat;
                    break;
                }

                // -----------------------------------------------------------------
                // 7. ADMIN HISTORIAL DE USUARIOS (historial_usuarios.php)
                // -----------------------------------------------------------------
                case 'admin_historial': {
                    if (esPrimeraCarga) break;
                    const tbody = document.querySelector('#tablaHistorial tbody');
                    if (!tbody || !Array.isArray(data.historial)) break;

                    let htmlHist = '';
                    data.historial.forEach(row => {
                        const rolCap = (row.rol || 'usuario').charAt(0).toUpperCase() + (row.rol || 'usuario').slice(1);
                        const ipLabel = (row.ip === '::1') ? '127.0.0.1 (Local)' : row.ip;
                        htmlHist += `
                            <tr class="fila-historial"
                                data-email="${escapeHtml((row.email || '').toLowerCase())}"
                                data-id="${escapeHtml((row.id_usuario || '').toLowerCase())}"
                                data-rol="${escapeHtml((row.rol || '').toLowerCase())}"
                                data-accion="${escapeHtml(row.accion)}"
                                data-ip="${escapeHtml((row.ip || '').toLowerCase())}"
                                data-dispositivo="${escapeHtml((row.dispositivo_nombre || '').toLowerCase())}">
                                <td data-label="Fecha y Hora" style="font-size:0.88em; color:#475569; white-space:nowrap;">
                                    <div class="celda-fecha">
                                        <div style="font-weight:600; color:#1e293b;"><i class="far fa-calendar-alt" style="color:#94a3b8; margin-right:4px;"></i>${escapeHtml(row.fecha_dia)}</div>
                                        <div style="font-size:0.85em; color:#64748b;"><i class="far fa-clock" style="color:#94a3b8; margin-right:4px;"></i>${escapeHtml(row.fecha_hora)}</div>
                                    </div>
                                </td>
                                <td data-label="Usuario / Correo">
                                    <div class="celda-email">${escapeHtml(row.email)}</div>
                                </td>
                                <td data-label="Rol" style="text-align:center; white-space:nowrap;">
                                    <span class="badge ${escapeHtml(row.badge_rol_clase)}">${escapeHtml(rolCap)}</span>
                                </td>
                                <td data-label="Acción Realizada" style="white-space:nowrap;">
                                    <span class="badge ${escapeHtml(row.badge_clase)}">
                                        <i class="fas ${escapeHtml(row.icono_accion)}" style="margin-right:4px;"></i> ${escapeHtml(row.accion)}
                                    </span>
                                </td>
                                <td data-label="Dirección IP" style="text-align:center; white-space:nowrap;">
                                    <span class="ip-tag">
                                        <i class="fas fa-network-wired" style="color:#94a3b8;"></i>
                                        <span>${escapeHtml(ipLabel)}</span>
                                    </span>
                                </td>
                                <td data-label="Dispositivo" title="${escapeHtml(row.dispositivo_raw)}">
                                    <span class="device-pill">
                                        <i class="${escapeHtml(row.dispositivo_icono)}"></i>
                                        <span>${escapeHtml(row.dispositivo_nombre)}</span>
                                    </span>
                                </td>
                            </tr>`;
                    });

                    htmlHist += `
                        <tr id="sinHistorialInicial" style="${data.historial.length === 0 ? '' : 'display:none;'}">
                            <td colspan="6" style="text-align:center; padding: 30px; color:#64748b;">
                                <i class="fas fa-history" style="font-size:2em; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                Aún no hay actividades registradas en la bitácora de auditoría.
                            </td>
                        </tr>
                        <tr id="filaSinCoincidencias" style="display:none;">
                            <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                <i class="fas fa-search" style="font-size:1.5em; margin-bottom:8px; display:block;"></i>
                                No se encontraron actividades que coincidan con la búsqueda o filtros seleccionados.
                            </td>
                        </tr>`;

                    tbody.innerHTML = htmlHist;
                    if (typeof window.filtrarHistorial === 'function') window.filtrarHistorial();
                    break;
                }

                // -----------------------------------------------------------------
                // 8. VENDEDOR MIS PLATILLOS (Misplatillos.php)
                // -----------------------------------------------------------------
                case 'vendedor_platillos': {
                    if (esPrimeraCarga) break;
                    const grid = document.getElementById('products-grid-container');
                    if (!grid || !Array.isArray(data.productos)) break;

                    if (data.productos.length === 0) {
                        grid.innerHTML = "<p style='grid-column: 1 / -1; text-align: center;'>No tienes platillos registrados. Haz clic en 'Agregar Platillo' para empezar.</p>";
                        break;
                    }

                    let htmlPlatillos = '';
                    data.productos.forEach(fila => {
                        htmlPlatillos += `
                            <div class="product-card" id="product-card-${escapeHtml(fila.idproducto)}">
                                <div class="product-image">
                                    <img src="${escapeHtml(fila.imagen)}"
                                        alt="${escapeHtml(fila.nombreproducto)}"
                                        decoding="async"
                                        onerror="this.src='../../img/default-food.png'"
                                        style="width: 100%; height: 160px; object-fit: cover; border-radius: 6px 6px 0 0;">
                                </div>
                                <div class="product-info">
                                    <h4>${escapeHtml(fila.nombreproducto)}</h4>
                                    <div style="margin-bottom: 5px;">
                                        <span class="badge-stock ${escapeHtml(fila.clase_stock)}">
                                            <i class="fas fa-box"></i> ${escapeHtml(fila.texto_stock)}
                                        </span>
                                    </div>
                                    <p>${escapeHtml(fila.descripcion)}</p>
                                    <div class="product-price">${escapeHtml(fila.precio_fmt)}</div>
                                    <div class="product-actions">
                                        <a href="editar_platillo.php?id=${escapeHtml(fila.idproducto)}" class="btn btn-primary" style="text-decoration: none;">Editar</a>
                                    </div>
                                </div>
                            </div>`;
                    });
                    grid.innerHTML = htmlPlatillos;
                    break;
                }

                // -----------------------------------------------------------------
                // 9. CLIENTE BUSCAR TIENDAS (BuscarTienda.php)
                // -----------------------------------------------------------------
                case 'cliente_tiendas': {
                    if (esPrimeraCarga) break;
                    const grid = document.querySelector('.products-grid');
                    if (!grid || !Array.isArray(data.tiendas)) break;

                    if (data.tiendas.length === 0) {
                        grid.innerHTML = "<p>No se encontraron tiendas...</p>";
                        break;
                    }

                    let htmlTiendas = '';
                    data.tiendas.forEach(t => {
                        htmlTiendas += `
                            <div class="product-card">
                                <div class="product-image" style="height: 150px; background-color: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                    <img src="${escapeHtml(t.foto_negocio)}"
                                        alt="Foto de ${escapeHtml(t.nombre_negocio)}"
                                        onerror="this.src='https://placehold.co/400x300/1A4F8B/ffffff?text=Tienda'"
                                        style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; object-position: center; padding: 4px;">
                                </div>
                                <div class="product-info">
                                    <h4>${escapeHtml(t.nombre_negocio)}</h4>
                                    <h4>${escapeHtml(t.nombre_vendedor)} ${escapeHtml(t.apellido_vendedor)}</h4>
                                    <div class="store-stats" style="font-size: 0.9em; color: #555; margin-bottom: 5px;">
                                        <br>
                                        ${escapeHtml(t.direccion)}
                                    </div>
                                    <p>${escapeHtml(t.descripcion)}</p>
                                    <div class="product-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                        <a href="ver_tienda.php?id=${escapeHtml(t.idvendedor)}" class="btn btn-primary">Ver Tienda</a>
                                    </div>
                                </div>
                            </div>`;
                    });
                    grid.innerHTML = htmlTiendas;
                    break;
                }

                // -----------------------------------------------------------------
                // 10. CLIENTE VER TIENDA (ver_tienda.php)
                // -----------------------------------------------------------------
                case 'cliente_ver_tienda': {
                    if (esPrimeraCarga) break;
                    const grid = document.querySelector('.products-grid');
                    if (!grid || !Array.isArray(data.productos)) break;

                    if (data.productos.length === 0) {
                        grid.innerHTML = "<p>Esta tienda no tiene productos disponibles en este momento.</p>";
                        break;
                    }

                    let htmlProd = '';
                    data.productos.forEach(p => {
                        htmlProd += `
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="${escapeHtml(p.imagen)}" alt="${escapeHtml(p.nombreproducto)}" onerror="this.src='../../img/default-food.png'">
                                </div>
                                <div class="product-info">
                                    <h4>${escapeHtml(p.nombreproducto)}</h4>
                                    <p style="font-size: 0.9em; color: #777; margin-bottom: 10px;">
                                        ${escapeHtml(p.descripcion)}
                                    </p>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <div class="product-price" style="font-weight: bold; color: #27ae60; font-size: 1.1em;">
                                            $${escapeHtml(p.precio_fmt)}
                                        </div>
                                        <span class="stock-badge">
                                            <i class="fas fa-box-open"></i> ${escapeHtml(p.disponible)} disp.
                                        </span>
                                    </div>
                                    <div class="product-actions">
                                        <button class="btn btn-agregar-carrito"
                                                style="width: 100%; background-color: #3498db; color: white; border: none; padding: 8px; border-radius: 5px; cursor: pointer;"
                                                data-idproducto="${escapeHtml(p.idproducto)}"
                                                data-precio="${escapeHtml(p.precio)}">
                                            Añadir al Carrito
                                        </button>
                                    </div>
                                </div>
                            </div>`;
                    });
                    grid.innerHTML = htmlProd;
                    break;
                }

                // -----------------------------------------------------------------
                // 11. CLIENTE CARRITO (carrito.php)
                // -----------------------------------------------------------------
                case 'cliente_carrito': {
                    if (esPrimeraCarga) break;
                    const cont = document.getElementById('cart-items-container');
                    if (!cont || !Array.isArray(data.items)) break;

                    // No sobrescribir mientras el usuario escribe en un input de cantidad
                    if (cont.contains(document.activeElement) && document.activeElement.tagName === 'INPUT') {
                        break;
                    }

                    if (data.items.length === 0) {
                        cont.innerHTML = `
                            <div class="empty-cart-message" style="display: block; text-align: center; padding: 40px 0;">
                                <i class="fas fa-shopping-cart" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                                <h3>Tu carrito está vacío</h3>
                                <p>Agrega productos deliciosos a tu carrito para continuar</p>
                            </div>`;
                    } else {
                        let htmlItems = '';
                        data.items.forEach(item => {
                            htmlItems += `
                                <div class="cart-item" data-idproducto="${escapeHtml(item.idproducto)}">
                                    <div class="item-image">
                                        <img src="${escapeHtml(item.imagen)}" alt="${escapeHtml(item.nombreproducto)}" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div class="item-details">
                                        <h3>${escapeHtml(item.nombreproducto)}</h3>
                                        <div class="item-price" data-precio-unitario="${escapeHtml(item.precio)}">
                                            $${escapeHtml(item.precio_fmt)}
                                        </div>
                                        <div class="item-actions">
                                            <div class="quantity-controls">
                                                <button class="quantity-btn quantity-btn-menos">-</button>
                                                <input type="number" class="quantity-input" value="${escapeHtml(item.cantidad)}" min="1" max="99">
                                                <button class="quantity-btn quantity-btn-mas">+</button>
                                            </div>
                                            <button class="remove-btn">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="item-subtotal" style="text-align: right; font-weight: bold;">
                                        $${escapeHtml(item.subtotal_fmt)}
                                    </div>
                                </div>`;
                        });
                        cont.innerHTML = htmlItems;
                    }

                    const subEl = document.getElementById('subtotal');
                    const totEl = document.getElementById('total');
                    if (subEl && data.subtotal_fmt) subEl.textContent = data.subtotal_fmt;
                    if (totEl && data.total_fmt) totEl.textContent = data.total_fmt;
                    break;
                }

                // -----------------------------------------------------------------
                // 12. CLIENTE RESEÑAS (resenas.php)
                // -----------------------------------------------------------------
                case 'cliente_resenas': {
                    if (esPrimeraCarga) break;
                    const selectVen = document.getElementById('select-vendedor');
                    if (!selectVen || !Array.isArray(data.vendedores)) break;
                    const valorActual = selectVen.value;

                    let htmlOpts = '<option value="">Selecciona un vendedor/tienda...</option>';
                    data.vendedores.forEach(v => {
                        const sel = (String(v.idvendedor) === String(valorActual)) ? ' selected' : '';
                        htmlOpts += `<option value="${escapeHtml(v.idvendedor)}"${sel}>${escapeHtml(v.nombre_mostrar)}</option>`;
                    });
                    selectVen.innerHTML = htmlOpts;
                    break;
                }
            }
        }

        // Exponer disparador manual global para acciones AJAX locales
        window.sincronizarVistaTiempoReal = function () {
            sincronizarVista(true);
        };

        // Primera sincronización para registrar el hash inicial
        sincronizarVista(false);

        // Polling inteligente cada 2 segundos mientras la pestaña esté visible
        setInterval(function () {
            if (!document.hidden) {
                sincronizarVista(false);
            }
        }, 2000);

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                sincronizarVista(false);
            }
        });

        window.addEventListener('focus', function () {
            sincronizarVista(false);
        });
    });
})();
