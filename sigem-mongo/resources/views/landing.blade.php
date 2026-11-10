<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEM — TecNM Veracruz | Gestión de equipos y materiales</title>
    <link rel="icon" href="{{ asset('images/sigem-logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else if (theme === 'system' || !theme) {
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    }
                }
            } catch (e) {}
        })();
    </script>
    <style>
        :root {
            --bg: #f0f4f8;
            --sidebar-bg: #0b1d3a;
            --card-bg: #ffffff;
            --accent: #1b65d4;
            --accent-light: #e8f0fe;
            --accent-dark: #0e4bad;
            --success: #0f9d58;
            --success-light: #e6f7ed;
            --warning: #f4a623;
            --warning-light: #fef7e6;
            --danger: #d93025;
            --danger-light: #fce8e6;
            --text-primary: #1a1a2e;
            --text-secondary: #5f6b7a;
            --text-muted: #8b95a5;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Modo Oscuro */
        .dark {
            --bg: #0f172a;
            --sidebar-bg: #020617;
            --card-bg: #1e293b;
            --accent-light: rgba(59, 130, 246, 0.2);
            --success-light: rgba(16, 185, 129, 0.2);
            --warning-light: rgba(245, 158, 11, 0.2);
            --danger-light: rgba(239, 68, 68, 0.2);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
            --border-light: #1e293b;
            --shadow: 0 1px 3px rgba(0,0,0,0.3), 0 1px 2px rgba(0,0,0,0.2);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.5);
        }

        .dark .hero .btn-secondary {
            background: #1e293b;
            color: #f8fafc;
            border-color: #334155;
        }

        .dark .hero .btn-secondary:hover {
            background: #334155;
        }

        .dark .header-logos img, .dark .footer img {
            background: rgba(255, 255, 255, 0.85);
            padding: 4px 8px;
            border-radius: 6px;
            backdrop-filter: blur(4px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('{{ asset("images/fondo.jpg") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            z-index: -2;
        }
        
        .bg-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 29, 58, 0.75);
            z-index: -1;
        }

        /* ===== HEADER ===== */
        .header {
            background: var(--sidebar-bg);
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent), #4f9cf7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: #fff;
            letter-spacing: -0.5px;
            box-shadow: 0 4px 10px rgba(27,101,212,0.4);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 13px;
            color: #8fa8cc;
            margin-top: 2px;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 26px;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(27,101,212,0.3);
        }

        .btn-primary:hover {
            background: var(--accent-dark);
            box-shadow: 0 6px 16px rgba(27,101,212,0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .hero .btn-secondary {
            background: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .hero .btn-secondary:hover {
            background: #f8fafc;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            padding: 90px 40px 70px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-badge {
            background: var(--accent-light);
            color: var(--accent);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
            display: inline-block;
            animation: slideUp 0.6s ease forwards;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -1.5px;
            line-height: 1.15;
            margin-bottom: 22px;
            opacity: 0;
            animation: slideUp 0.6s ease 0.1s forwards;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .hero-sub {
            font-size: 19px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 700px;
            opacity: 0;
            animation: slideUp 0.6s ease 0.2s forwards;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            opacity: 0;
            animation: slideUp 0.6s ease 0.3s forwards;
        }

        /* ===== INFO CARDS (MODULES) ===== */
        .modules-section {
            padding: 0 40px 90px;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 26px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 26px;
            position: relative;
            overflow: hidden;
            transition: all 0.25s ease;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            opacity: 0;
            animation: slideUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.2s; }
        .stat-card:nth-child(2) { animation-delay: 0.3s; }
        .stat-card:nth-child(3) { animation-delay: 0.4s; }
        .stat-card:nth-child(4) { animation-delay: 0.5s; }

        .stat-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: transparent;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.blue::before { background: linear-gradient(90deg, var(--accent), #4f9cf7); }
        .stat-card.green::before { background: linear-gradient(90deg, var(--success), #34d399); }
        .stat-card.orange::before { background: linear-gradient(90deg, var(--warning), #fbbf24); }
        .stat-card.red::before { background: linear-gradient(90deg, var(--danger), #f87171); }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }

        .stat-icon svg {
            width: 26px;
            height: 26px;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .stat-card.blue .stat-icon { background: var(--accent-light); }
        .stat-card.blue .stat-icon svg { stroke: var(--accent); }
        .stat-card.green .stat-icon { background: var(--success-light); }
        .stat-card.green .stat-icon svg { stroke: var(--success); }
        .stat-card.orange .stat-icon { background: var(--warning-light); }
        .stat-card.orange .stat-icon svg { stroke: var(--warning); }
        .stat-card.red .stat-icon { background: var(--danger-light); }
        .stat-card.red .stat-icon svg { stroke: var(--danger); }

        .stat-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            letter-spacing: -0.3px;
        }

        .stat-desc {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
            flex: 1;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--sidebar-bg);
            color: #8fa8cc;
            padding: 30px 40px;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 14px;
            font-weight: 500;
        }

        .footer p {
            opacity: 0.9;
        }

        /* ===== ANIMATION ===== */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .modules-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .hero-title {
                font-size: 38px;
            }
        }

        @media (max-width: 640px) {
            .modules-grid {
                grid-template-columns: 1fr;
            }
            .header {
                padding: 16px 20px;
                flex-direction: column;
                gap: 20px;
            }
            .header-brand {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            .header-logos {
                margin-left: 0 !important;
                border-left: none !important;
                padding-left: 0 !important;
                margin-top: 10px;
                border-top: 1px solid rgba(255,255,255,0.2);
                padding-top: 16px;
                justify-content: center;
                width: 100%;
            }
            .header-actions {
                width: 100%;
            }
            .header-actions .btn {
                width: 100%;
                justify-content: center;
                min-height: 44px;
            }
            .hero {
                padding: 40px 20px 40px;
                justify-content: center;
            }
            .hero-title {
                font-size: 32px;
                line-height: 1.2;
                margin-bottom: 16px;
            }
            .hero-sub {
                font-size: 16px;
                margin-bottom: 24px;
            }
            .hero-actions {
                display: none; /* Oculto en móvil a favor del sticky */
            }
            .mobile-login-bar {
                display: block !important;
                position: sticky;
                bottom: 0;
                background: rgba(11, 29, 58, 0.95);
                backdrop-filter: blur(10px);
                padding: 16px 20px;
                z-index: 100;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
            .mobile-login-bar .btn {
                width: 100%;
                justify-content: center;
                min-height: 48px;
            }
            .modules-section {
                padding: 0 20px 60px;
            }
            .footer {
                padding: 30px 20px;
            }
            .footer > div {
                flex-direction: column;
                gap: 12px !important;
            }
            .footer img {
                height: 32px !important;
            }
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-brand">
            <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM" height="40">
            <div class="brand-text">
                <div class="brand-title">SIGEM</div>
                <div class="brand-sub">TecNM Veracruz</div>
            </div>
            <div class="header-logos" style="display: flex; gap: 12px; margin-left: 20px; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px;">
                <img src="{{ asset('images/tecnm-logo.png') }}" alt="TecNM" style="height: 40px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                <img src="{{ asset('images/itv-logo.png') }}" alt="ITV" style="height: 40px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Iniciar sesión
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="hero">
        <div class="hero-badge">Tecnológico Nacional de México</div>
        <h1 class="hero-title">Gestión de equipos y materiales</h1>
        <p class="hero-sub">Sistema integral para el control de inventario, gestión de préstamos, rentas y seguimiento de mantenimiento para el equipamiento del TecNM Campus Veracruz.</p>
        <div class="hero-actions">
            <a href="{{ url('/admin/login') }}" class="btn btn-primary" style="padding: 16px 36px; font-size: 17px;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Iniciar sesión
            </a>
        </div>
    </main>

    <!-- Modules / Info Cards -->
    <section class="modules-section">
        <div class="modules-grid">
            <!-- Card 1: Inventario -->
            <div class="stat-card blue">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="stat-title">Inventario activo</div>
                <div class="stat-desc">Registro detallado y seguimiento en tiempo real del estatus, ubicación y asignación de todo el equipamiento institucional.</div>
            </div>

            <!-- Card 2: Solicitudes -->
            <div class="stat-card orange">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="stat-title">Solicitudes y préstamos</div>
                <div class="stat-desc">Control ágil de asignaciones temporales, permanentes y rentas con flujos de autorización y fechas de devolución.</div>
            </div>

            <!-- Card 3: Mantenimiento -->
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <div class="stat-title">Mantenimiento</div>
                <div class="stat-desc">Gestión de servicios preventivos y correctivos, reporte de fallas por alumnos de servicio social y seguimiento de reparaciones.</div>
            </div>

            <!-- Card 4: Bitácora -->
            <div class="stat-card red">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-title">Bitácora del sistema</div>
                <div class="stat-desc">Auditoría completa de movimientos, historial de cambios de estado y supervisión de acciones con validación del administrador.</div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div style="display: flex; justify-content: center; align-items: center; gap: 20px; margin-bottom: 20px;">
            <span style="color: rgba(255,255,255,0.7); font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Instituciones:</span>
            <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM" style="height: 38px; opacity: 0.95; object-fit: contain;">
            <img src="{{ asset('images/tecnm-logo.png') }}" alt="TecNM" style="height: 38px; opacity: 0.95; object-fit: contain;">
            <img src="{{ asset('images/itv-logo.png') }}" alt="ITV" style="height: 38px; opacity: 0.95; object-fit: contain;">
        </div>
        <p>&copy; {{ date('Y') }} SIGEM — Tecnológico Nacional de México Campus Veracruz. Todos los derechos reservados.</p>
    </footer>

    <!-- Mobile Sticky Login Bar -->
    <div class="mobile-login-bar" style="display: none;">
        <a href="{{ url('/admin/login') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Iniciar sesión
        </a>
    </div>

</body>
</html>
