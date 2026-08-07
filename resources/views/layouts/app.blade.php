<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Obrador') }}</title>

    @vite('resources/js/app.js')

    <style>
        :root {
            --primary:           #6366f1;
            --primary-dark:      #4f46e5;
            --primary-light:     #e0e7ff;
            --sidebar-bg:        #0f172a;
            --sidebar-text:      #94a3b8;
            --sidebar-hover:     rgba(255,255,255,0.055);
            --sidebar-active-bg: rgba(99,102,241,0.18);
            --sidebar-width:     260px;
            --topbar-height:     62px;
            --bg-main:           #f1f5f9;
            --border:            #e2e8f0;
            --text-heading:      #0f172a;
            --text-body:         #374151;
            --text-muted:        #64748b;
            --card-radius:       12px;
            --shadow-sm:         0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md:         0 4px 12px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.05);
            --shadow-lg:         0 10px 30px rgba(0,0,0,0.12);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-main);
            color: var(--text-body);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* ── SIDEBAR ─────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }

        /* Brand */
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            height: var(--topbar-height);
            border-bottom: 1px solid rgba(255,255,255,0.055);
            flex-shrink: 0;
        }

        .sidebar-brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        }

        .sidebar-brand-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #f1f5f9;
            line-height: 1.2;
        }

        .sidebar-brand-sub {
            font-size: 0.68rem;
            color: var(--sidebar-text);
            font-weight: 400;
        }

        /* Section labels */
        .sidebar-label {
            padding: 18px 20px 5px;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(148,163,184,0.45);
        }

        /* Nav items */
        .sidebar-nav {
            list-style: none;
            padding: 8px 10px;
            margin: 0;
            flex: 1;
        }

        .sidebar-nav li { margin-bottom: 1px; }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.845rem;
            font-weight: 500;
            transition: background 0.14s, color 0.14s;
            position: relative;
        }

        .sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: #e2e8f0;
        }

        .sidebar-nav a.active {
            background: var(--sidebar-active-bg);
            color: #a5b4fc;
            font-weight: 600;
        }

        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 18px;
            background: var(--primary);
            border-radius: 0 3px 3px 0;
        }

        .sidebar-nav a i {
            width: 17px;
            text-align: center;
            font-size: 0.875rem;
            flex-shrink: 0;
            opacity: 0.8;
        }

        .sidebar-nav a.active i { opacity: 1; }

        /* Footer / user */
        .sidebar-footer {
            padding: 10px;
            border-top: 1px solid rgba(255,255,255,0.055);
            flex-shrink: 0;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 8px;
            transition: background 0.14s;
        }

        .sidebar-user:hover { background: var(--sidebar-hover); }

        .sidebar-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700;
            color: white; flex-shrink: 0;
        }

        .sidebar-user-name  { font-size: 0.8rem; font-weight: 600; color: #e2e8f0; line-height: 1.2; }
        .sidebar-user-role  { font-size: 0.68rem; color: var(--sidebar-text); }

        .sidebar-logout {
            background: none; border: none; padding: 0;
            color: rgba(148,163,184,0.5);
            cursor: pointer; line-height: 1;
            transition: color 0.14s;
        }
        .sidebar-logout:hover { color: #f87171; }

        /* ── MAIN CONTENT ────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ──────────────────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 100;
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
        }

        .topbar-user-btn {
            display: flex; align-items: center; gap: 9px;
            padding: 5px 10px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.845rem;
            font-weight: 500;
            transition: background 0.14s;
        }
        .topbar-user-btn:hover { background: #f8fafc; color: var(--text-body); }

        .topbar-avatar {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.68rem; font-weight: 700; color: white;
        }

        /* ── CONTENT WRAPPER ─────────────────────────────────── */
        .content-wrapper {
            padding: 26px 28px;
            flex: 1;
        }

        /* ── CARDS ───────────────────────────────────────────── */
        .card {
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            background: white;
            margin-bottom: 20px;
        }

        .card-header {
            background: white !important;
            border-bottom: 1px solid var(--border);
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-heading);
            display: flex;
            align-items: center;
        }

        .card-header i { color: var(--primary); }

        /* ── STAT CARDS ──────────────────────────────────────── */
        .stat-card {
            border: none !important;
            border-radius: var(--card-radius) !important;
            overflow: hidden;
            margin-bottom: 0;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg) !important;
        }

        .stat-card .card-body { padding: 22px; }

        .stat-card-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgba(255,255,255,0.8);
            margin-bottom: 6px;
        }

        .stat-card-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            line-height: 1;
            margin-bottom: 2px;
        }

        .stat-card-sub {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.65);
        }

        .stat-card-icon {
            width: 46px; height: 46px;
            background: rgba(255,255,255,0.18);
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; color: white;
        }

        /* ── TABLES ──────────────────────────────────────────── */
        .table { margin-bottom: 0; font-size: 0.845rem; }

        .table thead th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            background: #f8fafc;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 11px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-body);
        }

        .table tbody tr:last-child td { border-bottom: none; }
        .table-hover tbody tr:hover td { background: #f8fafc; }
        .table tfoot td, .table tfoot th { background: #f8fafc; font-size: 0.845rem; }

        /* ── BUTTONS ─────────────────────────────────────────── */
        .btn {
            font-size: 0.825rem;
            font-weight: 500;
            border-radius: 7px;
            padding: 7px 16px;
            line-height: 1.4;
            transition: all 0.14s;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }

        .btn-success:hover  { box-shadow: 0 4px 12px rgba(34,197,94,0.3); }
        .btn-warning:hover  { box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
        .btn-danger:hover   { box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
        .btn-info:hover     { box-shadow: 0 4px 12px rgba(59,130,246,0.3); }

        .btn-sm {
            padding: 4px 10px;
            font-size: 0.775rem;
            border-radius: 6px;
        }

        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }

        /* ── BADGES ──────────────────────────────────────────── */
        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 5px;
        }

        /* ── ALERTS ──────────────────────────────────────────── */
        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 10px;
            font-size: 0.845rem;
            padding: 12px 16px;
        }
        .alert-success { border-left-color: #22c55e; background: #f0fdf4; color: #166534; }
        .alert-danger  { border-left-color: #ef4444; background: #fef2f2; color: #991b1b; }
        .alert-warning { border-left-color: #f59e0b; background: #fffbeb; color: #92400e; }
        .alert-info    { border-left-color: #3b82f6; background: #eff6ff; color: #1e40af; }
        .alert-secondary { border-left-color: #94a3b8; background: #f8fafc; color: #475569; }

        /* ── FORMS ───────────────────────────────────────────── */
        .form-control, .form-select {
            border-radius: 7px;
            border: 1px solid var(--border);
            font-size: 0.845rem;
            padding: 8px 12px;
            color: var(--text-body);
            background: white;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .form-text { font-size: 0.75rem; }
        .input-group-text {
            border-radius: 7px;
            border: 1px solid var(--border);
            background: #f8fafc;
            font-size: 0.845rem;
        }

        /* ── MODALS ──────────────────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
        }
        .modal-header {
            border-bottom: 1px solid var(--border);
            padding: 16px 20px;
            background: #f8fafc;
            border-radius: 14px 14px 0 0;
        }
        .modal-title { font-size: 0.92rem; font-weight: 600; color: var(--text-heading); }
        .modal-body { padding: 20px; }
        .modal-footer {
            border-top: 1px solid var(--border);
            padding: 12px 20px;
            background: #f8fafc;
            border-radius: 0 0 14px 14px;
        }

        /* ── DROPDOWN ────────────────────────────────────────── */
        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            padding: 6px;
            font-size: 0.845rem;
        }
        .dropdown-item {
            border-radius: 6px;
            padding: 7px 12px;
            color: var(--text-body);
        }
        .dropdown-item:hover { background: #f1f5f9; }
        .dropdown-divider { margin: 4px 0; }

        /* ── PAGINATION ──────────────────────────────────────── */
        .pagination .page-link {
            border-radius: 7px !important;
            margin: 0 2px;
            border: 1px solid var(--border);
            color: #475569;
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        .pagination .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }
        .pagination .page-link:hover { background: #f1f5f9; border-color: #cbd5e1; }

        /* ── LIST GROUP ──────────────────────────────────────── */
        .list-group-item {
            border-color: #f1f5f9;
            font-size: 0.845rem;
            padding: 10px 16px;
        }

        /* ── MISC ────────────────────────────────────────────── */
        .text-muted { color: var(--text-muted) !important; }

        hr { border-color: var(--border); opacity: 1; }

        .card-footer {
            background: #f8fafc;
            border-top: 1px solid var(--border);
            border-radius: 0 0 var(--card-radius) var(--card-radius);
            padding: 10px 16px;
        }

        /* Page header pattern used across modules */
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .module-header h4, .module-header h5 {
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
        }

        /* ── COMPONENTE: x-page-header ───────────────────────── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .page-header-text { min-width: 0; }
        .page-header-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header-title i {
            color: var(--primary);
            font-size: 1rem;
        }
        .page-header-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin: 4px 0 0;
        }
        .page-header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ── COMPONENTE: x-stat-card ─────────────────────────── */
        .stat-card-link {
            text-decoration: none;
            display: block;
        }
        .stat-card-link:hover { text-decoration: none; }
        .stat-card .min-w-0 { min-width: 0; }

        /* ── COMPONENTE: x-empty-state ───────────────────────── */
        .empty-state {
            text-align: center;
            padding: 44px 20px;
            color: var(--text-muted);
        }
        .empty-state-icon {
            width: 62px; height: 62px;
            margin: 0 auto 14px;
            border-radius: 16px;
            background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            color: #94a3b8;
        }
        .empty-state-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-heading);
        }
        .empty-state-message {
            font-size: 0.83rem;
            margin-top: 4px;
            max-width: 420px;
            margin-inline: auto;
        }

        /* ── UTILIDADES DE MÓDULO ────────────────────────────── */
        .filter-bar {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            padding: 14px 16px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .section-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        /* Tabla con la primera columna fija (usada en resumen anual) */
        .table-sticky-first td:first-child,
        .table-sticky-first th:first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            background: white;
        }
        .table-sticky-first thead th:first-child {
            z-index: 2;
            background: #f8fafc;
        }

        /* ── RESPONSIVE ──────────────────────────────────────── */
        @media (max-width: 991.98px) {
            .content-wrapper { padding: 18px 16px; }
            .page-header-title { font-size: 1.05rem; }
            .stat-card .card-body { padding: 16px; }
            .stat-card-value { font-size: 1.45rem; }
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- ── SIDEBAR ──────────────────────────────────────────── -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="fas fa-bread-slice"></i>
            </div>
            <div>
                <div class="sidebar-brand-name">{{ config('app.name') }}</div>
                <div class="sidebar-brand-sub">Panel de Gestión</div>
            </div>
        </div>

        <div class="sidebar-label">Principal</div>

        <ul class="sidebar-nav">
            @can('ver-dashboard')
            <li>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fas fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @endcan

            @role('Administrador')
            <li>
                <a href="{{ route('finanzas.index') }}" class="{{ request()->routeIs('finanzas.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>Resumen Financiero</span>
                </a>
            </li>
            @endrole

            @can('ver-categorias')
            <li>
                <a href="{{ route('categorias.index') }}" class="{{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    <span>Categorías</span>
                </a>
            </li>
            @endcan

            @can('ver-productos')
            <li>
                <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : '' }}">
                    <i class="fas fa-cookie-bite"></i>
                    <span>Productos</span>
                </a>
            </li>
            @endcan

            @can('ver-insumos')
            <li>
                <a href="{{ route('insumos.index') }}" class="{{ request()->routeIs('insumos.*') ? 'active' : '' }}">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Insumos</span>
                </a>
            </li>
            @endcan

            @can('ver-produccion')
            <li>
                <a href="{{ route('produccion.index') }}" class="{{ request()->routeIs('produccion.*') ? 'active' : '' }}">
                    <i class="fas fa-industry"></i>
                    <span>Producción</span>
                </a>
            </li>
            @endcan

            @can('ver-ventas')
            <li>
                <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                    <i class="fas fa-cart-shopping"></i>
                    <span>Ventas</span>
                </a>
            </li>
            @endcan

            @can('ver-almacenes')
            <li>
                <a href="{{ route('almacenes.index') }}" class="{{ request()->routeIs('almacenes.*') ? 'active' : '' }}">
                    <i class="fas fa-warehouse"></i>
                    <span>Almacenes</span>
                </a>
            </li>
            @endcan

            @can('ver-cortes')
            <li>
                <a href="{{ route('cortes.index') }}" class="{{ request()->routeIs('cortes.*') ? 'active' : '' }}">
                    <i class="fas fa-cash-register"></i>
                    <span>Cortes de Caja</span>
                </a>
            </li>
            @endcan

            @can('ver-reportes')
            <li>
                <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </li>
            @endcan
        </ul>

        @role('Administrador')
        <div class="sidebar-label">Recursos Humanos</div>
        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('empleados.index') }}" class="{{ request()->routeIs('empleados.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Empleados</span>
                </a>
            </li>
            <li>
                <a href="{{ route('asistencias.index') }}" class="{{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>Asistencias</span>
                </a>
            </li>
            <li>
                <a href="{{ route('planillas.index') }}" class="{{ request()->routeIs('planillas.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Planillas</span>
                </a>
            </li>
            <li>
                <a href="{{ route('cargos.index') }}" class="{{ request()->routeIs('cargos.*') ? 'active' : '' }}">
                    <i class="fas fa-briefcase"></i>
                    <span>Cargos</span>
                </a>
            </li>
        </ul>
        @endrole

        @role('Administrador')
        <div class="sidebar-label">Gastos</div>
        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('gastos-pagos.index') }}" class="{{ request()->routeIs('gastos-pagos.index') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Control de Pagos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gastos-pagos.anual') }}" class="{{ request()->routeIs('gastos-pagos.anual') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Resumen Anual</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gastos-fijos.index') }}" class="{{ request()->routeIs('gastos-fijos.*') ? 'active' : '' }}">
                    <i class="fas fa-receipt"></i>
                    <span>Gastos Fijos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gastos-variables.index') }}" class="{{ request()->routeIs('gastos-variables.*') ? 'active' : '' }}">
                    <i class="fas fa-cart-flatbed"></i>
                    <span>Gastos Variables</span>
                </a>
            </li>
        </ul>
        @endrole

        @role('Administrador')
        <div class="sidebar-label">Sistema</div>
        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('system.backups.index') }}" class="{{ request()->routeIs('system.backups.*') ? 'active' : '' }}">
                    <i class="fas fa-database"></i>
                    <span>Respaldos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('system.about') }}" class="{{ request()->routeIs('system.about') ? 'active' : '' }}">
                    <i class="fas fa-circle-info"></i>
                    <span>Acerca del sistema</span>
                </a>
            </li>
        </ul>
        @endrole

        @can('ver-usuarios')
        <div class="sidebar-label">Administración</div>
        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i>
                    <span>Usuarios</span>
                </a>
            </li>
        </ul>
        @endcan

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="sidebar-user-name text-truncate">{{ Auth::user()->name }}</div>
                    <div class="sidebar-user-role">
                        {{ Auth::user()->getRoleNames()->first() ?? 'Usuario' }}
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout" title="Cerrar sesión">
                        <i class="fas fa-right-from-bracket" style="font-size:0.85rem"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ── MAIN ──────────────────────────────────────────────── -->
    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <h5 class="topbar-title">@yield('page-title', 'Dashboard')</h5>

            <div class="dropdown">
                <a class="topbar-user-btn dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <div class="topbar-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                    <span>{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user me-2 text-muted" style="font-size:.8rem"></i>Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('system.about') }}">
                            <i class="fas fa-circle-info me-2 text-muted" style="font-size:.8rem"></i>Acerca del sistema
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-right-from-bracket me-2" style="font-size:.8rem"></i>Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <!-- Content -->
        <div class="content-wrapper">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
