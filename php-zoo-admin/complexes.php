<?php
require_once 'login.php';

try {
    $pdo = new PDO($attr, $user, $pass, $opts);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_complex'])) {
        $custom_id = (int)$_POST['custom_id'];
        $name = trim($_POST['complex_name']);
        
        if (!empty($name) && $custom_id > 0) {
            $check = $pdo->prepare("SELECT id_complexes FROM complexes WHERE id_complexes = ?");
            $check->execute([$custom_id]);
            
            if ($check->fetch()) {
                $error = 'Комплекс с таким ID уже существует!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO complexes (id_complexes, complex_name) VALUES (?, ?)");
                $stmt->execute([$custom_id, $name]);
                header("Location: complexes.php");
                exit;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_complex'])) {
        $edit_id = (int)$_POST['edit_id'];
        $new_name = trim($_POST['new_complex_name']);
        
        if (!empty($new_name) && $edit_id > 0) {
            $stmt = $pdo->prepare("UPDATE complexes SET complex_name = ? WHERE id_complexes = ?");
            $stmt->execute([$new_name, $edit_id]);
            header("Location: complexes.php");
            exit;
        }
    }

    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM complexes WHERE id_complexes = ?");
        $stmt->execute([$delete_id]);
        header("Location: complexes.php");
        exit;
    }

    $edit_row = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM complexes WHERE id_complexes = ?");
        $stmt->execute([$edit_id]);
        $edit_row = $stmt->fetch();
    }

    $query = "SELECT * FROM complexes ORDER BY id_complexes ASC";
    $result = $pdo->query($query);

    // Вызов процедуры show_animals_in_complex при клике на комплекс (toggle)
    $selected_complex = null;
    $animals_in_complex = null;
    if (isset($_GET['show_complex']) && $_GET['show_complex'] !== '') {
        $selected_complex = trim($_GET['show_complex']);
        $stmt = $pdo->prepare("CALL show_animals_in_complex(?)");
        $stmt->execute([$selected_complex]);
        $animals_in_complex = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    die("Ошибка СУБД: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление комплексами</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #F5F3EE;
            --surface:   #FFFFFF;
            --border:    #E2DDD6;
            --text-1:    #1A1A18;
            --text-2:    #5C5A54;
            --text-3:    #9A9891;
            --green:     #0F6E56;
            --green-bg:  #E1F5EE;
            --green-mid: #1D9E75;
            --blue:      #185FA5;
            --blue-bg:   #E6F1FB;
            --blue-mid:  #378ADD;
            --red:       #A32D2D;
            --red-bg:    #FCEBEB;
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
        .site-title em { color: var(--green); font-style: normal; }
        .site-sub { font-size: 14px; color: var(--text-2); }

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

        /* ── Content ── */
        .content {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px 28px;
        }
        .card.card-edit {
            border-color: var(--blue-mid);
            border-width: 1.5px;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .card-icon i { font-size: 20px; }
        .icon-green { background: var(--green-bg); } .icon-green i { color: var(--green); }
        .icon-blue  { background: var(--blue-bg);  } .icon-blue  i { color: var(--blue);  }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-1);
        }
        .card-subtitle {
            font-size: 13px;
            color: var(--text-3);
            margin-top: 2px;
        }

        /* ── Error alert ── */
        .alert-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--red-bg);
            border: 1px solid #F7C1C1;
            border-radius: var(--radius-sm);
            color: var(--red);
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 18px;
        }
        .alert-error i { font-size: 17px; }

        /* ── Form ── */
        .form-row {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .field-group { display: flex; flex-direction: column; gap: 5px; }
        .field-label { font-size: 12px; font-weight: 500; color: var(--text-2); }
        .input-id {
            padding: 10px 12px;
            width: 90px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-1);
            background: var(--bg);
            outline: none;
            transition: border-color 0.15s;
        }
        .input-name {
            padding: 10px 14px;
            width: 340px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-1);
            background: var(--bg);
            outline: none;
            transition: border-color 0.15s;
        }
        .input-id:focus, .input-name:focus {
            border-color: var(--green-mid);
        }
        .input-name-edit {
            padding: 10px 14px;
            width: 380px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-1);
            background: var(--bg);
            outline: none;
            transition: border-color 0.15s;
        }
        .input-name-edit:focus { border-color: var(--blue-mid); }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            background: var(--green);
            color: #A8E8D0;
            border: none;
            border-radius: 40px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, transform 0.15s;
            align-self: flex-end;
        }
        .btn-submit:hover { background: #0D5E49; transform: translateY(-1px); }
        .btn-submit i { font-size: 16px; }

        .btn-submit-blue {
            background: var(--blue);
            color: #B5D4F4;
        }
        .btn-submit-blue:hover { background: #144F8A; }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            background: var(--surface);
            color: var(--text-2);
            border: 1px solid var(--border);
            border-radius: 40px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s;
            align-self: flex-end;
        }
        .btn-cancel:hover { background: #F0EDE8; }
        .btn-cancel i { font-size: 16px; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            border-bottom: 1.5px solid var(--border);
        }
        th {
            padding: 10px 14px;
            text-align: left;
            font-size: 11.5px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-3);
        }
        th:last-child { text-align: right; }
        td {
            padding: 14px 14px;
            font-size: 14px;
            color: var(--text-1);
            border-bottom: 1px solid var(--border);
        }
        td:last-child { text-align: right; white-space: nowrap; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.1s; }
        tbody tr:hover { background: #FAFAF8; }

        .id-badge {
            display: inline-block;
            padding: 3px 9px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-2);
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, transform 0.12s;
            cursor: pointer;
        }
        .action-btn i { font-size: 14px; }
        .btn-edit-row {
            background: var(--blue-bg);
            color: var(--blue);
            margin-right: 6px; 
        }
        .btn-edit-row:hover { background: #C8DFF5; transform: translateY(-1px); }
        .btn-delete-row {
            background: var(--red-bg);
            color: var(--red);
            border: none;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-delete-row:hover { background: #F7C1C1; transform: translateY(-1px); }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-3);
            font-size: 14px;
        }
        .empty-state i { font-size: 32px; display: block; margin-bottom: 8px; }

        /* ── Кликабельное название комплекса ── */
        .complex-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-1);
            text-decoration: none;
            font-weight: 500;
            padding: 3px 0;
            border-bottom: 1.5px dashed var(--border);
            transition: color 0.15s, border-color 0.15s;
        }
        .complex-link:hover {
            color: var(--green);
            border-color: var(--green-mid);
        }
        .complex-link.active-complex {
            color: var(--green);
            border-color: var(--green);
            border-style: solid;
        }
        .complex-link i { font-size: 14px; }

        /* ── Блок с животными комплекса ── */
        .animals-result-card {
            background: var(--surface);
            border: 1.5px solid var(--green-mid);
            border-radius: var(--radius-lg);
            padding: 24px 28px;
        }
        .animals-result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .result-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: var(--radius-sm);
            background: var(--green-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .result-icon i { font-size: 19px; color: var(--green); }
        .result-title {
            font-family: 'Playfair Display', serif;
            font-size: 17px;
            font-weight: 600;
            color: var(--text-1);
        }
        .result-subtitle { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }
        .result-count {
            margin-left: auto;
            font-size: 12px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-bg);
            padding: 3px 10px;
            border-radius: 20px;
        }
        .result-table { width: 100%; border-collapse: collapse; }
        .result-table thead tr { border-bottom: 1.5px solid var(--border); }
        .result-table th {
            padding: 9px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-3);
        }
        .result-table td {
            padding: 12px 12px;
            font-size: 14px;
            color: var(--text-1);
            border-bottom: 1px solid var(--border);
        }
        .result-table tbody tr:last-child td { border-bottom: none; }
        .result-table tbody tr:hover { background: #FAFAF8; }
        .tag-room-green {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-bg);
            padding: 3px 9px;
            border-radius: 20px;
        }
        .tag-room-green i { font-size: 12px; }
        .empty-animals {
            text-align: center;
            padding: 28px;
            color: var(--text-3);
            font-size: 13.5px;
        }
        .empty-animals i { font-size: 28px; display: block; margin-bottom: 6px; }

        @media (max-width: 600px) {
            .form-row { flex-direction: column; align-items: flex-start; }
            .input-name, .input-name-edit { width: 100%; }
            .site-title { font-size: 26px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="eyebrow">Административная система</div>
    <h1 class="site-title">Зоопарк <em>Admin</em></h1>
    <p class="site-sub">Управление комплексами</p>
</header>

<nav>
    <a href="index.php"     class="nav-btn"><i class="ti ti-layout-dashboard"></i> Главная</a>
    <a href="complexes.php" class="nav-btn active"><i class="ti ti-building-estate"></i> Комплексы</a>
    <a href="rooms.php"     class="nav-btn"><i class="ti ti-door"></i> Вольеры</a>
    <a href="animals.php" class="nav-btn"><i class="ti ti-paw"></i> Животные</a>
</nav>

<main class="content">

    <?php if (isset($error)): ?>
        <div class="alert-error">
            <i class="ti ti-alert-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($edit_row): ?>
        <!-- Форма редактирования -->
        <div class="card card-edit">
            <div class="card-header">
                <div class="card-icon icon-blue"><i class="ti ti-edit"></i></div>
                <div>
                    <div class="card-title">Редактировать комплекс</div>
                    <div class="card-subtitle">ID: <?= htmlspecialchars($edit_row['id_complexes']) ?></div>
                </div>
            </div>
            <form method="POST" action="complexes.php">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_row['id_complexes']) ?>">
                <div class="form-row">
                    <div class="field-group">
                        <label class="field-label">Название комплекса</label>
                        <input class="input-name-edit" type="text" name="new_complex_name"
                               value="<?= htmlspecialchars($edit_row['complex_name']) ?>" required>
                    </div>
                    <button type="submit" name="edit_complex" class="btn-submit btn-submit-blue">
                        <i class="ti ti-device-floppy"></i> Сохранить
                    </button>
                    <a href="complexes.php" class="btn-cancel">
                        <i class="ti ti-x"></i> Отмена
                    </a>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- Форма добавления -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon icon-green"><i class="ti ti-plus"></i></div>
                <div>
                    <div class="card-title">Добавить комплекс</div>
                    <div class="card-subtitle">Укажите ID и название нового комплекса</div>
                </div>
            </div>
            <form method="POST" action="complexes.php">
                <div class="form-row">
                    <div class="field-group">
                        <label class="field-label">ID</label>
                        <input class="input-id" type="number" name="custom_id" placeholder="1" min="1" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Название</label>
                        <input class="input-name" type="text" name="complex_name" placeholder="Например: Террариум" required>
                    </div>
                    <button type="submit" name="add_complex" class="btn-submit">
                        <i class="ti ti-plus"></i> Добавить
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon icon-green"><i class="ti ti-building-community"></i></div>
            <div>
                <div class="card-title">Список комплексов</div>
                <div class="card-subtitle">Все записи из базы данных</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название комплекса</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $has_rows = false; while ($row = $result->fetch()): $has_rows = true; ?>
                        <tr>
                            <td><span class="id-badge"><?= htmlspecialchars($row['id_complexes']) ?></span></td>
                            <td>
                                <?php
                                    $is_active = ($selected_complex === $row['complex_name']);
                                    $href = $is_active
                                        ? 'complexes.php'
                                        : 'complexes.php?show_complex=' . urlencode($row['complex_name']);
                                ?>
                                <a href="<?= $href ?>"
                                   class="complex-link <?= $is_active ? 'active-complex' : '' ?>">
                                    <i class="ti ti-<?= $is_active ? 'chevron-down' : 'chevron-right' ?>"></i>
                                    <?= htmlspecialchars($row['complex_name']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="complexes.php?action=edit&id=<?= $row['id_complexes'] ?>" class="action-btn btn-edit-row">
                                    <i class="ti ti-edit"></i> Редактировать
                                </a>
                                <a href="complexes.php?delete_id=<?= $row['id_complexes'] ?>"
                                   class="action-btn btn-delete-row"
                                   onclick="return confirm('Удалить комплекс «<?= htmlspecialchars($row['complex_name']) ?>»?')">
                                    <i class="ti ti-trash"></i> Удалить
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_rows): ?>
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <i class="ti ti-building-off"></i>
                                    Комплексов пока нет. Добавьте первый!
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Блок животных выбранного комплекса -->
    <?php if ($animals_in_complex !== null): ?>
    <div class="animals-result-card">
        <div class="animals-result-header">
            <div class="result-icon"><i class="ti ti-paw"></i></div>
            <div>
                <div class="result-title">Животные: <?= htmlspecialchars($selected_complex) ?></div>
            </div>
            <span class="result-count"><?= count($animals_in_complex) ?> записей</span>
        </div>

        <?php if (!empty($animals_in_complex)): ?>
        <table class="result-table">
            <thead>
                <tr>
                    <th>Вид животного</th>
                    <th>Вольер</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($animals_in_complex as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['species_name']) ?></td>
                        <td>
                            <span class="tag-room-green">
                                <i class="ti ti-door"></i>
                                <?= htmlspecialchars($a['room_number']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-animals">
                <i class="ti ti-paw-off"></i>
                В комплексе «<?= htmlspecialchars($selected_complex) ?>» пока нет животных
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

</body>
</html>