<?php
// Helper para leer variables de entorno (desde panel de Hostinger o archivo .env)
function get_env_variable($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    
    $env_path = __DIR__ . '/../../../.env';
    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $value = trim($value, "\"'");
                if ($name === $key) {
                    return $value;
                }
            }
        }
    }
    return $default;
}

$expected_user = get_env_variable('ADMIN_USER', 'admin');
$expected_pass = get_env_variable('ADMIN_PASS', 'admin123');

// Solicitar autenticación Basic Auth nativa en PHP
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || 
    $_SERVER['PHP_AUTH_USER'] !== $expected_user || $_SERVER['PHP_AUTH_PW'] !== $expected_pass) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<html><body style="font-family: sans-serif; text-align: center; margin-top: 100px; background-color: #121212; color: #fff;"><h1>Acceso Denegado</h1><p>Se requiere inicio de sesión para acceder al panel de administración.</p></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Rojas Construcciones</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg-dark: #0d0d0d;
            --color-bg-card: #151515;
            --color-bg-input: #1f1f1f;
            --color-accent: #e50914; /* Rojo Rojas */
            --color-accent-hover: #b80710;
            --color-text-primary: #ffffff;
            --color-text-secondary: #aaaaaa;
            --color-text-muted: #666666;
            --color-border: #2a2a2a;
            --color-success: #15b762;
            --color-warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-bg-dark);
            color: var(--color-text-primary);
            line-height: 1.6;
            padding: 20px;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* HEADER */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand img {
            height: 45px;
        }

        .brand h1 {
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        .brand h1 span {
            color: var(--color-accent);
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            background-color: var(--color-accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: var(--color-accent-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            color: var(--color-text-primary);
        }

        .btn-secondary:hover {
            background-color: var(--color-bg-input);
            border-color: var(--color-text-secondary);
        }

        /* METRICS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background-color: rgba(229, 9, 20, 0.1);
            color: var(--color-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .metric-icon.success {
            background-color: rgba(21, 183, 98, 0.1);
            color: var(--color-success);
        }

        .metric-icon.warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--color-warning);
        }

        .metric-info h3 {
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-info p {
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            margin-top: 2px;
        }

        /* CONTROLS (SEARCH & FILTERS) */
        .controls-section {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .filters-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
        }

        @media (min-width: 1024px) {
            .filters-group {
                width: auto;
            }
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
        }

        .input-search {
            width: 100%;
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border);
            color: white;
            padding: 12px 15px 12px 45px;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-search:focus {
            border-color: var(--color-accent);
        }

        .select-filter {
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border);
            color: white;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            cursor: pointer;
            min-width: 150px;
            transition: border-color 0.3s;
        }

        .select-filter:focus {
            border-color: var(--color-accent);
        }

        .view-toggle {
            display: flex;
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border);
            border-radius: 6px;
            padding: 3px;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--color-text-secondary);
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .toggle-btn.active {
            background-color: var(--color-accent);
            color: white;
        }

        /* QUERY GRID & CARDS */
        .queries-container {
            margin-bottom: 50px;
        }

        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 25px;
        }

        .query-card {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: transform 0.3s, border-color 0.3s;
            position: relative;
            overflow: hidden;
        }

        .query-card:hover {
            transform: translateY(-4px);
            border-color: rgba(229, 9, 20, 0.4);
        }

        .query-card.respondido-true::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--color-success);
        }

        .query-card.respondido-false::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--color-warning);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .client-name {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .date-badge {
            font-size: 0.75rem;
            color: var(--color-text-muted);
        }

        .project-badge {
            display: inline-block;
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border);
            color: var(--color-text-secondary);
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .client-contact-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.9rem;
            background-color: var(--color-bg-input);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.02);
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--color-text-secondary);
            text-decoration: none;
        }

        .contact-item i {
            color: var(--color-accent);
            width: 15px;
        }

        .contact-item:hover {
            color: white;
            text-decoration: underline;
        }

        .query-message {
            font-size: 0.92rem;
            color: var(--color-text-secondary);
            background-color: rgba(255, 255, 255, 0.01);
            padding: 12px;
            border-radius: 6px;
            border-left: 2px solid var(--color-border);
            min-height: 60px;
            white-space: pre-wrap;
        }

        /* EDITABLE FIELDS INSIDE CARD */
        .editable-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .editable-field label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--color-text-muted);
            letter-spacing: 0.5px;
        }

        .input-inline {
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-inline:focus {
            border-color: var(--color-accent);
        }

        .textarea-inline {
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            user-select: none;
        }

        /* SWITCH TOGGLE */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--color-border);
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--color-success);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            border-top: 1px solid var(--color-border);
            padding-top: 15px;
        }

        .whatsapp-btn {
            background-color: #25d366;
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .whatsapp-btn:hover {
            background-color: #1ebd59;
            transform: translateY(-2px);
        }

        /* TABLE VIEW */
        .table-view {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            overflow: hidden;
        }

        .table-view th, .table-view td {
            padding: 15px 20px;
            text-align: left;
        }

        .table-view th {
            background-color: var(--color-bg-input);
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-secondary);
            border-bottom: 1px solid var(--color-border);
        }

        .table-view tr {
            border-bottom: 1px solid var(--color-border);
            transition: background-color 0.3s;
        }

        .table-view tr:hover {
            background-color: rgba(255, 255, 255, 0.01);
        }

        .table-view td {
            font-size: 0.92rem;
            vertical-align: top;
        }

        .table-view .client-cell {
            font-weight: 600;
        }

        .table-view .status-cell-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .table-view .status-cell-badge.respondido-true {
            background-color: rgba(21, 183, 98, 0.1);
            color: var(--color-success);
            border: 1px solid rgba(21, 183, 98, 0.2);
        }

        .table-view .status-cell-badge.respondido-false {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--color-warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .table-action-btn {
            background: none;
            border: 1px solid var(--color-border);
            color: var(--color-text-secondary);
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .table-action-btn:hover {
            background-color: var(--color-bg-input);
            border-color: var(--color-text-primary);
            color: white;
        }

        /* NOTIFICATION / TOAST */
        .toast {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--color-success);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            z-index: 1000;
            transform: translateY(150%);
            transition: transform 0.3s ease;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast.error {
            background-color: var(--color-accent);
        }

        .toast.show {
            transform: translateY(0);
        }

        .hidden {
            display: none !important;
        }

        /* MODAL FOR TABLE VIEW DETAILS */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background-color: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
        }

        .modal-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: var(--color-text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: white;
        }
    </style>
</head>
<body>

<div class="admin-container">
    
    <!-- HEADER -->
    <header class="admin-header">
        <div class="brand">
            <img src="../images/logo-white-vertical.png" alt="Rojas logo">
            <h1>Rojas <span>Construcciones</span> S.A.S.</h1>
        </div>
        <div class="header-actions">
            <a href="../" class="btn btn-secondary"><i class="fa-solid fa-earth-americas"></i> Ir a la Web</a>
            <a href="../api/consultas/excel" target="_blank" class="btn"><i class="fa-solid fa-file-excel"></i> Descargar Excel</a>
        </div>
    </header>

    <!-- METRICS -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-inbox"></i></div>
            <div class="metric-info">
                <h3>Total Consultas</h3>
                <p id="metric-total">0</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon warning"><i class="fa-solid fa-clock"></i></div>
            <div class="metric-info">
                <h3>Pendientes</h3>
                <p id="metric-pendientes">0</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="metric-info">
                <h3>Respondidas</h3>
                <p id="metric-respondidas">0</p>
            </div>
        </div>
    </div>

    <!-- CONTROLS & SEARCH -->
    <div class="controls-section">
        <div class="filters-group">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-input" class="input-search" placeholder="Buscar por nombre, mail, tel, mensaje...">
            </div>
            
            <select id="filter-status" class="select-filter">
                <option value="all">Todos los Estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="respondido">Respondidos</option>
            </select>

            <select id="filter-project" class="select-filter">
                <option value="all">Todos los Proyectos</option>
                <option value="obra_nueva">Obra Nueva</option>
                <option value="refaccion">Refacción/Ampliación</option>
                <option value="comercial">Comercial</option>
                <option value="infraestructura">Civil/Infraestructura</option>
            </select>
        </div>

        <div class="view-toggle">
            <button id="btn-view-cards" class="toggle-btn active"><i class="fa-solid fa-grip"></i> Tarjetas</button>
            <button id="btn-view-table" class="toggle-btn"><i class="fa-solid fa-list"></i> Tabla</button>
        </div>
    </div>

    <!-- QUERIES LIST -->
    <div class="queries-container">
        <!-- Tarjetas (Grid) -->
        <div id="queries-grid" class="grid-view">
            <!-- Cargadas dinámicamente -->
        </div>

        <!-- Tabla -->
        <table id="queries-table" class="table-view hidden">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Proyecto</th>
                    <th>Detalles</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="queries-table-body">
                <!-- Cargadas dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<!-- DETALLES MODAL (Para vista de tabla) -->
<div id="detail-modal" class="modal">
    <div class="modal-card" id="modal-card-content">
        <button class="modal-close-btn" onclick="closeModal()">&times;</button>
        <h2 style="margin-bottom: 20px; border-bottom: 1px solid var(--color-border); padding-bottom: 10px;">Detalles de Consulta</h2>
        <div id="modal-fields" style="display: flex; flex-direction: column; gap: 15px;">
            <!-- Cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- TOAST NOTIFICACIÓN -->
<div id="toast" class="toast">
    <i class="fa-solid fa-circle-check"></i> <span id="toast-message">Cambios guardados con éxito</span>
</div>

<script>
    let currentQueries = [];
    let activeView = 'cards'; // 'cards' o 'table'

    // Cargar consultas iniciales
    async function loadQueries() {
        try {
            const response = await fetch('../api/consultas');
            if (!response.ok) throw new Error('Error al cargar datos.');
            currentQueries = await response.json();
            
            // Garantizar estructura de campos nuevos si no existen
            currentQueries = currentQueries.map(q => ({
                id: q.id,
                fecha: q.fecha,
                nombre: q.nombre,
                telefono: q.telefono,
                email: q.email || '',
                tipo_proyecto: q.tipo_proyecto || '',
                mensaje: q.mensaje || '',
                respondido: q.respondido === undefined ? false : q.respondido,
                zona: q.zona || '',
                notas: q.notas || ''
            }));

            updateMetrics();
            renderQueries();
        } catch (e) {
            showToast('Error de red al leer consultas', true);
        }
    }

    // Actualizar métricas del header
    function updateMetrics() {
        const total = currentQueries.length;
        const respondidas = currentQueries.filter(q => q.respondido).length;
        const pendientes = total - respondidas;

        document.getElementById('metric-total').textContent = total;
        document.getElementById('metric-respondidas').textContent = respondidas;
        document.getElementById('metric-pendientes').textContent = pendientes;
    }

    // Traducir badges de tipo de obra
    function translateProject(type) {
        const mapping = {
            'obra_nueva': 'Obra Nueva',
            'refaccion': 'Refacción / Ampliación',
            'comercial': 'Comercial / Oficinas',
            'infraestructura': 'Civil / Infraestructura'
        };
        return mapping[type] || type || 'No especificado';
    }

    // Renderizar consultas aplicando filtros y búsqueda
    function renderQueries() {
        const searchQuery = document.getElementById('search-input').value.toLowerCase();
        const statusFilter = document.getElementById('filter-status').value;
        const projectFilter = document.getElementById('filter-project').value;

        // Filtrado
        const filtered = currentQueries.filter(q => {
            const matchSearch = q.nombre.toLowerCase().includes(searchQuery) ||
                                q.email.toLowerCase().includes(searchQuery) ||
                                q.telefono.toLowerCase().includes(searchQuery) ||
                                q.mensaje.toLowerCase().includes(searchQuery) ||
                                q.zona.toLowerCase().includes(searchQuery) ||
                                q.notas.toLowerCase().includes(searchQuery);

            const matchStatus = statusFilter === 'all' || 
                                (statusFilter === 'respondido' && q.respondido) ||
                                (statusFilter === 'pendiente' && !q.respondido);

            const matchProject = projectFilter === 'all' || q.tipo_proyecto === projectFilter;

            return matchSearch && matchStatus && matchProject;
        });

        // Grid (Tarjetas)
        const gridContainer = document.getElementById('queries-grid');
        gridContainer.innerHTML = '';

        // Tabla
        const tableBody = document.getElementById('queries-table-body');
        tableBody.innerHTML = '';

        if (filtered.length === 0) {
            gridContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--color-text-secondary);">No se encontraron consultas con los filtros seleccionados.</div>';
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--color-text-secondary);">No se encontraron consultas.</td></tr>';
            return;
        }

        filtered.forEach(q => {
            // RENDER CARDS VIEW
            const card = document.createElement('div');
            card.className = `query-card respondido-${q.respondido}`;
            
            const date = new Date(q.fecha).toLocaleDateString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            
            card.innerHTML = `
                <div class="card-header">
                    <div>
                        <h3 class="client-name">${escapeHTML(q.nombre)}</h3>
                        <span class="date-badge">${date}</span>
                    </div>
                    <span class="project-badge">${translateProject(q.tipo_proyecto)}</span>
                </div>

                <div class="client-contact-info">
                    <a href="tel:${q.telefono}" class="contact-item"><i class="fa-solid fa-phone"></i> ${escapeHTML(q.telefono)}</a>
                    ${q.email && q.email !== 'No especificado' ? `<a href="mailto:${q.email}" class="contact-item"><i class="fa-solid fa-envelope"></i> ${escapeHTML(q.email)}</a>` : ''}
                </div>

                <div class="query-message">${escapeHTML(q.mensaje)}</div>

                <div class="editable-field">
                    <label>Zona / Domicilio</label>
                    <input type="text" class="input-inline" value="${escapeHTML(q.zona)}" placeholder="Ej: Villa Ayelén / Trevelin" onchange="updateQueryField('${q.id}', 'zona', this.value)">
                </div>

                <div class="editable-field">
                    <label>Notas internas / Presupuesto</label>
                    <textarea class="input-inline textarea-inline" placeholder="Agregar detalles sobre el presupuesto o reuniones..." onchange="updateQueryField('${q.id}', 'notas', this.value)">${escapeHTML(q.notas)}</textarea>
                </div>

                <div class="card-actions">
                    <label class="status-toggle">
                        <span class="switch">
                            <input type="checkbox" ${q.respondido ? 'checked' : ''} onchange="updateQueryField('${q.id}', 'respondido', this.checked)">
                            <span class="slider"></span>
                        </span>
                        <span>${q.respondido ? 'Respondido' : 'Pendiente'}</span>
                    </label>
                    
                    <a href="https://wa.me/${q.telefono.replace(/[^0-9]/g, '')}" target="_blank" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            `;
            gridContainer.appendChild(card);

            // RENDER TABLE VIEW
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-size: 0.8rem; color: var(--color-text-muted);">${date}</td>
                <td class="client-cell">${escapeHTML(q.nombre)}</td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <span><i class="fa-solid fa-phone" style="color: var(--color-accent); font-size: 0.8rem; width: 15px;"></i> ${escapeHTML(q.telefono)}</span>
                        ${q.email && q.email !== 'No especificado' ? `<span style="font-size: 0.8rem; color: var(--color-text-secondary);"><i class="fa-solid fa-envelope" style="color: var(--color-accent); font-size: 0.8rem; width: 15px;"></i> ${escapeHTML(q.email)}</span>` : ''}
                    </div>
                </td>
                <td><span class="project-badge">${translateProject(q.tipo_proyecto)}</span></td>
                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem; color: var(--color-text-secondary);">${escapeHTML(q.mensaje)}</td>
                <td>
                    <span class="status-cell-badge respondido-${q.respondido}">${q.respondido ? 'Respondido' : 'Pendiente'}</span>
                </td>
                <td>
                    <button class="table-action-btn" onclick="openDetailsModal('${q.id}')"><i class="fa-solid fa-pen-to-square"></i> Gestionar</button>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    }

    // Guardar cambios en el backend mediante AJAX
    async function updateQueryField(id, field, value) {
        // Encontrar en el array local y actualizar
        const queryIndex = currentQueries.findIndex(q => q.id === id);
        if (queryIndex === -1) return;

        const originalVal = currentQueries[queryIndex][field];
        currentQueries[queryIndex][field] = value;

        try {
            const response = await fetch('../api/consultas/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    [field]: value
                })
            });

            if (!response.ok) throw new Error('Error al actualizar.');

            showToast('Consulta actualizada');
            updateMetrics();
            
            // Re-renderizar si cambiamos estado
            if (field === 'respondido') {
                renderQueries();
            }
        } catch (e) {
            // Revertir en caso de error
            currentQueries[queryIndex][field] = originalVal;
            renderQueries();
            showToast('Error al guardar los cambios en Hostinger', true);
        }
    }

    // Abrir modal de detalles en vista de tabla
    function openDetailsModal(id) {
        const q = currentQueries.find(item => item.id === id);
        if (!q) return;

        const modalFields = document.getElementById('modal-fields');
        const date = new Date(q.fecha).toLocaleString('es-AR');

        modalFields.innerHTML = `
            <div>
                <strong style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase;">Cliente</strong>
                <p style="font-size: 1.1rem; font-weight: 600; margin-top: 2px;">${escapeHTML(q.nombre)}</p>
                <p style="font-size: 0.8rem; color: var(--color-text-muted);">${date}</p>
            </div>
            
            <div class="client-contact-info">
                <a href="tel:${q.telefono}" class="contact-item"><i class="fa-solid fa-phone"></i> ${escapeHTML(q.telefono)}</a>
                ${q.email && q.email !== 'No especificado' ? `<a href="mailto:${q.email}" class="contact-item"><i class="fa-solid fa-envelope"></i> ${escapeHTML(q.email)}</a>` : ''}
            </div>

            <div>
                <strong style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase;">Proyecto</strong>
                <p style="margin-top: 2px;"><span class="project-badge">${translateProject(q.tipo_proyecto)}</span></p>
            </div>

            <div>
                <strong style="font-size: 0.8rem; color: var(--color-text-muted); text-transform: uppercase;">Mensaje</strong>
                <p class="query-message" style="margin-top: 4px;">${escapeHTML(q.mensaje)}</p>
            </div>

            <div class="editable-field">
                <label>Zona / Domicilio</label>
                <input type="text" class="input-inline" value="${escapeHTML(q.zona)}" placeholder="Ej: Villa Ayelén / Trevelin" onchange="updateQueryField('${q.id}', 'zona', this.value); q.zona = this.value;">
            </div>

            <div class="editable-field">
                <label>Notas internas</label>
                <textarea class="input-inline textarea-inline" placeholder="Agregar detalles sobre el presupuesto o reuniones..." onchange="updateQueryField('${q.id}', 'notes', this.value); q.notas = this.value;">${escapeHTML(q.notas)}</textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid var(--color-border); padding-top: 15px;">
                <label class="status-toggle">
                    <span class="switch">
                        <input type="checkbox" ${q.respondido ? 'checked' : ''} onchange="updateQueryField('${q.id}', 'respondido', this.checked); closeModal();">
                        <span class="slider"></span>
                    </span>
                    <span>${q.respondido ? 'Respondido' : 'Pendiente'}</span>
                </label>
                
                <a href="https://wa.me/${q.telefono.replace(/[^0-9]/g, '')}" target="_blank" class="whatsapp-btn">
                    <i class="fab fa-whatsapp"></i> Responder
                </a>
            </div>
        `;

        document.getElementById('detail-modal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('detail-modal').classList.remove('active');
    }

    // Notificaciones flotantes
    function showToast(message, isError = false) {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-message');
        
        toastMsg.textContent = message;
        toast.className = 'toast';
        
        if (isError) {
            toast.classList.add('error');
        }
        
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Sanitizar textos para evitar XSS
    function escapeHTML(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Toggle de vistas
    document.getElementById('btn-view-cards').addEventListener('click', function() {
        activeView = 'cards';
        this.classList.add('active');
        document.getElementById('btn-view-table').classList.remove('active');
        document.getElementById('queries-grid').classList.remove('hidden');
        document.getElementById('queries-table').classList.add('hidden');
        renderQueries();
    });

    document.getElementById('btn-view-table').addEventListener('click', function() {
        activeView = 'table';
        this.classList.add('active');
        document.getElementById('btn-view-cards').classList.remove('active');
        document.getElementById('queries-grid').classList.add('hidden');
        document.getElementById('queries-table').classList.remove('hidden');
        renderQueries();
    });

    // Inputs de filtrado & búsqueda
    document.getElementById('search-input').addEventListener('input', renderQueries);
    document.getElementById('filter-status').addEventListener('change', renderQueries);
    document.getElementById('filter-project').addEventListener('change', renderQueries);

    // Inicializar app
    loadQueries();
</script>
</body>
</html>
