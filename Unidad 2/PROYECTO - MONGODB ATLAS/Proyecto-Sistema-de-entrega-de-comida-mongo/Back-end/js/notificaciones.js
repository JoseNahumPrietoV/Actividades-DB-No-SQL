/**
 * SNACKVER - GESTOR DE NOTIFICACIONES EN TIEMPO REAL Y LECTURA MASIVA
 * Compatible con Cliente y Vendedor
 */
document.addEventListener('DOMContentLoaded', function () {
    const bellIcon = document.getElementById('bellIcon');
    const badge = document.getElementById('notification-badge');
    const dropdown = document.getElementById('notification-dropdown');
    const lista = document.getElementById('notification-list');

    if (!bellIcon || !dropdown || !lista) {
        return;
    }

    // Calcular ruta base para los endpoints de notificaciones y tipo de panel
    const pathActual = window.location.pathname;
    let baseUrlNotif = '../notificaciones/';
    if (pathActual.includes('/Back-end/notificaciones/')) {
        baseUrlNotif = '';
    } else if (pathActual.endsWith('/index.php') || !pathActual.includes('/Back-end/')) {
        baseUrlNotif = 'Back-end/notificaciones/';
    }

    let tipoPanel = '';
    if (pathActual.includes('/vendedor/')) {
        tipoPanel = 'vendedor';
    } else if (pathActual.includes('/cliente/')) {
        tipoPanel = 'cliente';
    }

    // 1. Alternar despliegue de la campana
    bellIcon.addEventListener('click', function (e) {
        e.stopPropagation();
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            dropdown.style.display = 'block';
            consultarNotificaciones();
        }
    });

    // Evitar que clics dentro del dropdown escalen al bellIcon
    dropdown.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // Cerrar al hacer clic fuera del dropdown
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== bellIcon) {
            dropdown.style.display = 'none';
        }
    });

    let marcandoLeidasEnProgreso = false;

    // 2. Función para renderizar la lista de notificaciones
    function renderizarNotificaciones(notificaciones, totalNoLeidas) {
        if (marcandoLeidasEnProgreso) {
            totalNoLeidas = 0;
        }

        // Actualizar Badge: solo puntito rojo sin números
        if (badge) {
            if (totalNoLeidas > 0) {
                badge.style.display = 'block';
                badge.textContent = ''; // Solo puntito rojo, sin números
                badge.innerHTML = '';
            } else {
                badge.style.display = 'none';
                badge.textContent = '';
                badge.innerHTML = '';
            }
        }

        // Renderizar lista
        if (!notificaciones || notificaciones.length === 0) {
            const htmlVacio = '<p class="no-notif">No tienes notificaciones</p>';
            if (lista.innerHTML !== htmlVacio) {
                lista.innerHTML = htmlVacio;
            }
            return;
        }

        let html = '';
        notificaciones.forEach(notif => {
            const esNoLeida = marcandoLeidasEnProgreso ? false : !notif.leida;
            const claseUnread = esNoLeida ? 'unread' : '';

            // Icono contextual
            let icono = '<i class="fas fa-bell" style="color: #1A4F8B; margin-top: 2px;"></i>';
            const msg = notif.mensaje || '';
            if (msg.includes('Listo')) {
                icono = '<i class="fas fa-check-circle" style="color: #10b981; margin-top: 2px;"></i>';
            } else if (msg.includes('preparando') || msg.includes('preparación')) {
                icono = '<i class="fas fa-fire-burner" style="color: #f59e0b; margin-top: 2px;"></i>';
            } else if (msg.includes('entregado') || msg.includes('Entregado')) {
                icono = '<i class="fas fa-bag-shopping" style="color: #3b82f6; margin-top: 2px;"></i>';
            } else if (msg.includes('cancelado') || msg.includes('rechazado')) {
                icono = '<i class="fas fa-circle-xmark" style="color: #ef4444; margin-top: 2px;"></i>';
            } else if (msg.includes('Nuevo pedido')) {
                icono = '<i class="fas fa-receipt" style="color: #8b5cf6; margin-top: 2px;"></i>';
            }

            const urlDestino = notif.url_destino ? `href="${notif.url_destino}"` : 'href="javascript:void(0)"';

            html += `
                <a ${urlDestino} class="notif-item ${claseUnread}" style="text-decoration: none; color: inherit; display: flex;">
                    ${icono}
                    <div class="notif-content">
                        <span>${escapeHtml(msg)}</span>
                        <small class="notif-time">${escapeHtml(notif.tiempo_relativo || 'Reciente')}</small>
                    </div>
                </a>
            `;
        });

        if (lista.innerHTML !== html) {
            lista.innerHTML = html;
        }
    }

    // 3. Consultar notificaciones y estados en tiempo real
    function consultarNotificaciones() {
        const queryParams = new URLSearchParams({
            t: Date.now()
        });
        if (tipoPanel) {
            queryParams.append('tipo', tipoPanel);
        }

        // Polling inteligente: solo consultar pedidos pesados si la página tiene contenedores visibles
        const tieneContenedoresPedidos = !!(
            document.getElementById('contenedor-pedidos') ||
            document.getElementById('contenedor-pedidos-cliente') ||
            document.querySelector('.order-history') ||
            document.getElementById('kpi-pendientes')
        );

        if (!tieneContenedoresPedidos) {
            queryParams.append('with_orders', '0');
        }

        fetch(baseUrlNotif + 'obtener_notificaciones.php?' + queryParams.toString())
            .then(res => {
                if (!res.ok) throw new Error('Respuesta no válida');
                return res.json();
            })
            .then(data => {
                if (data.status === 'success' || Array.isArray(data.notificaciones)) {
                    renderizarNotificaciones(data.notificaciones || [], data.total_no_leidas || 0);
                    if (data.stats) {
                        actualizarKPIsVendedor(data.stats);
                    }
                    if (Array.isArray(data.pedidos)) {
                        sincronizarEstadosEnPantalla(data.pedidos);
                    }
                }
            })
            .catch(() => {
                // Silencioso en caso de pérdida de red temporal
            });
    }

    // 4. Actualizar contadores KPIs en dashboard_vendedor.php en tiempo real
    function actualizarKPIsVendedor(stats) {
        if (!stats) return;
        const kpiPendientes = document.getElementById('kpi-pendientes');
        if (kpiPendientes && stats.pendientes !== undefined) {
            kpiPendientes.textContent = `${stats.pendientes} Pendientes`;
        }
        const kpiGeneral = document.getElementById('kpi-total-general');
        if (kpiGeneral && stats.total_general !== undefined) {
            kpiGeneral.textContent = `${stats.total_general} Registrados`;
        }
        const kpiProductos = document.getElementById('kpi-total-productos');
        if (kpiProductos && stats.total_productos !== undefined) {
            kpiProductos.textContent = `${stats.total_productos} Registrados`;
        }
    }

    // 5. Sincronizar estados de pedidos e insertar nuevos en tiempo real en la pantalla activa
    function sincronizarEstadosEnPantalla(pedidos) {
        if (!pedidos || !Array.isArray(pedidos)) return;

        const contVendedor = document.getElementById('contenedor-pedidos');
        const contCliente = document.getElementById('contenedor-pedidos-cliente') 
            || (tipoPanel === 'cliente' ? document.querySelector('.order-history') : null);

        pedidos.forEach(p => {
            const estadoNuevo = p.estado;
            const idsABuscar = [p.id, p.id_num, p.id_oid].filter(Boolean);

            // A. Si estamos en el panel de vendedor (Gestion_platillos.php o pedidos_pendientes.php)
            if (tipoPanel === 'vendedor' && contVendedor) {
                let existeEnDOM = false;
                for (const id of idsABuscar) {
                    if (document.getElementById('status-' + id) || document.querySelector(`.order-card[data-idpedido="${id}"]`)) {
                        existeEnDOM = true;
                        break;
                    }
                }

                // Si es un pedido nuevo que no está en el listado y coincide con filtros o vista activa
                if (!existeEnDOM && p.id) {
                    const filtroSelect = document.getElementById('filtro_estado');
                    const filtroActual = filtroSelect ? filtroSelect.value : 'Pendiente';
                    const coincideFiltro = (filtroActual === 'Todos' || filtroActual === p.estado);

                    if (coincideFiltro) {
                        // Limpiar mensaje de lista vacía si existía
                        contVendedor.querySelectorAll(':scope > div:not([data-idpedido])').forEach(el => {
                            if (el.textContent.includes('No se encontraron pedidos') || el.textContent.includes('No tienes pedidos') || el.textContent.includes('No hay pedidos')) {
                                el.remove();
                            }
                        });

                        const nuevaCard = document.createElement('div');
                        nuevaCard.className = 'order-card';
                        nuevaCard.dataset.idpedido = p.id;
                        let claseBadge = 'status-pending';
                        if (p.estado === 'En preparación') claseBadge = 'status-preparing';
                        else if (p.estado === 'Listo') claseBadge = 'status-ready';
                        else if (p.estado === 'Entregado') claseBadge = 'status-completed';
                        else if (p.estado === 'Rechazado') claseBadge = 'status-rechazado';
                        else if (p.estado === 'Cancelado') claseBadge = 'status-cancelado';

                        nuevaCard.innerHTML = `
                            <div class="order-header">
                                <div>
                                    <h4>Pedido #ORD-${escapeHtml(p.id)}</h4>
                                    <p>Realizado el ${escapeHtml(p.fechapedido || 'Hoy')}</p>
                                </div>
                                <div>
                                    <span id="status-${escapeHtml(p.id)}" class="order-status ${claseBadge}">
                                        ${escapeHtml(p.estado)}
                                    </span>
                                </div>
                            </div>
                            <div class="order-items">
                                <div class="order-item">
                                    <span><strong>Productos:</strong> ${escapeHtml(p.productos || 'Platillos')}</span>
                                </div>
                                <div class="order-item order-item-details">
                                    <span><strong>Cliente:</strong> ${escapeHtml(p.cliente || 'Cliente')}</span>
                                </div>
                                <div class="order-item order-item-details">
                                    <span><strong>Metodo de Pago:</strong> ${escapeHtml(p.metodopago || 'Efectivo')}</span>
                                </div>
                                ${p.referenciapago ? `<div class="order-item order-item-details"><span><strong>Referencia:</strong> ${escapeHtml(p.referenciapago)}</span></div>` : ''}
                            </div>
                            <div class="order-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <div class="order-total-inline">
                                    <strong>Total: ${escapeHtml(p.total || '$0.00')}</strong>
                                </div>
                                <div id="accion-${escapeHtml(p.id)}" style="display: flex; gap: 10px;">
                                    ${p.estado === 'Pendiente' ? `
                                        <button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${escapeHtml(p.id)}' data-nuevo-estado='En preparación'>Aceptar</button>
                                        <button class='btn btn-danger btn-sm btn-actualizar-estado' data-idpedido='${escapeHtml(p.id)}' data-nuevo-estado='Rechazado'>Rechazar</button>
                                    ` : (p.estado === 'En preparación' ? `
                                        <button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${escapeHtml(p.id)}' data-nuevo-estado='Listo'>Listo</button>
                                    ` : '')}
                                </div>
                            </div>
                        `;
                        contVendedor.prepend(nuevaCard);
                    }
                }
            }

            // A2. Actualizar tarjetas existentes de vendedor
            for (const id of idsABuscar) {
                const spanVen = document.getElementById('status-' + id);
                if (spanVen && spanVen.textContent.trim() !== estadoNuevo) {
                    if (typeof window.actualizarFila === 'function') {
                        window.actualizarFila(id, estadoNuevo);
                    } else {
                        spanVen.textContent = estadoNuevo;
                        spanVen.className = 'order-status';
                        if (estadoNuevo === 'Pendiente') spanVen.classList.add('status-pending');
                        else if (estadoNuevo === 'En preparación') spanVen.classList.add('status-preparing');
                        else if (estadoNuevo === 'Listo') spanVen.classList.add('status-ready');
                        else if (estadoNuevo === 'Entregado') spanVen.classList.add('status-completed');
                        else if (estadoNuevo === 'Rechazado') spanVen.classList.add('status-rechazado');
                        else if (estadoNuevo === 'Cancelado') spanVen.classList.add('status-cancelado');
                        else spanVen.classList.add('status-default');

                        const divAccion = document.getElementById('accion-' + id);
                        if (divAccion) {
                            if (estadoNuevo === 'En preparación') {
                                divAccion.innerHTML = `<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${id}' data-nuevo-estado='Listo'>Listo</button>`;
                            } else if (estadoNuevo === 'Listo') {
                                divAccion.innerHTML = `<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${id}' data-nuevo-estado='Entregado'>Entregado</button>`;
                            } else if (estadoNuevo === 'Entregado') {
                                divAccion.innerHTML = `<span style='color: #2ecc71; font-weight: bold;'>Completado</span>`;
                            } else if (estadoNuevo === 'Rechazado') {
                                divAccion.innerHTML = `<span style='color: #e74c3c; font-weight: bold;'>Rechazado</span>`;
                            } else if (estadoNuevo === 'Cancelado') {
                                divAccion.innerHTML = `<span style='color: #e74c3c; font-weight: bold;'>Cancelado</span>`;
                            }
                        }
                    }
                }
            }

            // B. Si estamos en el panel de cliente (dashboard_cliente.php o MisPedidos.php)
            if (tipoPanel === 'cliente' && contCliente) {
                let existeEnDOMCli = false;
                for (const id of idsABuscar) {
                    if (document.getElementById('status-order-' + id) || document.querySelector(`.order-card[data-idpedido="${id}"]`)) {
                        existeEnDOMCli = true;
                        break;
                    }
                }

                // Si se generó un nuevo pedido y no está en la pantalla del cliente
                if (!existeEnDOMCli && p.id) {
                    contCliente.querySelectorAll(':scope > div:not([data-idpedido])').forEach(el => {
                        if (el.textContent.includes('No se encontraron pedidos')) {
                            el.remove();
                        }
                    });

                    const nuevaCardCli = document.createElement('div');
                    nuevaCardCli.className = 'order-card';
                    nuevaCardCli.dataset.idpedido = p.id;
                    let claseBadgeCli = 'status-pending';
                    if (p.estado === 'En preparación') claseBadgeCli = 'status-preparing';
                    else if (p.estado === 'Listo') claseBadgeCli = 'status-ready';
                    else if (p.estado === 'Entregado') claseBadgeCli = 'status-completed';
                    else if (p.estado === 'Cancelado' || p.estado === 'Rechazado') claseBadgeCli = 'status-rejected';

                    nuevaCardCli.innerHTML = `
                        <div class="order-header">
                            <div>
                                <h4>Pedido #ORD-${escapeHtml(p.id)}</h4>
                                <p>Realizado el ${escapeHtml(p.fechapedido || 'Hoy')}</p>
                            </div>
                            <div>
                                <span id="status-order-${escapeHtml(p.id)}" class="order-status ${claseBadgeCli}">${escapeHtml(p.estado)}</span>
                            </div>
                        </div>
                        <div class="order-items">
                            <div class="order-item">
                                <span>Productos: ${escapeHtml(p.productos || 'Platillo')}</span>
                            </div>
                            <div class="order-item order-item-details">
                                <span>Vendedor: <strong>${escapeHtml(p.vendedor || 'Vendedor')}</strong></span>
                            </div>
                        </div>
                        <div class="order-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <div class="order-item order-item-details">
                                <span class="order-total-inline"><strong>Total: ${escapeHtml(p.total || '$0.00')}</strong></span>
                            </div>
                            <div id="acciones-order-${escapeHtml(p.id)}" style="display: flex; gap: 10px; justify-content: flex-end;">
                                ${p.estado === 'Pendiente' ? `
                                    <form action="cancelar_pedido_cliente.php" method="POST" style="display:inline; margin: 0;">
                                        <input type="hidden" name="idpedido_a_cancelar" value="${escapeHtml(p.id)}">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
                                    </form>
                                ` : `
                                    <a href="repetir_pedido.php?idpedido=${escapeHtml(p.id)}" class="btn btn-primary">Volver a Pedir</a>
                                `}
                            </div>
                        </div>
                    `;
                    contCliente.prepend(nuevaCardCli);
                }
            }

            // B2. Actualizar tarjetas existentes de cliente
            for (const id of idsABuscar) {
                const spanCli = document.getElementById('status-order-' + id) 
                    || document.querySelector(`.order-card[data-idpedido="${id}"] .order-status`);

                if (spanCli && spanCli.textContent.trim() !== estadoNuevo) {
                    spanCli.textContent = estadoNuevo;
                    spanCli.className = 'order-status';

                    if (estadoNuevo === 'Pendiente') {
                        spanCli.classList.add('status-pending');
                    } else if (estadoNuevo === 'En preparación') {
                        spanCli.classList.add('status-preparing');
                    } else if (estadoNuevo === 'Listo') {
                        spanCli.classList.add('status-ready');
                    } else if (estadoNuevo === 'Entregado') {
                        spanCli.classList.add('status-completed');
                    } else if (estadoNuevo === 'Cancelado' || estadoNuevo === 'Rechazado') {
                        spanCli.classList.add('status-rejected');
                    } else {
                        spanCli.classList.add('status-default');
                    }

                    // Si el estado ya no es 'Pendiente', ocultar botones de acción (Cancelar y Pagar SPEI)
                    const card = spanCli.closest('.order-card');
                    if (card && estadoNuevo !== 'Pendiente') {
                        const acciones = card.querySelector('#acciones-order-' + id) 
                            || card.querySelector('.order-footer div[style*="justify-content: flex-end"]');
                        if (acciones) {
                            const formCancelar = acciones.querySelector('form[action*="cancelar_pedido_cliente"]');
                            if (formCancelar) formCancelar.remove();

                            const btnSPEI = acciones.querySelector('button[onclick*="simularPagoBanco"]');
                            if (btnSPEI) btnSPEI.remove();
                        }
                    }
                }
            }
        });

        // Disparar evento personalizado
        window.dispatchEvent(new CustomEvent('snackver:pedidos_actualizados', { detail: pedidos }));
    }

    // 5. Marcar todas las notificaciones como leídas
    function configurarBotonLeerTodas() {
        const btnLeerTodas = document.getElementById('btnMarcarTodasLeidas');
        if (btnLeerTodas) {
            btnLeerTodas.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Prevenir múltiples clics si ya se está procesando
                if (marcandoLeidasEnProgreso) return;
                marcandoLeidasEnProgreso = true;
                btnLeerTodas.disabled = true;

                // 1. RESPUESTA VISUAL INSTANTÁNEA (0ms) - Optimistic UI
                if (badge) {
                    badge.style.display = 'none';
                    badge.textContent = '';
                    badge.innerHTML = '';
                }
                const items = lista.querySelectorAll('.notif-item.unread');
                items.forEach(el => el.classList.remove('unread'));

                // 2. Persistencia en segundo plano en MongoDB Atlas
                const formData = new FormData();
                if (tipoPanel) {
                    formData.append('tipo', tipoPanel);
                }

                fetch(baseUrlNotif + 'marcar_todas_leidas.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        // Confirmado por el servidor
                    })
                    .catch(err => {
                        console.error('Error al marcar leídas:', err);
                    })
                    .finally(() => {
                        marcandoLeidasEnProgreso = false;
                        btnLeerTodas.disabled = false;
                    });
            });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 6. Interceptar cancelación de pedidos del cliente en 0ms sin recargar la página
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.tagName === 'FORM' && form.getAttribute('action') && form.getAttribute('action').includes('cancelar_pedido_cliente.php')) {
            e.preventDefault();
            const inputId = form.querySelector('input[name="idpedido_a_cancelar"]');
            const idPedido = inputId ? inputId.value : '';
            if (!idPedido) return;

            const btnSubmit = form.querySelector('button[type="submit"]');
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.textContent = 'Cancelando...';
            }

            // Actualización optimista inmediata (0ms)
            const spanEstado = document.getElementById('status-order-' + idPedido);
            if (spanEstado) {
                spanEstado.textContent = 'Cancelado';
                spanEstado.className = 'order-status status-rejected';
            }
            const divAcciones = document.getElementById('acciones-order-' + idPedido);
            if (divAcciones) {
                if (window.location.pathname.includes('dashboard_cliente.php')) {
                    divAcciones.innerHTML = `<a href="repetir_pedido.php?idpedido=${escapeHtml(idPedido)}" class="btn btn-primary">Volver a Pedir</a>`;
                } else {
                    divAcciones.innerHTML = '';
                }
            }

            const formData = new FormData(form);
            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        consultarNotificaciones();
                    } else {
                        alert(data.message || 'No se pudo cancelar el pedido.');
                        consultarNotificaciones();
                    }
                })
                .catch(() => {
                    consultarNotificaciones();
                });
        }
    });

    // Exponer función para invocación manual/inmediata desde otros scripts
    window.consultarNotificaciones = consultarNotificaciones;

    // Inicializar
    configurarBotonLeerTodas();
    consultarNotificaciones();

    // Polling en tiempo real cada 1.5 segundos (1500 ms) si la pestaña está activa
    setInterval(function () {
        if (!document.hidden) {
            consultarNotificaciones();
        }
    }, 1500);

    // Actualizar inmediatamente al volver a la pestaña
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            consultarNotificaciones();
        }
    });

    // Actualizar inmediatamente al recibir el foco la ventana (0ms al alternar ventanas en local)
    window.addEventListener('focus', function () {
        consultarNotificaciones();
    });
});
