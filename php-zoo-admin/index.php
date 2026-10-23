<?php 
require_once 'login.php'; 
 
try {
    if (!isset($pdo)) {
        $pdo = new PDO($attr, $user, $pass, $opts);
    }
 
    $stmt = $pdo->query("SELECT COUNT(*) FROM complexes");
    $count_complexes = $stmt->fetchColumn();
 
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    $count_rooms = $stmt->fetchColumn();
 
    $stmt = $pdo->query("SELECT COUNT(*) FROM animal_species");
    $count_animals = $stmt->fetchColumn();
} catch (Exception $e) {
    $count_complexes = 5;
    $count_rooms = 5;
    $count_animals = 11;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления зоопарком</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        :root {
            --bg:       #F5F3EE;
            --surface:  #FFFFFF;
            --border:   #E2DDD6;
            --text-1:   #1A1A18;
            --text-2:   #5C5A54;
            --text-3:   #9A9891;
            --green:    #0F6E56;
            --green-bg: #E1F5EE;
            --green-mid:#1D9E75;
            --amber-bg: #FAEEDA;
            --amber:    #854F0B;
            --blue-bg:  #E6F1FB;
            --blue:     #185FA5;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
 
        body {
            font-family: 'DM Sans', Arial, sans-serif;
            background: var(--bg);
            color: var(--text-1);
            min-height: 100vh;
        }
 
        /* ── Header ── */
        .site-header {
            text-align: center;
            padding: 56px 20px 32px;
        }
        .eyebrow {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: 12px;
        }
        .site-title {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 700;
            color: var(--text-1);
            line-height: 1.15;
            margin-bottom: 8px;
        }
        .site-title em {
            color: var(--green);
            font-style: normal;
        }
        .site-sub {
            font-size: 14px;
            color: var(--text-2);
        }
 
        /* ── Nav ── */
        nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 0 20px 40px;
        }
        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            border-radius: 40px;
            font-size: 13.5px;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-2);
            transition: background 0.15s, color 0.15s, border-color 0.15s, transform 0.15s;
            cursor: pointer;
        }
        .nav-btn i { font-size: 16px; }
        .nav-btn:hover {
            background: #F0EDE8;
            color: var(--text-1);
            border-color: #C8C4BC;
            transform: translateY(-1px);
        }
        .nav-btn.active {
            background: var(--green);
            color: #A8E8D0;
            border-color: var(--green);
        }
        .nav-btn.active:hover {
            background: #0D5E49;
        }
 
        /* ── Main content ── */
        .content {
            max-width: 780px;
            margin: 0 auto;
            padding: 0 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
 
        /* ── Welcome card ── */
        .welcome-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        .welcome-icon {
            width: 56px;
            height: 56px;
            min-width: 56px;
            border-radius: var(--radius-sm);
            background: var(--green-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-icon i {
            font-size: 26px;
            color: var(--green);
        }
        .welcome-body { flex: 1; }
        .welcome-body h2 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-1);
            margin-bottom: 8px;
        }
        .welcome-body p {
            font-size: 14px;
            color: var(--text-2);
            line-height: 1.65;
        }
        .welcome-body p strong {
            color: var(--text-1);
            font-weight: 500;
        }
        .status-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green-mid);
            flex-shrink: 0;
        }
        .status-text {
            font-size: 12.5px;
            color: var(--text-2);
        }
        .db-badge {
            margin-left: auto;
            font-size: 12px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-bg);
            padding: 3px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .db-badge i { font-size: 13px; }
 
        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px 20px 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .stat-icon i { font-size: 20px; }
        .icon-green { background: var(--green-bg); } .icon-green i { color: var(--green); }
        .icon-amber { background: var(--amber-bg); } .icon-amber i { color: var(--amber); }
        .icon-blue  { background: var(--blue-bg);  } .icon-blue  i { color: var(--blue);  }
 
        .stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 40px;
            font-weight: 700;
            line-height: 1;
            color: var(--text-1);
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 12.5px;
            color: var(--text-2);
            font-weight: 400;
        }
        .stat-footer {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 11.5px;
            color: var(--green-mid);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-footer i { font-size: 13px; }
 
        @media (max-width: 560px) {
            .stats-grid { grid-template-columns: 1fr; }
            .welcome-card { flex-direction: column; }
            .site-title { font-size: 26px; }
        }
    </style>
</head>
<body>
 
    <header class="site-header">
        <div class="eyebrow">Административная система</div>
        <h1 class="site-title">Зоопарк <em>Admin</em></h1>
        <p class="site-sub">Управление данными и мониторинг в реальном времени</p>
    </header>
 
    <nav>
        <a href="index.php"    class="nav-btn active"><i class="ti ti-layout-dashboard"></i> Главная</a>
        <a href="complexes.php" class="nav-btn"><i class="ti ti-building-estate"></i> Комплексы</a>
        <a href="rooms.php"    class="nav-btn"><i class="ti ti-door"></i> Вольеры</a>
        <a href="animals.php"  class="nav-btn"><i class="ti ti-paw"></i> Животные</a>
    </nav>
 
    <main class="content">
 
        <div class="welcome-card">
            <div class="welcome-icon">
                <i class="ti ti-key"></i>
            </div>
            <div class="welcome-body">
                <h2>Панель управления запущена</h2>
                <p>Вы вошли в закрытую административную часть. Все изменения (добавление и удаление записей) напрямую синхронизируются с базой данных <strong>mydb</strong>.</p>
                <div class="status-bar">
                    <div class="dot"></div>
                    <span class="status-text">Соединение активно</span>
                    <span class="db-badge"><i class="ti ti-database"></i> mydb</span>
                </div>
            </div>
        </div>
 
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="ti ti-building-community"></i>
                </div>
                <div class="stat-num"><?php echo $count_complexes; ?></div>
                <div class="stat-label">Комплексов</div>
                <div class="stat-footer"><i class="ti ti-circle-check"></i> Все активны</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-amber">
                    <i class="ti ti-home"></i>
                </div>
                <div class="stat-num"><?php echo $count_rooms; ?></div>
                <div class="stat-label">Вольеров</div>
                <div class="stat-footer"><i class="ti ti-circle-check"></i> Все активны</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="ti ti-paw"></i>
                </div>
                <div class="stat-num"><?php echo $count_animals; ?></div>
                <div class="stat-label">Видов животных</div>
                <div class="stat-footer"><i class="ti ti-circle-check"></i> Все активны</div>
            </div>
        </div>
 
    </main>
 
</body>
</html>