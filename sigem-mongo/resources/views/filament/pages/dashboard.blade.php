<div>
<x-filament-panels::page>
    <div x-data="sigemDashboard()" class="sigem-professional" x-cloak>

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
                    <span class="brand-title text-primary-util">SIGEM – TecNM Veracruz</span>
                    <span class="brand-subtitle text-secondary-util">Sistema de Gestión de Inventario</span>
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
                            <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
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
                <h1 class="banner-greeting text-primary-util"><span x-text="getGreeting()"></span>, {{ explode(' ', $user->name)[0] ?? 'Usuario' }}</h1>
                <p class="banner-sub text-secondary-util">Tienes <strong>{{ $solicitudesPendientes ?? 0 }} solicitudes</strong> por autorizar y <strong>{{ $mantenimientosPendientes ?? 0 }} mantenimientos</strong> programados.</p>
            </div>
            <div class="banner-actions">
                <button class="btn btn-glass" @click="openCatalog('modulo-solicitudes')">Ver pendientes</button>
                <a href="{{ url('/admin/inventarios/create') }}" class="btn btn-white">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo Inventario
                </a>
            </div>
        </div>

        <!-- 3. STATS -->
        <div class="grid-4">
            <!-- Stat 1 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">TOTAL ACTIVOS</span>
                    <div class="stat-icon bg-brand"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $totalActivos ?? 0 }}</h2>
                <div class="stat-sub text-emerald">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                    +{{ $creadosEsteMes ?? 0 }} este mes
                </div>

            </div>
            
            <!-- Stat 2 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">EN BUEN ESTADO</span>
                    <div class="stat-icon bg-emerald"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $activosBueno ?? 0 }}</h2>
                <div class="stat-sub text-secondary-util">{{ $porcentajeBuenEstado ?? 0 }}% del inventario</div>
                <div class="progress-bar-wrap hidden-mobile">
                    <div class="progress-bar-fill" style="width: {{ $porcentajeBuenEstado ?? 0 }}%;"></div>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">MANTENIMIENTOS</span>
                    <div class="stat-icon bg-amber"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $mantenimientosTotales ?? 0 }}</h2>
                <div class="stat-sub text-amber-util">{{ $mantenimientosPendientes ?? 0 }} pendientes/revisión</div>

            </div>

            <!-- Stat 4 -->
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title text-secondary-util">ALERTAS STOCK</span>
                    <div class="stat-icon bg-red"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                </div>
                <h2 class="stat-val mono-text text-primary-util">{{ $materialesStockBajoCount ?? 0 }}</h2>
                <div class="stat-sub {{ ($materialesStockBajoCount ?? 0) > 0 ? 'text-red' : 'text-emerald' }}">
                    {{ ($materialesStockBajoCount ?? 0) > 0 ? 'requiere atención' : 'todo en orden' }}
                </div>
                <div class="severity-bars hidden-mobile">
                    @php $sev = min(5, max(0, $materialesStockBajoCount ?? 0)); @endphp
                    <div class="severity-bar {{ $sev > 0 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 1 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 2 ? 'active' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 3 ? 'active-light' : '' }}"></div>
                    <div class="severity-bar {{ $sev > 4 ? 'active-light' : '' }}"></div>
                </div>
            </div>
        </div>

        <!-- ACCESOS RÁPIDOS MÓVIL (Cambio 1) -->
        <div class="mobile-only" style="margin-bottom: 24px;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="{{ url('/admin/inventarios') }}" class="btn" style="background: var(--brand-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    </div>
                    Inventario
                    <span style="margin-left: auto; background: white; color: var(--brand-600); padding: 4px 8px; border-radius: 99px; font-size: 12px;">{{ $totalActivos ?? 0 }}</span>
                </a>
                <a href="{{ url('/admin/solicituds') }}" class="btn" style="background: var(--amber-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    Solicitudes
                    <span style="margin-left: auto; background: white; color: var(--amber-600); padding: 4px 8px; border-radius: 99px; font-size: 12px;">{{ $solicitudesPendientes ?? 0 }}</span>
                </a>
                <a href="{{ url('/admin/mantenimientos') }}" class="btn" style="background: var(--red-500); color: white; min-height: 56px; justify-content: flex-start; padding: 0 20px; font-size: 16px;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    </div>
                    Mantenimiento
                    <span style="margin-left: auto; background: white; color: var(--red-600); padding: 4px 8px; border-radius: 99px; font-size: 12px;">{{ $mantenimientosPendientes ?? 0 }}</span>
                </a>
                <a href="{{ url('/admin') }}" class="btn btn-white" style="min-height: 56px; margin-top: 8px; font-size: 16px;">
                    Ver Catálogos Completos
                </a>
            </div>
        </div>

        <!-- 5. MODULES -->
        <div class="grid-3 hidden-mobile">
            <!-- Inventario -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-emerald" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                    <div class="mod-title text-primary-util">Inventario</div>
                    <div class="mod-badge">{{ $totalActivos ?? 0 }} Total</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $inventarioDisponibles ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Disponibles</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $inventarioEnMantenimiento ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Mantenimiento</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $inventarioDanados ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Dañados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th class="text-secondary-util">Código</th><th class="text-secondary-util">Equipo</th><th class="text-secondary-util">Estado</th></tr></thead>
                        <tbody>
                            @forelse($inventariosRecientes ?? [] as $inv)
                            <tr>
                                <td class="mono-text text-primary-util">{{ $inv->num_serie }}</td>
                                <td class="text-primary-util">{{ Str::limit($inv->material?->nombre ?? 'N/A', 15) }}</td>
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
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-inventario')">Ver todo →</button>
                    <a href="{{ url('/admin/inventarios/create') }}" class="btn-blue">Crear</a>
                </div>
            </div>

            <!-- Solicitudes -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-brand" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="mod-title text-primary-util">Solicitudes</div>
                    <div class="mod-badge bg-amber-util text-amber-util">{{ $solicitudesPendientes ?? 0 }} Pendientes</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $solicitudesPendientes ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Pendientes</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $solicitudesAutorizadas ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Autorizadas</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $solicitudesRechazadas ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Rechazadas</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th class="text-secondary-util">#</th><th class="text-secondary-util">Solicitante</th><th class="text-secondary-util">Estado</th></tr></thead>
                        <tbody>
                            @forelse($solicitudesRecientes ?? [] as $sol)
                            <tr>
                                <td class="mono-text text-primary-util">S-{{ str_pad($sol->id_solicitud, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="text-primary-util">{{ explode(' ', $sol->usuario?->name)[0] ?? 'Usuario' }}</td>
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
                            <tr><td colspan="3" class="text-muted-util" style="text-align: center; padding: 24px;">Sin registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-solicitudes')">Ver todas →</button>
                    <a href="{{ url('/admin/solicituds') }}" class="btn-green">Autorizar</a>
                </div>
            </div>

            <!-- Mantenimiento -->
            <div class="module-card">
                <div class="mod-header">
                    <div class="stat-icon bg-amber" style="width: 36px; height: 36px; border-radius: 8px;"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                    <div class="mod-title text-primary-util">Mantenimiento</div>
                    <div class="mod-badge">{{ $mantenimientosTotales ?? 0 }} Total</div>
                </div>
                <div class="mod-stats">
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $mantenimientoEnRevision ?? 0 }}</div><div class="mod-stat-label text-secondary-util">En revisión</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $mantenimientoEnProceso ?? 0 }}</div><div class="mod-stat-label text-secondary-util">En proceso</div></div>
                    <div class="mod-stat"><div class="mod-stat-val text-primary-util">{{ $mantenimientoCompletados ?? 0 }}</div><div class="mod-stat-label text-secondary-util">Completados</div></div>
                </div>
                <div class="mini-table-wrap">
                    <table class="mod-table">
                        <thead><tr><th class="text-secondary-util">#</th><th class="text-secondary-util">Equipo</th><th class="text-secondary-util">Estado</th></tr></thead>
                        <tbody>
                            @forelse($mantenimientosRecientes ?? [] as $mant)
                            <tr>
                                <td class="mono-text text-primary-util">M-{{ str_pad($mant->id_mantenimiento, 4, '0', STR_PAD_LEFT) }}</td>
                                <td class="text-primary-util">{{ $mant->inventario?->num_serie ?? 'N/A' }}</td>
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
                            <tr><td colspan="3" class="text-muted-util" style="text-align: center; padding: 24px;">Sin registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mod-footer">
                    <button class="link-blue" style="background:transparent;border:none;padding:0;cursor:pointer;" @click="openCatalog('modulo-mantenimiento')">Ver todos →</button>
                    <a href="{{ url('/admin/mantenimientos/create') }}" class="btn-blue">Nuevo</a>
                </div>
            </div>
        </div>


        <!-- 4. CATALOGS -->
        <div class="section-block hidden-mobile">
            <div class="block-header">
                <div class="block-title-wrap">
                    <div class="block-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                    <div>
                        <h2 class="block-title text-primary-util">Catálogos del sistema</h2>
                        <p class="block-subtitle text-secondary-util">9 catálogos · {{ $totalRegistrosCatalogos ?? 0 }} registros en total</p>
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
                <div class="recent-label text-secondary-util"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> RECIENTES</div>
                <button class="recent-chip" @click="openCatalog('departamentos')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-brand-500"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg> Departamentos</button>
                <button class="recent-chip" @click="openCatalog('materiales')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-emerald-500"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg> Materiales</button>
                <button class="recent-chip" @click="openCatalog('usuarios')"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-indigo-500"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg> Usuarios</button>
            </div>

            <!-- G1: ORG -->
            <div class="catalog-group" x-show="matchesSearch('departamentos', 'áreas', 'usuarios')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-brand" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"></path></svg></div>
                        <div>
                            <span class="group-title-text text-primary-util">Organización</span>
                            <span class="group-title-sub text-secondary-util">· estructura institucional</span>
                        </div>
                    </div>
                    <div class="group-count text-secondary-util">3 catálogos</div>
                </div>
                
                <div class="grid-3">
                    <div class="cat-card" x-show="matchesSearch('departamentos')" @click="openCatalog('departamentos')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, var(--brand-500), var(--brand-600));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('departamentos')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Departamentos</h3>
                        <p class="cat-desc text-secondary-util">Áreas académicas y administrativas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number text-primary-util">{{ count($departamentosList ?? []) }}</div>
                                <div class="cat-num-label text-secondary-util">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview text-secondary-util">
                            @php $lastDep = collect($departamentosList ?? [])->last(); @endphp
                            Último: {{ $lastDep ? Str::limit($lastDep->nombre, 25) : 'Ninguno' }}
                        </div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('áreas')" @click="openCatalog('areas')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #22d3ee, var(--cyan-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('areas')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Áreas</h3>
                        <p class="cat-desc text-secondary-util">Espacios físicos y oficinas</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number text-primary-util">{{ count($areasList ?? []) }}</div>
                                <div class="cat-num-label text-secondary-util">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview text-secondary-util">
                            @php $lastArea = collect($areasList ?? [])->last(); @endphp
                            Último: {{ $lastArea ? Str::limit($lastArea->nombre, 25) : 'Ninguno' }}
                        </div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('usuarios')" @click="openCatalog('usuarios')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #818cf8, var(--indigo-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('usuarios')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Usuarios</h3>
                        <p class="cat-desc text-secondary-util">Personal y cuentas del sistema</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number text-primary-util">{{ count($usuariosList ?? []) }}</div>
                                <div class="cat-num-label text-secondary-util">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview text-secondary-util" style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="avatar-stack">
                                @foreach(collect($usuariosList ?? [])->take(3) as $u)
                                <div class="avatar-sm" style="background: var(--brand-500);">{{ substr($u->name, 0, 1) }}</div>
                                @endforeach
                            </div>
                            <span>3 recientes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- G2: INVENTARIO -->
            <div class="catalog-group" x-show="matchesSearch('materiales', 'marcas', 'tipos', 'unidades')">
                <div class="group-header">
                    <div class="group-title-wrap">
                        <div class="stat-icon bg-emerald" style="width: 32px; height: 32px; border-radius: 8px;"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        <div>
                            <span class="group-title-text text-primary-util">Inventario</span>
                            <span class="group-title-sub text-secondary-util">· configuración de activos</span>
                        </div>
                    </div>
                    <div class="group-count text-secondary-util">4 catálogos</div>
                </div>
                
                <div class="grid-4">
                    <div class="cat-card" x-show="matchesSearch('materiales')" @click="openCatalog('materiales')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #34d399, var(--emerald-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('materiales')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Materiales</h3>
                        <div class="cat-footer">
                            <span class="cat-number text-primary-util" style="font-size: 20px;">{{ count($materialesList ?? []) }}</span>
                            <span class="cat-num-label text-secondary-util">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('marcas')" @click="openCatalog('marcas')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #2dd4bf, var(--teal-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8l4 4-4 4M8 12h8"></path></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('marcas')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Marcas</h3>
                        <div class="cat-footer">
                            <span class="cat-number text-primary-util" style="font-size: 20px;">{{ count($marcasList ?? []) }}</span>
                            <span class="cat-num-label text-secondary-util">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('tipos')" @click="openCatalog('tipos')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #a78bfa, var(--violet-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('tipos')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Tipos de Mat.</h3>
                        <div class="cat-footer">
                            <span class="cat-number text-primary-util" style="font-size: 20px;">{{ count($tiposList ?? []) }}</span>
                            <span class="cat-num-label text-secondary-util">registros</span>
                        </div>
                    </div>

                    <div class="cat-card" x-show="matchesSearch('unidades')" @click="openCatalog('unidades')">
                        <div class="cat-card-header" style="margin-bottom: 8px;">
                            <div class="cat-icon cat-icon-sm" style="background: linear-gradient(135deg, #fb7185, var(--rose-500));"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('unidades')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Unidades</h3>
                        <div class="cat-footer">
                            <span class="cat-number text-primary-util" style="font-size: 20px;">{{ count($unidadesList ?? []) }}</span>
                            <span class="cat-num-label text-secondary-util">registros</span>
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
                            <span class="group-title-text text-primary-util">Contactos</span>
                            <span class="group-title-sub text-secondary-util">· personas y proveedores</span>
                        </div>
                    </div>
                    <div class="group-count text-secondary-util">2 catálogos</div>
                </div>
                
                <div class="grid-4" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="cat-card" x-show="matchesSearch('proveedores')" @click="openCatalog('proveedores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #c084fc, var(--purple-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('proveedores')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Proveedores</h3>
                        <p class="cat-desc text-secondary-util">Empresas y distribuidores</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number text-primary-util">{{ count($proveedoresList ?? []) }}</div>
                                <div class="cat-num-label text-secondary-util">registros</div>
                            </div>
                        </div>
                        <div class="hover-preview text-secondary-util">
                            @php $lastProv = collect($proveedoresList ?? [])->last(); @endphp
                            Último: {{ $lastProv ? Str::limit($lastProv->nombre_empresa, 25) : 'Ninguno' }}
                        </div>
                    </div>
                    
                    <div class="cat-card" x-show="matchesSearch('receptores')" @click="openCatalog('receptores')">
                        <div class="cat-card-header">
                            <div class="cat-icon" style="background: linear-gradient(135deg, #e879f9, var(--fuchsia-500));"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                            <button class="cat-add-btn" @click.stop="openCreateForm('receptores')"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                        </div>
                        <h3 class="cat-title text-primary-util">Receptores</h3>
                        <p class="cat-desc text-secondary-util">Personas que reciben asignaciones</p>
                        <div class="cat-footer">
                            <div>
                                <div class="cat-number text-primary-util">{{ count($receptoresList ?? []) }}</div>
                                <div class="cat-num-label text-secondary-util">registros</div>
                            </div>
                            <svg width="40" height="20" viewBox="0 0 40 20" fill="none" stroke="var(--fuchsia-500)" stroke-width="2"><polyline points="0,15 10,18 20,10 30,12 40,5"></polyline></svg>
                        </div>
                        <div class="hover-preview text-secondary-util" style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="avatar-stack">
                                @foreach(collect($receptoresList ?? [])->take(3) as $u)
                                <div class="avatar-sm" style="background: var(--fuchsia-500);">{{ substr($u->nombre, 0, 1) }}</div>
                                @endforeach
                            </div>
                            <span>activos este mes</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block-footer">
                <div class="text-secondary-util">Tip: presiona <span class="kbd">/</span> para buscar catálogos</div>
                <a href="{{ url('/admin') }}" class="link-blue">Ir a Configuración →</a>
            </div>
        </div>

        <!-- 6. ACTIVITY -->
        <div class="section-block">
            <div class="block-header" style="border-bottom: none; padding-bottom: 0;">
                <h2 class="block-title text-primary-util" style="font-size: 16px;">Actividad Reciente</h2>
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
                                'crear' => 'creó un registro en',
                                'editar' => 'editó un registro en',
                                'eliminar' => 'eliminó un registro en',
                                'autorizar' => 'autorizó un registro en',
                            ];
                            $accionTexto = $accionesMap[strtolower($act->event ?? '')] ?? (strtolower($act->event ?? '') . ' en');
                        @endphp
                        <p class="tl-text text-primary-util"><strong>{{ $act->causer?->name ?? 'Usuario' }}</strong> {{ $accionTexto }} <em>{{ class_basename($act->subject_type) }}</em></p>
                        <div class="tl-time text-muted-util">{{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}</div>
                    </div>
                </div>
                @empty
                <p class="text-muted-util" style="text-align: center;">Sin actividad reciente.</p>
                @endforelse
            </div>
            <div class="block-footer" style="background: white; border-top: 1px solid var(--slate-100); justify-content: center;">
                <a href="#" class="link-blue">Ver bitácora completa →</a>
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
                                    <button x-show="mode === 'form'" type="button" class="btn-back" @click="mode = 'list'">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                    </button>
                                    <div>
                                        <h2 id="slide-over-title" class="slide-title text-primary-util" x-text="mode === 'form' ? (form.id ? 'Editar Registro' : 'Nuevo Registro') : getCatalogTitle()">Catálogo</h2>
                                        <p class="slide-subtitle text-secondary-util" x-text="mode === 'form' ? 'Completa los datos' : 'Gestión completa del catálogo'"></p>
                                    </div>
                                </div>
                                <div class="slide-actions">
                                    <template x-if="mode === 'list' && canCreateInline()">
                                        <button @click="openCreateForm(activeCatalog)" class="btn-blue">Nuevo</button>
                                    </template>
                                    <template x-if="mode === 'list' && canCreateExternal()">
                                        <a :href="getExternalCreateUrl()" class="btn-blue" style="text-decoration:none;">Nuevo</a>
                                    </template>
                                    <button type="button" class="btn-close" @click="closeCatalog()">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="slide-body">
                                
                                <!-- TABLE VIEW -->
                                <div x-show="mode === 'list'" class="slide-table-card">
                                    <div class="table-toolbar">
                                        <div class="catalog-search" style="width: 300px;">
                                            <div class="search-input-wrap" style="width: 100%;">
                                                <svg class="search-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                <input type="text" class="search-input" style="width: 100%; padding: 8px 12px 8px 36px;" x-model="tableSearch" @input="page = 1" placeholder="Buscar registros...">
                                            </div>
                                        </div>
                                        <div class="table-results text-secondary-util"><span x-text="getFilteredData().length"></span> resultados</div>
                                    </div>

                                    <div style="overflow-x: auto;">
                                        <table class="st-wrap">
                                            <thead>
                                                <tr x-html="getTableHeaders()"></tr>
                                            </thead>
                                            <tbody x-html="getTableRows()"></tbody>
                                        </table>
                                        <div x-show="!getTableRows()" class="text-muted-util" style="padding: 40px; text-align: center;">
                                            Sin registros encontrados.
                                        </div>
                                    </div>
                                    <!-- Pagination -->
                                    <div class="table-pagination" x-show="totalPages() > 1" style="padding: 12px 24px; border-top: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; background: white;">
                                        <button type="button" class="btn-text" @click="if(page > 1) page--" :disabled="page === 1" :style="page === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''">Anterior</button>
                                        <span class="text-secondary-util" style="font-size: 13px;">Página <strong class="text-primary-util" x-text="page"></strong> de <strong class="text-primary-util" x-text="totalPages()"></strong></span>
                                        <button type="button" class="btn-text" @click="if(page < totalPages()) page++" :disabled="page === totalPages()" :style="page === totalPages() ? 'opacity: 0.5; cursor: not-allowed;' : ''">Siguiente</button>
                                    </div>
                                </div>

                                <!-- FORM VIEW -->
                                <div x-show="mode === 'form'" style="display: none;">
                                    <div class="slide-form-card">
                                        <h3 class="form-title text-primary-util">Datos del registro</h3>
                                        
                                        <!-- Dynamic Fields -->
                                        <div id="dynamic-form-fields" x-html="getFormFields()"></div>
                                        
                                        <div class="form-group" style="margin-top: 24px;">
                                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                <input type="checkbox" checked style="accent-color: var(--brand-600); width: 16px; height: 16px;">
                                                <span class="text-primary-util" style="font-size: 14px;">Registro activo</span>
                                            </label>
                                        </div>

                                        <div class="form-actions">
                                            <button type="button" class="btn-text" @click="mode = 'list'">Cancelar</button>
                                            <button type="button" class="btn-blue" @click="submitForm()" :disabled="isSubmitting" x-text="isSubmitting ? 'Guardando...' : (form.id ? 'Guardar cambios' : 'Crear registro')"></button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Footer -->
                            <div class="slide-footer">
                                <div><a :href="getExternalListUrl()" class="link-blue">Vista avanzada →</a></div>
                                <div class="text-secondary-util" style="font-size: 13px;">por página: <strong class="text-primary-util">10</strong> <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;"><polyline points="6 9 12 15 18 9"></polyline></svg></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast Notification -->
        <div class="toast" x-show="toast.show" x-transition.duration.300ms style="display: none;">
            <svg class="toast-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span x-text="toast.message"></span>
        </div>

        <!-- Confirm Modal -->
        <template x-teleport="body">
            <div x-show="confirmModal.show" style="display: none;" class="sigem-confirm-overlay" x-transition.opacity.duration.300ms>
                <div @click.away="closeConfirm()" class="sigem-confirm-modal" x-show="confirmModal.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                     <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                         <div :style="'display: flex; align-items: center; justify-content: center; width: 3.5rem; height: 3.5rem; border-radius: 9999px; background-color: ' + getConfirmColor(0.1) + '; color: ' + getConfirmColor(1) + ';'">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                         </div>
                     </div>
                     <h3 class="text-primary-util" style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;" x-text="confirmModal.title"></h3>
                     <p class="text-secondary-util" style="font-size: 0.875rem; margin-bottom: 1.5rem;" x-text="confirmModal.message"></p>
                     <div style="display: flex; justify-content: center; gap: 0.75rem;">
                         <button type="button" @click="closeConfirm()" class="sigem-confirm-cancel">
                             Cancelar
                         </button>
                         <button type="button" @click="executeConfirm()" :style="'padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; color: white; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; background-color: ' + getConfirmColor(1) + '; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);'" onmouseover="this.style.filter='brightness(0.9)'" onmouseout="this.style.filter='none'">
                             Confirmar
                         </button>
                     </div>
                </div>
            </div>
        </template>
        
        <style>
            .sigem-confirm-overlay { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); }
            .sigem-confirm-modal { background: white; border-radius: 0.75rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 24rem; width: 100%; margin: 1rem; padding: 1.5rem; text-align: center; border: 1px solid var(--slate-200); }
            .dark .sigem-confirm-modal { background: #18181b; border-color: #27272a; }
            .sigem-confirm-cancel { padding: 0.5rem 1rem; border: 1px solid var(--slate-300); border-radius: 0.5rem; background: white; color: var(--slate-700); font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
            .sigem-confirm-cancel:hover { background: var(--slate-50); }
            .dark .sigem-confirm-cancel { background: #27272a; border-color: #3f3f46; color: #e4e4e7; }
            .dark .sigem-confirm-cancel:hover { background: #3f3f46; }
        </style>

        <!-- Inject data to JS -->
        <script>
            const registerSigemDashboard = () => {
                Alpine.data('sigemDashboard', () => ({
                    getGreeting() {
                        const hour = new Date().getHours();
                        if (hour >= 6 && hour < 12) return 'Buenos días';
                        if (hour >= 12 && hour < 18) return 'Buenas tardes';
                        return 'Buenas noches';
                    },
                    searchQuery: '',
                    activeCatalog: null,
                    mode: 'list', // 'list' or 'form'
                    tableSearch: '',
                    page: 1,
                    pageSize: 10,
                    isSubmitting: false,
                    form: {},
                    toast: { show: false, message: '' },

                    confirmModal: { show: false, title: '', message: '', color: 'blue', onConfirm: null },
                    
                    openConfirm(title, message, color, callback) {
                        this.confirmModal.title = title;
                        this.confirmModal.message = message;
                        this.confirmModal.color = color;
                        this.confirmModal.onConfirm = callback;
                        this.confirmModal.show = true;
                    },
                    closeConfirm() {
                        this.confirmModal.show = false;
                        setTimeout(() => { this.confirmModal.onConfirm = null; }, 300);
                    },
                    executeConfirm() {
                        if (this.confirmModal.onConfirm) this.confirmModal.onConfirm();
                        this.closeConfirm();
                    },
                    getConfirmColor(alpha) {
                        const colors = { blue: `rgba(59, 130, 246, ${alpha})`, green: `rgba(16, 185, 129, ${alpha})`, red: `rgba(239, 68, 68, ${alpha})` };
                        return colors[this.confirmModal.color] || colors.blue;
                    },
                    
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

                    showToast(msg) {
                        this.toast.message = msg;
                        this.toast.show = true;
                        setTimeout(() => { this.toast.show = false; }, 2500);
                    },

                    matchesSearch(...terms) {
                        if (this.searchQuery === '') return true;
                        const q = this.searchQuery.toLowerCase();
                        return terms.some(t => t.toLowerCase().includes(q));
                    },

                    openCatalog(id) {
                        this.activeCatalog = id;
                        this.mode = 'list';
                        this.tableSearch = '';
                        this.page = 1;
                        this.form = {};
                    },

                    closeCatalog() {
                        this.activeCatalog = null;
                    },

                    getCatalogTitle() {
                        const titles = {
                            departamentos: 'Departamentos', areas: 'Áreas', usuarios: 'Usuarios',
                            materiales: 'Materiales', marcas: 'Marcas', tipos: 'Tipos de material',
                            unidades: 'Unidades de medida', proveedores: 'Proveedores', receptores: 'Receptores',
                            'modulo-inventario': 'Inventario general', 'modulo-solicitudes': 'Solicitudes', 'modulo-mantenimiento': 'Mantenimientos'
                        };
                        return titles[this.activeCatalog] || 'Catálogo';
                    },

                    canCreateInline() {
                        return ['departamentos', 'areas', 'marcas', 'tipos', 'unidades'].includes(this.activeCatalog);
                    },

                    canCreateExternal() {
                        return ['usuarios', 'materiales', 'proveedores', 'receptores', 'modulo-inventario', 'modulo-solicitudes', 'modulo-mantenimiento'].includes(this.activeCatalog);
                    },

                    getExternalCreateUrl() {
                        const map = {
                            usuarios: '/admin/users/create', materiales: '/admin/materials/create',
                            proveedores: '/admin/proveedors/create', receptores: '/admin/receptors/create',
                            'modulo-inventario': '/admin/inventarios/create', 'modulo-solicitudes': '/admin/solicituds/create', 'modulo-mantenimiento': '/admin/mantenimientos/create'
                        };
                        return map[this.activeCatalog] || '#';
                    },
                    
                    getExternalListUrl() {
                        const map = {
                            departamentos: '/admin/departamentos', areas: '/admin/areas', usuarios: '/admin/users',
                            materiales: '/admin/materials', marcas: '/admin/marca-materials', tipos: '/admin/tipo-materials',
                            unidades: '/admin/unidad-medidas', proveedores: '/admin/proveedors', receptores: '/admin/receptors',
                            'modulo-inventario': '/admin/inventarios', 'modulo-solicitudes': '/admin/solicituds', 'modulo-mantenimiento': '/admin/mantenimientos'
                        };
                        return map[this.activeCatalog] || '#';
                    },

                    getTableHeaders() {
                        const headers = {
                            departamentos: ['Nombre', 'Acciones'],
                            areas: ['Nombre', 'Departamento', 'Estado', 'Acciones'],
                            usuarios: ['Nombre', 'Email', 'Rol', 'Acciones'],
                            materiales: ['Nombre', 'Tipo', 'Unidad', 'Marca', 'Acciones'],
                            marcas: ['Nombre', 'Materiales', 'Acciones'],
                            tipos: ['Nombre', 'Acciones'],
                            unidades: ['Nombre', 'Acciones'],
                            proveedores: ['Empresa', 'Contacto', 'RFC', 'Acciones'],
                            receptores: ['Nombre', 'Área', 'Departamento', 'Acciones'],
                            'modulo-inventario': ['N/S', 'Material', 'Estado', 'Acciones'],
                            'modulo-solicitudes': ['Folio', 'Usuario', 'Estado', 'Acciones'],
                            'modulo-mantenimiento': ['Activo', 'Técnico', 'Estado', 'Acciones']
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
                            let pk = Object.keys(item).find(k => k.startsWith('id_') || k === 'id');
                            let id = item[pk];
                            
                            let editBtn = '';
                            let delBtn = '';
                            if (this.canCreateInline()) {
                                const safeItem = JSON.stringify(item).replace(/'/g, "\\'").replace(/"/g, '&quot;');
                                editBtn = `<button class="btn-icon" onclick="document.querySelector('.sigem-professional')._x_dataStack[0].openEditForm(${safeItem})"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>`;
                                delBtn = `<button class="btn-icon del" onclick="document.querySelector('.sigem-professional')._x_dataStack[0].deleteInline(${id})"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>`;
                            } else {
                                let prefix = this.getExternalListUrl();
                                editBtn = `<a href="${prefix}/${id}/edit" class="btn-icon" style="display:inline-block"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>`;
                            }

                            let actions = `<td style="white-space: nowrap;">${editBtn} ${delBtn}</td>`;

                            if (this.activeCatalog === 'departamentos') {
                                cols = `<td>${item.nombre}</td>${actions}`;
                            } else if (this.activeCatalog === 'areas') {
                                cols = `<td>${item.nombre}</td><td>${item.departamento?.nombre || ''}</td><td><span class="badge badge-green">Activo</span></td>${actions}`;
                            } else if (this.activeCatalog === 'usuarios') {
                                let roles = item.tipo_usuario || '';
                                cols = `<td>${item.name}</td><td>${item.email}</td><td>${roles}</td>${actions}`;
                            } else if (this.activeCatalog === 'materiales') {
                                cols = `<td>${item.nombre}</td><td>${item.tipo?.nombre || ''}</td><td>${item.unidad?.nombre || ''}</td><td>${item.marca?.nombre || ''}</td>${actions}`;
                            } else if (this.activeCatalog === 'marcas') {
                                cols = `<td>${item.nombre}</td><td>${item.materiales_count || 0}</td>${actions}`;
                            } else if (this.activeCatalog === 'tipos') {
                                cols = `<td>${item.nombre}</td>${actions}`;
                            } else if (this.activeCatalog === 'unidades') {
                                cols = `<td>${item.nombre}</td>${actions}`;
                            } else if (this.activeCatalog === 'proveedores') {
                                cols = `<td>${item.nombre_empresa}</td><td>${item.contacto_nombre || ''}</td><td class="mono-text">${item.rfc || ''}</td>${actions}`;
                            } else if (this.activeCatalog === 'receptores') {
                                cols = `<td>${item.nombre} ${item.apellido_paterno || ''}</td><td>${item.area?.nombre || ''}</td><td>${item.area?.departamento?.nombre || ''}</td>${actions}`;
                            } else if (this.activeCatalog === 'modulo-inventario') {
                                let badge = ['Disponible'].includes(item.estado) ? 'badge-green' : (item.estado === 'Asignado' ? 'badge-blue' : 'badge-amber');
                                cols = `<td class="mono-text">${item.num_serie}</td><td>${item.material?.nombre || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>${actions}`;
                            } else if (this.activeCatalog === 'modulo-solicitudes') {
                                let badge = item.estado === 'Autorizado' ? 'badge-green' : (item.estado === 'Pendiente' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">SOL-${String(item.id_solicitud).padStart(4,'0')}</td><td>${item.usuario?.name || ''}</td><td><span class="badge ${badge}">${item.estado}</span></td>${actions}`;
                            } else if (this.activeCatalog === 'modulo-mantenimiento') {
                                let badge = item.estado === 'Completado' ? 'badge-green' : (item.estado === 'En proceso' ? 'badge-amber' : 'badge-red');
                                cols = `<td class="mono-text">${item.inventario?.num_serie || ''}</td><td>${item.nombre_tecnico || 'N/A'}</td><td><span class="badge ${badge}">${item.estado}</span></td>${actions}`;
                            }
                            
                            return `<tr>${cols}</tr>`;
                        }).join('');
                    },

                    openCreateForm(catId = null) {
                        if (catId) this.activeCatalog = catId;
                        if (this.canCreateExternal()) {
                            window.location.href = this.getExternalCreateUrl();
                            return;
                        }
                        this.form = {};
                        this.mode = 'form';
                    },

                    openEditForm(item) {
                        this.form = { ...item };
                        let pk = Object.keys(item).find(k => k.startsWith('id_') || k === 'id');
                        this.form.id = item[pk];
                        this.mode = 'form';
                    },

                    getFormFields() {
                        if (['departamentos', 'marcas', 'tipos', 'unidades'].includes(this.activeCatalog)) {
                            return `
                                <div class="form-group">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-input" x-model="form.nombre" placeholder="Nombre completo">
                                </div>
                            `;
                        }
                        if (this.activeCatalog === 'areas') {
                            let opts = this.data.departamentos.map(d => `<option value="${d.id_departamento}">${d.nombre}</option>`).join('');
                            return `
                                <div class="form-group">
                                    <label class="form-label">Nombre del Área</label>
                                    <input type="text" class="form-input" x-model="form.nombre" placeholder="Ej: Sala de juntas">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Departamento</label>
                                    <select class="form-input" x-model="form.id_departamento" style="appearance:none;">
                                        <option value="">Seleccione un departamento...</option>
                                        ${opts}
                                    </select>
                                </div>
                            `;
                        }
                        return '';
                    },

                    async submitForm() {
                        this.isSubmitting = true;
                        try {
                            if (this.activeCatalog === 'departamentos') {
                                await @this.saveDepartamento(this.form.id || null, this.form.nombre);
                            } else if (this.activeCatalog === 'areas') {
                                await @this.saveArea(this.form.id || null, this.form.nombre, this.form.id_departamento);
                            } else if (this.activeCatalog === 'marcas') {
                                await @this.saveMarca(this.form.id || null, this.form.nombre);
                            } else if (this.activeCatalog === 'tipos') {
                                await @this.saveTipo(this.form.id || null, this.form.nombre);
                            } else if (this.activeCatalog === 'unidades') {
                                await @this.saveUnidad(this.form.id || null, this.form.nombre);
                            }
                            this.showToast('Registro guardado exitosamente');
                            setTimeout(() => { window.location.reload(); }, 1000);
                        } catch (e) {
                            alert('Error al guardar. Verifica la consola.');
                            console.error(e);
                            this.isSubmitting = false;
                        }
                    },

                    deleteInline(id) {
                        this.openConfirm('Eliminar registro', '¿Estás seguro de eliminar este registro de forma permanente?', 'red', async () => {
                            try {
                                if (this.activeCatalog === 'departamentos') await @this.deleteDepartamento(id);
                                else if (this.activeCatalog === 'areas') await @this.deleteArea(id);
                                else if (this.activeCatalog === 'marcas') await @this.deleteMarca(id);
                                else if (this.activeCatalog === 'tipos') await @this.deleteTipo(id);
                                else if (this.activeCatalog === 'unidades') await @this.deleteUnidad(id);
                                
                                this.showToast('Registro eliminado');
                                setTimeout(() => { window.location.reload(); }, 1000);
                            } catch (e) {
                                alert('Error al eliminar');
                                console.error(e);
                            }
                        });
                    }
                }));
            };

            if (window.Alpine) {
                registerSigemDashboard();
            } else {
                document.addEventListener('alpine:init', registerSigemDashboard);
            }
        </script>
    </div>
</x-filament-panels::page>
</div>