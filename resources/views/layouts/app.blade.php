<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POB System - PT SCM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --scm-primary: #1a3c5e;
            --scm-accent:  #e8a020;
        }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-brand { font-weight: 700; letter-spacing: 1px; }
        .sidebar {
            min-height: 100vh;
            background: var(--scm-primary);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            border-radius: 8px;
            margin: 2px 8px;
            transition: all .2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.15);
        }
        .sidebar .nav-link i { width: 20px; }
        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            transition: transform .2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-card.pob  { border-left: 5px solid #0d6efd; }
        .stat-card.mp   { border-left: 5px solid #198754; }
        .stat-card.week { border-left: 5px solid #fd7e14; }
        .stat-card.month{ border-left: 5px solid #6f42c1; }
        .table th { background: var(--scm-primary); color: #fff; }
        .badge-pob { background: #0d6efd; }
        .badge-mp  { background: #198754; }
    </style>
    @stack('styles')
</head>
<body>
@yield('content')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')
</body>
</html>
