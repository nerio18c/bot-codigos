<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Codes - Nerio18pe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-0: #0b0d10;
            --bg-1: #111621;
            --bg-2: #0f1116;
            --ink-0: #f5f7fb;
            --ink-1: #c9d2e1;
            --ink-2: #93a0b5;
            --accent: #2dd4bf;
            --accent-2: #f97316;
            --card: #151a24;
            --stroke: rgba(255,255,255,0.08);
            --glow: 0 24px 60px rgba(0,0,0,0.55);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Sora", system-ui, sans-serif;
            color: var(--ink-0);
            background: radial-gradient(1200px 600px at 80% -10%, #1a2233 0%, transparent 60%),
                        radial-gradient(900px 500px at 10% 10%, #1b1f2e 0%, transparent 60%),
                        linear-gradient(160deg, var(--bg-0), var(--bg-1) 45%, var(--bg-2));
            min-height: 100vh;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .brand-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0ea5e9, #22d3ee);
            display: grid;
            place-items: center;
            font-weight: 700;
            color: #081018;
            box-shadow: var(--glow);
        }

        .nav {
            display: flex;
            gap: 18px;
            color: var(--ink-2);
            font-size: 14px;
        }

        .nav span {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.04);
        }

        main {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(260px, 320px) minmax(280px, 520px);
            gap: 28px;
            padding: 10px 32px 40px;
            align-items: center;
            justify-content: center;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--stroke);
            border-radius: 18px;
            padding: 28px;
            box-shadow: var(--glow);
            position: relative;
            overflow: hidden;
        }

        .panel::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(250px 120px at 80% 0%, rgba(45,212,191,0.14), transparent 60%);
            pointer-events: none;
        }

        .title {
            font-family: "Space Grotesk", sans-serif;
            font-size: 26px;
            margin: 0 0 10px;
        }

        .subtitle {
            color: var(--ink-2);
            margin: 0 0 24px;
            line-height: 1.4;
            font-size: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 18px;
        }

        label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ink-2);
        }

        input[type="email"] {
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.08);
            background: #0e121a;
            color: var(--ink-0);
            font-size: 15px;
            outline: none;
        }

        input[type="email"]:focus {
            border-color: rgba(45,212,191,0.6);
            box-shadow: 0 0 0 3px rgba(45,212,191,0.15);
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            border: none;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #22d3ee);
            color: #07131b;
            box-shadow: 0 12px 24px rgba(14,165,233,0.25);
        }

        .btn-primary:hover { transform: translateY(-1px); }

        .btn-ghost {
            background: transparent;
            color: var(--ink-1);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .result {
            margin-top: 22px;
            padding: 16px;
            border-radius: 14px;
            background: rgba(0,0,0,0.25);
            border: 1px dashed rgba(255,255,255,0.08);
            display: none;
        }

        .result.show { display: block; animation: rise 0.4s ease; }

        .pin {
            font-family: "Space Grotesk", sans-serif;
            font-size: 38px;
            letter-spacing: 6px;
            margin: 10px 0;
        }

        .meta {
            color: var(--ink-2);
            font-size: 12px;
        }

        .platforms {
            display: grid;
            gap: 14px;
        }

        .card {
            padding: 18px;
            border-radius: 16px;
            border: 1px solid var(--stroke);
            background: rgba(15,18,24,0.8);
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 14px;
            align-items: center;
            cursor: pointer;
            transition: border 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
        }

        .card:hover { transform: translateY(-2px); }

        .card.active {
            border-color: rgba(45,212,191,0.7);
            box-shadow: 0 16px 32px rgba(0,0,0,0.35);
        }

        .icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 18px;
        }

        .icon.netflix { background: linear-gradient(135deg, #b91c1c, #ef4444); }
        .icon.prime { background: linear-gradient(135deg, #2563eb, #38bdf8); }
        .icon.disney { background: linear-gradient(135deg, #6366f1, #0ea5e9); }

        .card-title { font-weight: 600; margin-bottom: 4px; }
        .card-sub { color: var(--ink-2); font-size: 13px; }

        footer {
            padding: 20px 32px 28px;
            color: var(--ink-2);
            font-size: 12px;
            text-align: center;
        }

        @keyframes rise {
            from { transform: translateY(8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 920px) {
            main {
                grid-template-columns: 1fr;
                padding: 12px 20px 32px;
            }

            header { padding: 20px; flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header>
            <div class="brand">
                <div class="brand-badge">SC</div>
                <div>Streaming Codes</div>
            </div>
            <div class="nav">
                <span>Inicio</span>
                <span>Streaming</span>
                <span>Soporte</span>
            </div>
        </header>

        <main>
            <section class="platforms" aria-label="Plataformas">
                <div class="card active" data-platform="netflix">
                    <div class="icon netflix">N</div>
                    <div>
                        <div class="card-title">Netflix</div>
                        <div class="card-sub">Recupera tu codigo de acceso temporal.</div>
                    </div>
                </div>
                <div class="card" data-platform="prime">
                    <div class="icon prime">P</div>
                    <div>
                        <div class="card-title">Prime Video</div>
                        <div class="card-sub">Consulta intentos y codigos recientes.</div>
                    </div>
                </div>
                <div class="card" data-platform="disney">
                    <div class="icon disney">D</div>
                    <div>
                        <div class="card-title">Disney+</div>
                        <div class="card-sub">Acceso unico con verificacion rapida.</div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h1 class="title" id="panel-title">Netflix - Consulta de Codigo</h1>
                <p class="subtitle" id="panel-sub">Ingresa el correo asociado para recibir tu PIN de Netflix.</p>

                <div class="field">
                    <label for="correo">Correo electronico</label>
                    <input type="email" id="correo" placeholder="correo@tudominio.com" autocomplete="email">
                </div>

                <input type="hidden" id="plataforma" value="netflix">

                <div class="actions">
                    <button class="btn btn-primary" id="btn-buscar" onclick="consultar()">Obtener codigo</button>
                    <button class="btn btn-ghost" onclick="limpiar()">Limpiar</button>
                </div>

                <div class="result" id="resultado">
                    <div class="meta" id="plataforma-info">Plataforma: Netflix</div>
                    <div class="pin" id="pin-display">----</div>
                    <div class="meta" id="tiempo-info"></div>
                </div>
            </section>
        </main>

        <footer>Soporte y verificacion para Netflix, Prime Video y Disney+.</footer>
    </div>

    <script>
        window.alert = (message) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Mensaje',
                    text: message,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            }
        };

        const platforms = {
            netflix: {
                label: 'Netflix',
                title: 'Netflix - Consulta de Codigo',
                sub: 'Ingresa el correo asociado para recibir tu PIN de Netflix.'
            },
            prime: {
                label: 'Prime Video',
                title: 'Prime Video - Consulta de Codigo',
                sub: 'Verifica el correo de Amazon y recupera tu codigo.'
            },
            disney: {
                label: 'Disney+',
                title: 'Disney+ - Consulta de Codigo',
                sub: 'Recupera tu acceso temporal de Disney+.'
            }
        };

        const cards = document.querySelectorAll('.card');
        const platformInput = document.getElementById('plataforma');
        const panelTitle = document.getElementById('panel-title');
        const panelSub = document.getElementById('panel-sub');
        const platformInfo = document.getElementById('plataforma-info');

        cards.forEach(card => {
            card.addEventListener('click', () => {
                cards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                const key = card.dataset.platform;
                const data = platforms[key];
                platformInput.value = key;
                panelTitle.textContent = data.title;
                panelSub.textContent = data.sub;
                platformInfo.textContent = 'Plataforma: ' + data.label;
            });
        });

        async function consultar() {
            const correo = document.getElementById('correo').value.trim();
            const plataforma = platformInput.value;
            const resDiv = document.getElementById('resultado');
            const btn = document.getElementById('btn-buscar');

            if (!correo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Correo requerido',
                    text: 'Ingresa un correo valido para continuar.',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
                return;
            }

            btn.textContent = 'Buscando...';
            btn.disabled = true;

            try {
                const response = await fetch('/consultar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ correo, plataforma })
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    resDiv.classList.add('show');
                    document.getElementById('pin-display').textContent = data.pin || '----';
                    platformInfo.textContent = 'Plataforma: ' + (data.plataforma || platforms[plataforma].label);
                    const hora = data.hora ? data.hora + ' ' : '';
                    document.getElementById('tiempo-info').textContent = 'Recibido ' + hora + '(' + data.hace + ')';
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin codigo reciente',
                        text: data.message || 'No se encontro un codigo reciente.',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al consultar',
                    text: 'Intenta de nuevo en unos segundos.',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            } finally {
                btn.textContent = 'Obtener codigo';
                btn.disabled = false;
            }
        }

        function limpiar() {
            document.getElementById('correo').value = '';
            document.getElementById('pin-display').textContent = '----';
            document.getElementById('tiempo-info').textContent = '';
            document.getElementById('resultado').classList.remove('show');
        }
    </script>
</body>
</html>
