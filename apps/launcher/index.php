<?php
// Docker Web App Launcher - portable version

$APPS = [
    'SIMPEG' => [
        'containers' => ['simpeg-web', 'simpeg-db', 'simpeg-phpmyadmin'],
        'url' => 'http://localhost:8080',
        'pma' => 'http://localhost:8081',
    ],
    'Fotorecv3' => [
        'containers' => ['fotorecv3-web', 'fotorecv3-db', 'fotorecv3-pma'],
        'url' => 'http://localhost:8082',
        'pma' => 'http://localhost:8083',
    ],
    'SPMB' => [
        'containers' => ['spmb-web', 'spmb-db', 'spmb-phpmyadmin'],
        'url' => 'http://localhost:8085',
        'pma' => 'http://localhost:8086',
    ],
];

$status = [];
foreach ($APPS as $key => $app) {
    $output = shell_exec("sudo docker ps --filter name={$app['containers'][0]} --format '{{.Names}}' 2>/dev/null");
    $status[$key] = !empty(trim($output ?? ''));
}

$action = $_GET['action'] ?? '';
$target = $_GET['target'] ?? '';

if ($action && $target && isset($APPS[$target])) {
    $containers = implode(' ', $APPS[$target]['containers']);
    if ($action === 'start') {
        shell_exec("sudo docker start $containers 2>&1 > /dev/null &");
    } elseif ($action === 'stop') {
        shell_exec("sudo docker stop $containers 2>&1 > /dev/null &");
    }
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Web App Launcher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            min-height: 100vh;
        }
        .container { max-width: 700px; margin: 0 auto; padding: 40px 20px; }
        h1 {
            text-align: center;
            font-weight: 300;
            font-size: 2.2em;
            margin-bottom: 10px;
            color: #fff;
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 40px;
            font-size: 0.95em;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
        .card {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #2a2a4a;
            transition: transform 0.2s, border-color 0.2s;
        }
        .card:hover { transform: translateY(-2px); border-color: #e94560; }
        .card h2 { font-weight: 400; font-size: 1.3em; margin-bottom: 16px; color: #fff; }
        .status-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9em;
        }
        .status-bar.running { background: rgba(76, 175, 80, 0.12); color: #81c784; }
        .status-bar.stopped { background: rgba(244, 67, 54, 0.10); color: #e57373; }
        .dot {
            width: 10px; height: 10px; border-radius: 50%; display: inline-block;
        }
        .dot.green { background: #4caf50; box-shadow: 0 0 8px rgba(76,175,80,0.5); }
        .dot.red { background: #f44336; box-shadow: 0 0 8px rgba(244,67,54,0.5); }
        .actions { display: flex; gap: 10px; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-start { background: #2e7d32; color: #fff; }
        .btn-start:hover { background: #388e3c; }
        .btn-stop { background: #c62828; color: #fff; }
        .btn-stop:hover { background: #d32f2f; }
        .btn-open { background: #1565c0; color: #fff; }
        .btn-open:hover { background: #1976d2; }
        .btn-pma { background: #6a1b9a; color: #fff; }
        .btn-pma:hover { background: #7b1fa2; }
        .btn-lab { background: #e65100; color: #fff; }
        .btn-lab:hover { background: #ef6c00; }
        .btn:active { transform: scale(0.97); }
        .links { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 6px;
        }
        .logo {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .brand h1 {
            text-align: left;
            margin-bottom: 2px;
        }
        .brand .subtitle {
            text-align: left;
            margin-bottom: 0;
        }
        .badge-nick {
            text-align: center;
            margin-bottom: 32px;
            font-size: 0.85em;
            color: #777;
        }
        .badge-nick strong {
            color: #e94560;
            font-weight: 600;
        }
        .nick-icon {
            display: inline-block;
            vertical-align: middle;
            margin-right: 4px;
            color: #e94560;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            color: #555;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo-section">
        <div class="logo">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="lg" x1="0" y1="0" x2="60" y2="60">
                        <stop offset="0%" stop-color="#e94560"/>
                        <stop offset="100%" stop-color="#ff6b6b"/>
                    </linearGradient>
                    <linearGradient id="lg2" x1="0" y1="0" x2="60" y2="60">
                        <stop offset="0%" stop-color="#4fc3f7"/>
                        <stop offset="100%" stop-color="#1565c0"/>
                    </linearGradient>
                </defs>
                <rect x="8" y="18" width="44" height="30" rx="6" fill="url(#lg)" opacity="0.15"/>
                <path d="M30 8L12 18v14c0 10.5 7.2 20.3 18 22 10.8-1.7 18-11.5 18-22V18L30 8z" fill="url(#lg)" opacity="0.9"/>
                <path d="M30 12L16 20v12c0 8.4 5.8 16.2 14 17.5 8.2-1.3 14-9.1 14-17.5V20L30 12z" fill="url(#lg2)" opacity="0.8"/>
                <path d="M24 30l4 4 8-8" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="30" cy="30" r="2" fill="#fff" opacity="0.3"/>
            </svg>
        </div>
        <div class="brand">
            <h1>Web App Launcher</h1>
            <p class="subtitle">Lab Simulasi Pembelajaran Keamanan Siber</p>
        </div>
    </div>
    <div class="badge-nick">
        <svg class="nick-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        Created by <strong>adpermana</strong>
    </div>

    <div class="grid">
        <?php foreach ($APPS as $key => $app): $is_on = $status[$key]; ?>
        <div class="card">
            <h2><?= $key ?></h2>
            <div class="status-bar <?= $is_on ? 'running' : 'stopped' ?>">
                <span class="dot <?= $is_on ? 'green' : 'red' ?>"></span>
                <?= $is_on ? 'Sedang berjalan' : 'Berhenti' ?>
            </div>
            <div class="actions">
                <?php if ($is_on): ?>
                    <a href="?action=stop&target=<?= $key ?>" class="btn btn-stop" onclick="return confirm('Hentikan <?= $key ?>?')">Stop</a>
                <?php else: ?>
                    <a href="?action=start&target=<?= $key ?>" class="btn btn-start">Start</a>
                <?php endif; ?>
            </div>
            <?php if ($is_on): ?>
            <div class="links">
                <a href="<?= $app['url'] ?>" target="_blank" class="btn btn-open">Buka Aplikasi</a>
                <a href="<?= $app['pma'] ?>" target="_blank" class="btn btn-pma">phpMyAdmin</a>
                <a href="lab.php?app=<?= $key ?>" class="btn btn-lab">Lab</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">Lab Keamanan Siber &mdash; <?= date('Y') ?></div>
</div>
</body>
</html>
