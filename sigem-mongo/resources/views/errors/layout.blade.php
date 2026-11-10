<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SIGEM TecNM Veracruz</title>
    <link rel="icon" href="{{ asset('images/sigem-favicon.svg?v=2') }}" type="image/svg+xml">
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
            --accent: #1b65d4;
            --accent-dark: #0e4bad;
            --text-primary: #1a1a2e;
            --text-secondary: #5f6b7a;
            --card-bg: #ffffff;
            --border: #e2e8f0;
        }

        /* Modo Oscuro */
        .dark {
            --bg: #0d1117;
            --text-primary: #e2e8f0;
            --text-secondary: #8b949e;
            --card-bg: rgba(22, 27, 34, 0.85);
            --border: #30363d;
            --accent: #3b82f6;
            --accent-dark: #60a5fa;
        }

        .dark .logo {
            background: rgba(255, 255, 255, 0.85);
            padding: 4px 8px;
            border-radius: 6px;
            backdrop-filter: blur(4px);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
            background-color: var(--bg);
        }

        .bg-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('{{ asset("images/fondo.jpg") }}');
            background-size: cover;
            background-position: center;
            z-index: 1;
        }
        .bg-gradient {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(27,101,212,0.85) 0%, rgba(11,29,58,0.95) 100%);
            z-index: 2;
        }
        .dark .bg-gradient {
            background: linear-gradient(135deg, rgba(13,17,23,0.9) 0%, rgba(22,27,34,0.98) 100%);
        }

        .error-container {
            position: relative;
            z-index: 10;
            background-color: var(--card-bg);
            padding: 3.5rem 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 480px;
            width: 90%;
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            height: 48px;
            margin-bottom: 1.5rem;
        }

        .error-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 7rem;
            font-weight: 700;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 12px rgba(27,101,212,0.15);
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .error-message {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: var(--accent);
            color: white;
            text-decoration: none;
            padding: 0.85rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(27,101,212,0.2), 0 2px 4px -1px rgba(27,101,212,0.1);
        }

        .btn:hover {
            background-color: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(27,101,212,0.3), 0 4px 6px -2px rgba(27,101,212,0.2);
        }

        @media (max-width: 640px) {
            .error-container { padding: 2.5rem 1.5rem; }
            .error-code { font-size: 5rem; }
            .error-title { font-size: 1.5rem; }
            .error-message { font-size: 1rem; margin-bottom: 2rem; }
            .btn { width: 100%; min-height: 44px; display: flex; align-items: center; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="bg-gradient"></div>

    <div class="error-container">
        <img src="{{ asset('images/sigem-logo.svg') }}" alt="SIGEM Logo" class="logo">
        
        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('message')</h1>
        <p class="error-message">@yield('submessage')</p>
        
        @yield('action')
    </div>
</body>
</html>
