<?php
require_once 'login.php';

try {
    $pdo = new PDO($attr, $user, $pass, $opts);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
        $custom_id = (int)$_POST['custom_id'];
        $number = trim($_POST['room_number']);
        $complex_id = (int)$_POST['id_complexes'];
        $has_water = isset($_POST['has_water']) ? 1 : 0;
        $has_heating = isset($_POST['has_heating']) ? 1 : 0;
        $total_animals = (int)$_POST['total_animals'];

        if (!empty($number) && $complex_id > 0 && $custom_id > 0) {
            $check = $pdo->prepare("SELECT id_room FROM rooms WHERE id_room = ?");
            $check->execute([$custom_id]);

            if ($check->fetch()) {
                $error = 'Вольер с таким ID уже существует!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO rooms (id_room, room_number, has_water, has_heating, id_complexes, total_animals) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$custom_id, $number, $has_water, $has_heating, $complex_id, $total_animals]);
                header("Location: rooms.php");
                exit;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_room'])) {
        $edit_id = (int)$_POST['edit_id'];
        $number = trim($_POST['room_number']);
        $complex_id = (int)$_POST['id_complexes'];
        $has_water = isset($_POST['has_water']) ? 1 : 0;
        $has_heating = isset($_POST['has_heating']) ? 1 : 0;
        $total_animals = (int)$_POST['total_animals'];

        if (!empty($number) && $complex_id > 0 && $edit_id > 0) {
            $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, has_water = ?, has_heating = ?, id_complexes = ?, total_animals = ? WHERE id_room = ?");
            $stmt->execute([$number, $has_water, $has_heating, $complex_id, $total_animals, $edit_id]);
            header("Location: rooms.php");
            exit;
        }
    }

    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id_room = ?");
        $stmt->execute([$delete_id]);
        header("Location: rooms.php");
        exit;
    }

    $edit_row = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id_room = ?");
        $stmt->execute([$edit_id]);
        $edit_row = $stmt->fetch();
    }

    $complexes_res = $pdo->query("SELECT * FROM complexes ORDER BY complex_name");
    $complexes = $complexes_res->fetchAll();

    $query = "SELECT r.*, c.complex_name 
              FROM rooms r 
              LEFT JOIN complexes c ON r.id_complexes = c.id_complexes 
              ORDER BY r.id_room ASC";
    $result = $pdo->query($query);

} catch (PDOException $e) {
    die("Ошибка СУБД: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление вольерами</title>
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
            --amber:     #854F0B;
            --amber-bg:  #FAEEDA;
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

        .content {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
        }
        .card.card-edit { border-color: var(--blue-mid); border-width: 1.5px; }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
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
        .card-subtitle { font-size: 13px; color: var(--text-3); margin-top: 2px; }

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

        /* ── Form grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .field-group { display: flex; flex-direction: column; gap: 5px; }
        .field-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-2);
        }
        .field-input {
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-1);
            background: var(--bg);
            outline: none;
            transition: border-color 0.15s;
            width: 100%;
        }
        .field-input:focus { border-color: var(--green-mid); }
        .field-input.blue:focus { border-color: var(--blue-mid); }

        select.field-input { cursor: pointer; }

        /* ── Checkbox toggle ── */
        .toggle-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .toggle-label-top { font-size: 12px; font-weight: 500; color: var(--text-2); }
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            height: 42px;
        }
        .toggle-row:has(input:checked) {
            border-color: var(--green-mid);
            background: var(--green-bg);
        }
        .toggle-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--green);
            cursor: pointer;
            flex-shrink: 0;
        }
        .toggle-row span { font-size: 13.5px; color: var(--text-1); }

        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            background: var(--green);
            color: #A8E8D0;
            border: none;
            border-radius: 40px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, transform 0.15s;
        }
        .btn-submit:hover { background: #0D5E49; transform: translateY(-1px); }
        .btn-submit i { font-size: 16px; }
        .btn-submit-blue { background: var(--blue); color: #B5D4F4; }
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
        }
        .btn-cancel:hover { background: #F0EDE8; }
        .btn-cancel i { font-size: 16px; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { border-bottom: 1.5px solid var(--border); }
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
            padding: 13px 14px;
            font-size: 14px;
            color: var(--text-1);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
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

        .bool-yes {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-bg);
            padding: 3px 9px;
            border-radius: 20px;
        }
        .bool-no {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-3);
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 3px 9px;
            border-radius: 20px;
        }
        .bool-yes i, .bool-no i { font-size: 13px; }

        .complex-tag {
            display: inline-block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--amber);
            background: var(--amber-bg);
            padding: 3px 9px;
            border-radius: 20px;
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
        .btn-edit-row { background: var(--blue-bg); color: var(--blue); margin-right: 6px; }
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

        @media (max-width: 640px) {
            .site-title { font-size: 26px; }
            .form-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="eyebrow">Административная система</div>
    <h1 class="site-title">Зоопарк <em>Admin</em></h1>
    <p class="site-sub">Управление вольерами</p>
</header>

<nav>
    <a href="index.php"     class="nav-btn"><i class="ti ti-layout-dashboard"></i> Главная</a>
    <a href="complexes.php" class="nav-btn"><i class="ti ti-building-estate"></i> Комплексы</a>
    <a href="rooms.php"     class="nav-btn active"><i class="ti ti-door"></i> Вольеры</a>
    <a href="animals.php"   class="nav-btn"><i class="ti ti-paw"></i> Животные</a>
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
                <div class="card-title">Редактировать вольер</div>
                <div class="card-subtitle">ID: <?= htmlspecialchars($edit_row['id_room']) ?></div>
            </div>
        </div>
        <form method="POST" action="rooms.php">
            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_row['id_room']) ?>">
            <div class="form-grid">
                <div class="field-group">
                    <label class="field-label">Номер вольера</label>
                    <input class="field-input blue" type="text" name="room_number"
                           value="<?= htmlspecialchars($edit_row['room_number']) ?>" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Комплекс</label>
                    <select class="field-input blue" name="id_complexes" required>
                        <?php foreach ($complexes as $c): ?>
                            <option value="<?= $c['id_complexes'] ?>" <?= $c['id_complexes'] == $edit_row['id_complexes'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['complex_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
<label class="field-label">Кол-во животных</label>
<!-- Показываем текущее число из базы, но запрещаем его редактировать вручную -->
<input class="field-input blue" type="text" value="<?= htmlspecialchars($edit_row['total_animals']) ?>" disabled style="background-color: #e2e8f0; color: #94a3b8; cursor: not-allowed;">
<input type="hidden" name="total_animals" value="<?= htmlspecialchars($edit_row['total_animals']) ?>">
</div>

                <div class="toggle-group">
                    <span class="toggle-label-top">Водопровод</span>
                    <label class="toggle-row">
                        <input type="checkbox" name="has_water" value="1" <?= $edit_row['has_water'] ? 'checked' : '' ?>>
                        <span>Есть водопровод</span>
                    </label>
                </div>
                <div class="toggle-group">
                    <span class="toggle-label-top">Отопление</span>
                    <label class="toggle-row">
                        <input type="checkbox" name="has_heating" value="1" <?= $edit_row['has_heating'] ? 'checked' : '' ?>>
                        <span>Есть отопление</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="edit_room" class="btn-submit btn-submit-blue">
                    <i class="ti ti-device-floppy"></i> Сохранить
                </button>
                <a href="rooms.php" class="btn-cancel">
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
                <div class="card-title">Добавить вольер</div>
                <div class="card-subtitle">Заполните параметры нового вольера</div>
            </div>
        </div>
        <form method="POST" action="rooms.php">
            <div class="form-grid">
                <div class="field-group">
                    <label class="field-label">ID вольера</label>
                    <input class="field-input" type="number" name="custom_id" placeholder="Например, 6" min="1" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Номер вольера</label>
                    <input class="field-input" type="text" name="room_number" placeholder="Например, 501-X" required>
                </div>
                <div class="field-group">
                    <label class="field-label">Комплекс</label>
                    <select class="field-input" name="id_complexes" required>
                        <option value="">— Выберите комплекс —</option>
                        <?php foreach ($complexes as $c): ?>
                            <option value="<?= $c['id_complexes'] ?>"><?= htmlspecialchars($c['complex_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
<label class="field-label">Кол-во животных</label>
<!-- Поле визуально заблокировано (disabled), пользователь видит строго 0 -->
<input class="field-input" type="text" value="0" disabled style="background-color: #e2e8f0; color: #94a3b8; cursor: not-allowed;">
<!-- Скрытое поле, которое безопасно отправит нужный 0 в PHP-обработчик базы данных -->
<input type="hidden" name="total_animals" value="0">
</div>

                <div class="toggle-group">
                    <span class="toggle-label-top">Водопровод</span>
                    <label class="toggle-row">
                        <input type="checkbox" name="has_water" value="1">
                        <span>Есть водопровод</span>
                    </label>
                </div>
                <div class="toggle-group">
                    <span class="toggle-label-top">Отопление</span>
                    <label class="toggle-row">
                        <input type="checkbox" name="has_heating" value="1">
                        <span>Есть отопление</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_room" class="btn-submit">
                    <i class="ti ti-plus"></i> Добавить вольер
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon icon-green"><i class="ti ti-door"></i></div>
            <div>
                <div class="card-title">Список вольеров</div>
                <div class="card-subtitle">Все записи из базы данных</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер</th>
                        <th>Комплекс</th>
                        <th>Вода</th>
                        <th>Отопление</th>
                        <th>Животных</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $has_rows = false; while ($row = $result->fetch()): $has_rows = true; ?>
                        <tr>
                            <td><span class="id-badge"><?= htmlspecialchars($row['id_room']) ?></span></td>
                            <td><?= htmlspecialchars($row['room_number']) ?></td>
                            <td>
                                <span class="complex-tag"><?= htmlspecialchars($row['complex_name'] ?? 'Не указан') ?></span>
                            </td>
                            <td>
                                <?php if ($row['has_water']): ?>
                                    <span class="bool-yes"><i class="ti ti-check"></i> Есть</span>
                                <?php else: ?>
                                    <span class="bool-no"><i class="ti ti-minus"></i> Нет</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['has_heating']): ?>
                                    <span class="bool-yes"><i class="ti ti-check"></i> Есть</span>
                                <?php else: ?>
                                    <span class="bool-no"><i class="ti ti-minus"></i> Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['total_animals']) ?></td>
                            <td>
                                <a href="rooms.php?action=edit&id=<?= $row['id_room'] ?>" class="action-btn btn-edit-row">
                                    <i class="ti ti-edit"></i> Редактировать
                                </a>
                                <a href="rooms.php?delete_id=<?= $row['id_room'] ?>"
                                   class="action-btn btn-delete-row"
                                   onclick="return confirm('Удалить вольер №<?= htmlspecialchars($row['room_number']) ?>?')">
                                    <i class="ti ti-trash"></i> Удалить
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_rows): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="ti ti-door-off"></i>
                                    Вольеров пока нет. Добавьте первый!
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</body>
</html>
