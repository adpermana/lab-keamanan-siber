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
    <h1>Web App Launcher</h1>
    <p class="subtitle">Lab Simulasi Pembelajaran Keamanan Siber</p>

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
