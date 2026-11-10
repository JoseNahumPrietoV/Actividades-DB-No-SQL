<div>
<x-filament-panels::page>
    <div x-data="sigemDashboardSS()" class="sigem-professional" x-cloak>

        @php
            $user = auth()->user();
            $initial = substr($user->name ?? 'U', 0, 1);
            $role = $user->tipo_usuario ?? 'Usuario';
        @endphp

        <!-- 1. HEADER -->
        <header class="top-header">
            <div class="header-brand">
                <div class="brand-logo">S</div>
                <div class="brand-text-container">
                    <span class="brand-title">SIGEM – Servicio Social</span>
                    <span class="brand-subtitle">Módulo de Usuarios</span>
                </div>
            </div>
            <div class="header-user">
                <button type="button" class="btn-theme" title="Alternar tema"
                    x-data="{ 
                        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
                        toggle() {
                            this.theme = this.theme === 'dark' ? 'light' : 'dark';
                            localStorage.setItem('theme', this.theme);
                            if (this.theme === 'dark') {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                        }
                    }" 
                    @click="toggle()">
                    <svg x-show="theme === 'dark'" style="display:none;" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg x-show="theme !== 'dark'" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>
                <div class="user-info" x-data="{ open: false }" style="position:relative;">
                    <div class="user-text">
                        <div class="user-name text-primary-util">{{ $user->name ?? 'Usuario' }}</div>
                        <div class="user-role text-secondary-util">{{ $role }}</div>
                    </div>
                    <button type="button" class="user-avatar" @click="open = !open" style="border:none; cursor:pointer; outline:none;">
                        {{ strtoupper($initial) }}
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition.opacity.duration.200ms class="user-dropdown" style="display:none; position:absolute; top:48px; right:0; background:white; border:1px solid var(--slate-200); border-radius:12px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); width:200px; z-index:50; overflow:hidden;">
                        <div style="padding:12px 16px; border-bottom:1px solid var(--slate-100);">
                            <div class="text-primary-util" style="font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->name ?? 'Usuario' }}</div>
                            <div class="text-secondary-util" style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->email ?? '' }}</div>
                        </div>
                        <div style="padding:8px;">
                            <form method="POST" action="{{ route('filament.servicio-social.auth.logout') }}">
                                @csrf
                                <button type="submit" style="width:100%; text-align:left; background:transparent; border:none; padding:8px 12px; font-size:13px; font-weight:500; color:var(--red-600); cursor:pointer; display:flex; align-items:center; gap:8px; border-radius:6px; transition:0.18s;" onmouseover="this.style.background='var(--red-50)'" onmouseout="this.style.background='transparent'">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- 2. BANNER -->
        <div class="welcome-banner">
            <div>
                <h1 class="banner-greeting"><span x-text="getGreeting()"></span>, {{ explode(' ', $user->name)[0] ?? 'Usuario' }}</h1>
                <p class="banner-sub">Tienes <strong>{{ $misSolicitudesPendientes ?? 0 }} solicitudes</strong> pendientes de revisión.</p>
            </div>
            <div class="banner-actions">
                <button class="btn btn-glass" @click="openCatalog('modulo-solicitudes')">Ver mis solicitudes</button>
            </div>
        </div>

        <!-- 3. STATS (SS Scope) -->
        <div class="grid-4">
            <!-- Stat 1 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">TOTAL ACTIVOS EN SISTEMA</span>
                    <div class="stat-icon bg-brand"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $totalActivos ?? 0 }}</h2>
                <div class="stat-sub text-brand">Registros globales</div>
            </div>
            
            <!-- Stat 2 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">MIS SOLICITUDES</span>
                    <div class="stat-icon bg-emerald"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misSolicitudes ?? 0 }}</h2>
                <div class="stat-sub text-emerald">{{ $solicitudesAutorizadas ?? 0 }} autorizadas</div>
            </div>

            <!-- Stat 3 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">MIS MANTENIMIENTOS</span>
                    <div class="stat-icon bg-amber"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misMantenimientos ?? 0 }}</h2>
                <div class="stat-sub text-amber">{{ $mantenimientoEnProceso ?? 0 }} en proceso</div>
            </div>

            <!-- Stat 4 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">SOLICITUDES PENDIENTES</span>
                    <div class="stat-icon bg-red"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text">{{ $misSolicitudesPendientes ?? 0 }}</h2>
                <div class="stat-sub {{ ($misSolicitudesPendientes ?? 0) > 0 ? 'text-red' : 'text-emerald' }}">
                    {{ ($misSolicitudesPendientes ?? 0) > 0 ? 'en espera de autorización' : 'al día' }}
                </div>
            </div>
        </div>

        <!-- ACCESOS RÁPIDOS MÓVIL SS -->
        <div class="mobile-only" style="margin-bottom: 24px;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="{{ url('/servicio-social/solicituds/create') }}" class="btn" style="background: var(--amber-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    Nueva Solicitud
                </a>
                <a href="{{ url('/servicio-social/mantenimientos/create') }}" class="btn" style="background: var(--red-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    </div>
                    Reportar Falla
                </a>
                <a href="{{ url('/servicio-social/inventarios') }}" class="btn" style="background: var(--brand-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    </div>
                    Ver Inventario
                </a>
            </div>
        </div>

        <!-- 4. MODULES (SS Scope) -->
        <div class="grid-2 hidden-mobile">
            <!-- Solicitudes -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-brand" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="mod-title">Mis Solicitudes</div>
                    <div class="mod-badge" style="background:var(--amber-50); color:var(--amber-600);">{{ $misSolicitudesPendientes ?? 0 }} Pendientes</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $misSolicitudesPendientes ?? 0 }}</div><div class="mod-stat-label">Pendientes</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $solicitudesAutorizadas ?? 0 }}</div><div class="mod-stat-label">Autorizadas</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $solicitudesRechazadas ?? 0 }}</div><div class="mod-stat-label">Rechazadas</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>#</th><th>Solicitante</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($solicitudesRecientes ?? [] as $sol)
                            <tr>
                                <td class="mono-text">{{ str_pad($sol->id_solicitud, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ explode(' ', $sol->usuario?->name)[0] ?? 'Usuario' }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if($sol->estado === 'Pendiente') $cls = 'badge-amber';
                                        elseif($sol->estado === 'Autorizado' || $sol->estado === 'Completado') $cls = 'badge-green';
                                        elseif(in_array($sol->estado, ['Rechazado', 'Cancelado'])) $cls = 'badge-red';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $sol->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted-util" style="text-align: center; padding: 24px;">No tienes solicitudes recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer" style="display:flex; justify-content:space-between; align-items:center;">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-solicitudes')">Ver todas →</button>
                    <a href="{{ url('/servicio-social/solicituds/create') }}" class="btn-blue" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Nueva Solicitud
                    </a>
                </div>
            </div>

            <!-- Mantenimiento -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-amber" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                    <div class="mod-title">Mis Mantenimientos</div>
                    <div class="mod-badge">{{ $misMantenimientos ?? 0 }} Total</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoEnRevision ?? 0 }}</div><div class="mod-stat-label">En revisión</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoEnProceso ?? 0 }}</div><div class="mod-stat-label">En proceso</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $mantenimientoCompletados ?? 0 }}</div><div class="mod-stat-label">Completados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>#</th><th>Equipo</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($mantenimientosRecientes ?? [] as $mant)
                            <tr>
                                <td class="mono-text">{{ str_pad($mant->id_mantenimiento, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $mant->inventario?->num_serie ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if(in_array($mant->estado, ['Solicitado', 'Pendiente Revision Admin'])) $cls = 'badge-red';
                                        elseif($mant->estado === 'En proceso') $cls = 'badge-amber';
                                        elseif($mant->estado === 'Completado') $cls = 'badge-green';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $mant->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted-util" style="text-align: center; padding: 24px;">No tienes mantenimientos recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer" style="display:flex; justify-content:space-between; align-items:center;">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-mantenimiento')">Ver todos →</button>
                    <a href="{{ url('/servicio-social/mantenimientos/create') }}" class="btn-blue" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        Reportar Falla
                    </a>
                </div>
            </div>
        </div>
            <!-- Inventario -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-emerald" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                    <div class="mod-title">Inventario general</div>
                    <div class="mod-badge">Consultas</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioDisponibles ?? 0 }}</div><div class="mod-stat-label">Disponibles</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioEnMantenimiento ?? 0 }}</div><div class="mod-stat-label">Mantenimiento</div></div>
                    <div class="mod-stat"><div class="mod-stat-val">{{ $inventarioDanados ?? 0 }}</div><div class="mod-stat-label">Dañados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th>Código</th><th>Equipo</th><th>Estado</th></tr></thead>
                        <tbody>
                            @forelse($inventariosRecientes ?? [] as $inv)
                            <tr>
                                <td class="mono-text">{{ $inv->num_serie }}</td>
                                <td>{{ Str::limit($inv->material?->nombre ?? 'N/A', 15) }}</td>
                                <td>
                                    @php
                                        $cls = 'badge-slate';
                                        if($inv->estado === 'Disponible') $cls = 'badge-green';
                                        elseif($inv->estado === 'Asignado') $cls = 'badge-blue';
                                        elseif($inv->estado === 'En Mantenimiento') $cls = 'badge-amber';
                                        elseif(in_array($inv->estado, ['Dañado', 'Baja'])) $cls = 'badge-red';
                                    @endphp
                                    <span class="badge {{ $cls }}">{{ $inv->estado }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted-util" style="text-align: center; padding: 24px;">Sin registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer" style="display:flex; justify-content:space-between; align-items:center;">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-inventario')">Ver todo →</button>
                    <a href="{{ url('/servicio-social/inventarios/create') }}" class="btn-blue" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Agregar activo
                    </a>
                </div>
            </div>


        <!-- 5. CATALOGS (READ ONLY) -->
        <div class="section-block">
            <div class="block-header">
                <div class="block-title-wrap">
                    <div class="block-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                    <div>
                        <h2 class="block-title">Catálogos del sistema</h2>
                        <p class="block-subtitle">Modo sólo lectura · {{ $totalRegistrosCatalogos ?? 0 }} registros</p>
                    </div>
                </div>
                <div class="catalog-search">
                    <div class="search-input-wrap">
                        <svg class="search-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" class="search-input" x-model="searchQuery" id="catSearchInput" placeholder="Buscar catálogos...">
                    </div>
                </div>
            </div>

            <div class="recent-row">
                <div class="recent-label"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> BÚSQUEDA RÁPIDA</div>
                <button class="recent-chip" @click="openCatalog('departamentos')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-brand-500"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg> Departamentos</button>
                <button class="recent-chip" @click="openCatalog('materiales')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-emerald-500"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg> Materiales</button>
            </div>

            <!-- G1: ORG -->
            <div class="catalog-group" x-show="matchesSearch('departamentos', 'áreas', 'usuarios')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-brand" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"></path></svg></div>
                        <div>
                            <span class="group-title-text">Organización</span>
                            <span class="group-title-sub">· estructura institucional</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-3">
                    <div class="cat-card" x-show="matchesSearch('departamentos')" @click="openCatalog('departamentos')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, var(--brand-500), var(--brand-600));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Departamentos</h3>
                        <p class="cat-desc">Áreas académicas y administrativas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($departamentosList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('áreas')" @click="openCatalog('areas')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #22d3ee, var(--cyan-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Áreas</h3>
                        <p class="cat-desc">Espacios físicos y oficinas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($areasList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('usuarios')" @click="openCatalog('usuarios')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #818cf8, var(--indigo-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Usuarios</h3>
                        <p class="cat-desc">Personal y cuentas del sistema</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($usuariosList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                </div>
            </div>

            <!-- G2: INVENTARIO -->
            <div class="catalog-group" x-show="matchesSearch('materiales', 'marcas', 'tipos', 'unidades')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-emerald" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        <div>
                            <span class="group-title-text">Inventario</span>
                            <span class="group-title-sub">· configuración de activos</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-4">
                    <div class="cat-card" x-show="matchesSearch('materiales')" @click="openCatalog('materiales')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #34d399, var(--emerald-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Materiales</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($materialesList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('marcas')" @click="openCatalog('marcas')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #2dd4bf, var(--teal-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8l4 4-4 4M8 12h8"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Marcas</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($marcasList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('tipos')" @click="openCatalog('tipos')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #a78bfa, var(--violet-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                        </div>
                        <h3 class="cat-title">Tipos de Mat.</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($tiposList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('unidades')" @click="openCatalog('unidades')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #fb7185, var(--rose-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        </div>
                        <h3 class="cat-title">Unidades</h3>
                        <div class="cat-footer">
                            <span class="cat-number" style="font-size: 20px;">{{ count($unidadesList ?? []) }}</span>
                            <span class="cat-num-label">registros</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- G3: CONTACTOS -->
            <div class="catalog-group" x-show="matchesSearch('proveedores', 'receptores')" style="border-bottom: none;">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon" style="background: var(--purple-500); opacity: 0.1; width: 32px; height: 32px; border-radius: 8px; position:absolute;"></div>
                        <div class="stat-icon" style="color: var(--purple-500); width: 32px; height: 32px; border-radius: 8px; z-index:1;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                        <div>
                            <span class="group-title-text">Contactos</span>
                            <span class="group-title-sub">· personas y proveedores</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid-4" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="cat-card" x-show="matchesSearch('proveedores')" @click="openCatalog('proveedores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #c084fc, var(--purple-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                        </div>
                        <h3 class="cat-title">Proveedores</h3>
                        <p class="cat-desc">Empresas y distribuidores</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($proveedoresList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('receptores')" @click="openCatalog('receptores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #e879f9, var(--fuchsia-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                        </div>
                        <h3 class="cat-title">Receptores</h3>
                        <p class="cat-desc">Personas que reciben asignaciones</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number">{{ count($receptoresList ?? []) }}</div>
                                <div class="cat-num-label">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview">Ver detalles del catálogo</div>
                    </div>
                </div>
            </div>

            <div class="block-footer">
                <div>Tip: presiona <span class="kbd">/</span> para buscar catálogos</div>
            </div>
        </div>

        <!-- 6. ACTIVITY -->
        <div class="section-block">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title" style="font-size: 16px;">Mi Actividad Reciente</h2>
            </div>
            <div class="timeline">
                @forelse($actividadReciente ?? [] as $act)
                    @php
                        $dot = 'create';
                        $acc = strtolower($act->event ?? '');
                        if(str_contains($acc, 'crea')) $dot = 'create';
                        elseif(str_contains($acc, 'actualiza') || str_contains($acc, 'edita')) $dot = 'update';
                        elseif(str_contains($acc, 'autoriza')) $dot = 'auth';
                        elseif(str_contains($acc, 'elimina')) $dot = 'delete';
                    @endphp
                <div class="timeline-item">
                    <div class="dot {{ $dot }}"></div>
                    <div class="tl-content">
                        @php
                            $accionesMap = [
                                'crear' => 'Creaste un',
                                'editar' => 'Editaste un',
                                'eliminar' => 'Eliminaste un',
                                'autorizar' => 'Autorizaste un',
                            ];
                            $accionTexto = $accionesMap[strtolower($act->event ?? '')] ?? ('Has ' . strtolower($act->event ?? '') . ' en');
                        @endphp
                        <p class="tl-text">{{ $accionTexto }} <em>{{ class_basename($act->subject_type) }}</em></p>
                        <div class="tl-time">{{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <div class="block-footer" style="background: white; border-top: 1px solid var(--slate-100); justify-content: center;">
                    <p class="text-muted-util" style="text-align: center;">Sin actividad reciente.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- SLIDE-OVER (Alpine Component) -->
        <div x-show="activeCatalog" style="display: none;" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Overlay -->
            <div x-show="activeCatalog" 
                 x-transition:enter="transition-opacity ease-linear duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition-opacity ease-linear duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="slide-overlay" @click="closeCatalog()"></div>

            <div class="fixed inset-0 overflow-hidden" style="z-index: 101; pointer-events: none;">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10" style="width: 100%;">
                        <!-- Panel -->
                        <div x-show="activeCatalog" 
                             x-transition:enter="transform transition ease-in-out duration-300" 
                             x-transition:enter-start="translate-x-full" 
                             x-transition:enter-end="translate-x-0" 
                             x-transition:leave="transform transition ease-in-out duration-200" 
                             x-transition:leave-start="translate-x-0" 
                             x-transition:leave-end="translate-x-full" 
                             class="pointer-events-auto w-full slide-panel" style="max-width: 800px; width: 100%; margin-left: auto;"
                             @keydown.escape.window="closeCatalog()">
                            
                            <!-- Header -->
                            <div class="slide-header">
                                <div class="slide-title-area">
                                    <div>
                                        <h2 id="slide-over-title" class="slide-title" x-text="getCatalogTitle()">Catálogo</h2>
                                        <p class="slide-subtitle">Consulta de registros (Solo Lectura)</p>
                                    </div>
                                </div>
                                <div class="slide-actions">
                                    <button type="button" class="btn-close" @click="closeCatalog()">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="slide-body">
                                
                                <!-- TABLE VIEW ONLY -->
                                <div class="slide-table-card">
                                    <div class="table-toolbar">
                                        <div class="catalog-search" style="width: 300px;">
                                            <div class="search-input-wrap" style="width: 100%;">
                                                <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                <input type="text" class="search-input" style="width: 100%; padding: 8px 12px 8px 36px;" x-model="tableSearch" @input="page = 1" placeholder="Buscar registros...">
                                            </div>
                                        </div>
                                        <div class="table-results"><span x-text="getFilteredData().length"></span> resultados</div>
                                    </div>

                                    <div style="overflow-x: auto;">
                                        <div class="table-results" style="padding: 16px 24px; border-bottom: 1px solid var(--slate-200);">Mostrando registros del catálogo</div>
                                        <table class="st-wrap">
                                            <thead>
                                                <tr x-html="getTableHeaders()"></tr>
                                            </thead>
                                            <tbody x-html="getTableRows()"></tbody>
                                        </table>
                                        <div x-show="!getTableRows()" class="text-muted-util" style="padding: 40px; text-align: center;">
                                            No hay resultados
                                        </div>
                                    </div>
                                    <!-- Pagination -->
                                    <div class="table-pagination" x-show="totalPages() > 1" style="padding: 12px 24px; border-top: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; background: white;">
                                        <span class="text-secondary-util" style="font-size: 13px;">Página <strong class="text-primary-util" x-text="page"></strong> de <strong class="text-primary-util" x-text="totalPages()"></strong></span>
                                        <div style="display: flex; gap: 8px;">
                                            <button @click="if(page > 1) page--" :disabled="page === 1" class="btn-icon" style="background: var(--slate-100); padding: 4px 12px; border-radius: 6px;">Ant.</button>
                                            <button @click="if(page < totalPages()) page++" :disabled="page === totalPages()" class="btn-icon" style="background: var(--slate-100); padding: 4px 12px; border-radius: 6px;">Sig.</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="slide-footer" style="justify-content: space-between;">
                                <div><a :href="getExternalListUrl()" class="link-blue">Vista avanzada →</a></div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px;">
                                    <div class="text-secondary-util" style="font-size: 13px;">por página: <strong class="text-primary-util">10</strong> <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inject data to JS -->
        <script>
            const registerSigemDashboardSS = () => {
                Alpine.data('sigemDashboardSS', () => ({
                    getGreeting() {
                        const hour = new Date().getHours();
                        if (hour >= 6 && hour < 12) return 'Buenos días';
                        if (hour >= 12 && hour < 18) return 'Buenas tardes';
                        return 'Buenas noches';
                    },
                    searchQuery: '',
                    activeCatalog: null,
                    tableSearch: '',
                    page: 1,
                    pageSize: 10,
                    
                    data: {
                        departamentos: @json($departamentosList ?? []),
                        areas: @json($areasList ?? []),
                        usuarios: @json($usuariosList ?? []),
                        materiales: @json($materialesList ?? []),
                        marcas: @json($marcasList ?? []),
                        tipos: @json($tiposList ?? []),
                        unidades: @json($unidadesList ?? []),
                        proveedores: @json($proveedoresList ?? []),
                        receptores: @json($receptoresList ?? []),
                        'modulo-inventario': @json($inventariosCompletos ?? []),
                        'modulo-solicitudes': @json($solicitudesCompletas ?? []),
                        'modulo-mantenimiento': @json($mantenimientosCompletos ?? []),
                    },

                    init() {
                        window.addEventListener('keydown', (e) => {
                            if (e.key === '/' && this.activeCatalog === null) {
                                e.preventDefault();
                                document.getElementById('catSearchInput').focus();
                            }
                        });
                    },

                    matchesSearch(...terms) {
                        if (this.searchQuery === '') return true;
                        const q = this.searchQuery.toLowerCase();
                        return terms.some(t => t.toLowerCase().includes(q));
                    },

                    openCatalog(id) {
                        this.activeCatalog = id;
                        this.tableSearch = '';
                        this.page = 1;
                    },

                    closeCatalog() {
                        this.activeCatalog = null;
                    },

                    getCatalogTitle() {
                        const titles = {
                            departamentos: 'Departamentos', areas: 'Áreas', usuarios: 'Usuarios',
                            materiales: 'Materiales', marcas: 'Marcas', tipos: 'Tipos de material',
                            unidades: 'Unidades de medida', proveedores: 'Proveedores', receptores: 'Receptores',
                            'modulo-inventario': 'Inventario general', 'modulo-solicitudes': 'Mis Solicitudes', 'modulo-mantenimiento': 'Mis Mantenimientos'
                        };
                        return titles[this.activeCatalog] || 'Catálogo';
                    },

                    getExternalListUrl() {
                        const map = {
                            departamentos: '#', areas: '/servicio-social/areas', usuarios: '#',
                            materiales: '/servicio-social/materials', marcas: '/servicio-social/marca-materials', tipos: '/servicio-social/tipo-materials',
                            unidades: '/servicio-social/unidad-medidas', proveedores: '#', receptores: '/servicio-social/receptors',
                            'modulo-inventario': '/servicio-social/inventarios', 'modulo-solicitudes': '/servicio-social/solicituds', 'modulo-mantenimiento': '/servicio-social/mantenimientos'
                        };
                        return map[this.activeCatalog] || '#';
                    },

                    getTableHeaders() {
                        const headers = {
                            departamentos: ['Nombre'],
                            areas: ['Nombre', 'Departamento', 'Estado'],
                            usuarios: ['Nombre', 'Email', 'Rol'],
                            materiales: ['Nombre', 'Tipo', 'Unidad', 'Marca'],
                            marcas: ['Nombre', 'Materiales'],
                            tipos: ['Nombre'],
                            unidades: ['Nombre'],
                            proveedores: ['Empresa', 'Contacto', 'RFC'],
                            receptores: ['Nombre', 'Área', 'Departamento'],
                            'modulo-inventario': ['N/S', 'Material', 'Estado'],
                            'modulo-solicitudes': ['Folio', 'Usuario', 'Estado'],
                            'modulo-mantenimiento': ['Activo', 'Técnico', 'Estado']
                        };
                        const cols = headers[this.activeCatalog] || [];
                        return cols.map(c => `<th>${c}</th>`).join('');
                    },

                    totalPages() {
                        return Math.ceil(this.getFilteredData().length / this.pageSize) || 1;
                    },

                    paginatedData() {
                        const start = (this.page - 1) * this.pageSize;
                        return this.getFilteredData().slice(start, start + this.pageSize);
                    },

                    getFilteredData() {
                        let rawItems = this.data[this.activeCatalog] || [];
                        const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                        if (!this.tableSearch) return items;
                        const q = this.tableSearch.toLowerCase();
                        return items.filter(item => JSON.stringify(item).toLowerCase().includes(q));
                    },

                    getTableRows() {
                        const items = this.paginatedData();
                        if (items.length === 0) return '';
                        
                        return items.map(item => {
                            let cols = '';
                            if (this.activeCatalog === 'departamentos') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'areas') {
                                cols = `<td>${item.nombre}</td><td>${item.departamento?.nombre || ''}</td><td><span class="badge badge-green">Activo</span></td>`;
                            } else if (this.activeCatalog === 'usuarios') {
                                let roles = item.tipo_usuario || '';
                                cols = `<td>${item.name}</td><td>${item.email}</td><td>${roles}</td>`;
                            } else if (this.activeCatalog === 'materiales') {
                                cols = `<td>${item.nombre}</td><td>${item.tipo?.nombre || ''}</td><td>${item.unidad?.nombre || ''}</td><td>${item.marca?.nombre || ''}</td>`;
                            } else if (this.activeCatalog === 'marcas') {
                                cols = `<td>${item.nombre}</td><td>${item.materiales_count || 0}</td>`;
                            } else if (this.activeCatalog === 'tipos') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'unidades') {
                                cols = `<td>${item.nombre}</td>`;
                            } else if (this.activeCatalog === 'proveedores') {
                                cols = `<td>${item.nombre_empresa}</td><td>${item.contacto_nombre || ''}</td><td class="mono-text">${item.rfc || ''}</td>`;
                            } else if (this.activeCatalog === 'receptores') {
                                cols = `<td>${item.nombre} ${item.apellido_paterno || ''}</td><td>${item.area?.nombre || ''}</td><td>${item.area?.departamento?.nombre || ''}</td>`;
                            } else if (this.activeCatalog === 'modulo-inventario') {
                                let badge = ['Disponible'].includes(item.estado) ? 'badge-green' : (item.estado === 'Asignado' ? 'badge-blue' : 'badge-amber');
                                cols = `<td class="mono-text">${item.num_serie}</td><td>${item.material?.nombre || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            } else if (this.activeCatalog === 'modulo-solicitudes') {
                                let badge = item.estado === 'Autorizado' ? 'badge-green' : (item.estado === 'Pendiente' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">SOL-${String(item.id_solicitud).padStart(4,'0')}</td><td>${item.usuario?.name || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            } else if (this.activeCatalog === 'modulo-mantenimiento') {
                                let badge = item.estado === 'Completado' ? 'badge-green' : (item.estado === 'En proceso' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">${item.inventario?.num_serie || ''}</td><td>${item.nombre_tecnico || 'N/A'}</td><td><span class="badge ${badge}">${item.estado}</span></td>`;
                            }
                            
                            return `<tr>${cols}</tr>`;
                        }).join('');
                    }
                }));
            };

            if (window.Alpine) {
                registerSigemDashboardSS();
            } else {
                document.addEventListener('alpine:init', registerSigemDashboardSS);
            }
        </script>
    </div>
</x-filament-panels::page>
</div>
