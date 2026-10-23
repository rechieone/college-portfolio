<?php
require_once 'login.php';

$error = null;

try {
    $pdo = new PDO($attr, $user, $pass, $opts);

    try {

    // --- НОВОЕ: добавление размещения (раньше этого обработчика не было) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_placement'])) {
        $add_specie = (int)$_POST['id_specie'];
        $add_room   = (int)$_POST['id_room'];
        if ($add_specie > 0 && $add_room > 0) {
            $stmt = $pdo->prepare("INSERT INTO animal_placement (id_species, id_room) VALUES (?, ?)");
            $stmt->execute([$add_specie, $add_room]);
            header("Location: animals.php");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_food_submit'])) {
    $species_id = (int)$_POST['species_id_food'];
    $new_food = (float)$_POST['new_daily_food'];
    if ($species_id > 0 && $new_food > 0) {
        $stmt_food = $pdo->prepare("UPDATE animal_species SET daily_food = ? WHERE id_specie = ?");
        $stmt_food->execute([$new_food, $species_id]);
        header("Location: animals.php");
        exit;
    }
}


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_placement'])) {
        $specie_id = (int)$_POST['edit_specie_id'];
        $old_room_id = (int)$_POST['old_room_id'];
        $new_room_id = (int)$_POST['new_room_id'];

        if ($specie_id > 0 && $new_room_id > 0) {
            $stmt = $pdo->prepare("UPDATE animal_placement SET id_room = ? WHERE id_species = ? AND id_room = ?");
            $stmt->execute([$new_room_id, $specie_id, $old_room_id]);
            header("Location: animals.php");
            exit;
        }
    }

    if (isset($_GET['delete_specie_id']) && isset($_GET['delete_room_id'])) {
        $s_id = (int)$_GET['delete_specie_id'];
        $r_id = (int)$_GET['delete_room_id'];
        $stmt = $pdo->prepare("DELETE FROM animal_placement WHERE id_species = ? AND id_room = ?");
        $stmt->execute([$s_id, $r_id]);
        header("Location: animals.php");
        exit;
    }

    } catch (PDOException $e) {
        // 45000 = сообщения наших триггеров (SIGNAL): показываем их на странице
        if ($e->getCode() === '45000') {
            $error = $e->errorInfo[2] ?? $e->getMessage();
        } elseif ($e->getCode() === '23000') {
            $error = 'Такое размещение уже существует или данные ссылаются на несуществующую запись.';
        } else {
            throw $e;
        }
    }

    $edit_row = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        $s_id = (int)$_GET['specie_id'];
        $r_id = (int)$_GET['room_id'];

        $stmt = $pdo->prepare("SELECT p.*, s.species_name FROM animal_placement p JOIN animal_species s ON p.id_species = s.id_specie WHERE p.id_species = ? AND p.id_room = ?");
        $stmt->execute([$s_id, $r_id]);
        $edit_row = $stmt->fetch();
    }

    $species = $pdo->query("SELECT * FROM animal_species ORDER BY species_name")->fetchAll();
    $rooms   = $pdo->query("SELECT * FROM rooms ORDER BY room_number")->fetchAll();

    $query = "SELECT p.id_species, p.id_room, s.species_name, s.family, s.continent, s.daily_food, r.room_number 
              FROM animal_placement p
              JOIN animal_species s ON p.id_species = s.id_specie
              JOIN rooms r ON p.id_room = r.id_room
              ORDER BY s.species_name ASC";
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
    <title>Размещение животных</title>
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
            --purple:    #534AB7;
            --purple-bg: #EEEDFE;
            --teal:      #0F6E56;
            --teal-bg:   #E1F5EE;
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
        .icon-green  { background: var(--green-bg);  } .icon-green i  { color: var(--green);  }
        .icon-blue   { background: var(--blue-bg);   } .icon-blue i   { color: var(--blue);   }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-1);
        }
        .card-subtitle { font-size: 13px; color: var(--text-3); margin-top: 2px; }

        /* ── Form ── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .field-group { display: flex; flex-direction: column; gap: 5px; }
        .field-label { font-size: 12px; font-weight: 500; color: var(--text-2); }
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
            cursor: pointer;
        }
        .field-input:focus { border-color: var(--green-mid); }
        .field-input.blue:focus { border-color: var(--blue-mid); }

        .form-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

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

        /* ── Edit animal info banner ── */
        .animal-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: var(--blue-bg);
            border: 1px solid #B5D4F4;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
        }
        .animal-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .animal-avatar i { font-size: 20px; color: #B5D4F4; }
        .animal-name { font-size: 15px; font-weight: 500; color: var(--blue); }
        .animal-hint { font-size: 12.5px; color: var(--text-2); margin-top: 2px; }

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

        .species-name {
            font-weight: 500;
            color: var(--text-1);
        }

        .tag {
            display: inline-block;
            font-size: 12px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .tag-family   { background: var(--purple-bg); color: var(--purple); }
        .tag-continent { background: var(--amber-bg);  color: var(--amber);  }
        .tag-room     { background: var(--green-bg);  color: var(--green);  }
        .tag-food     { background: #FEF3E2;           color: #92400E;       }

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
        .btn-edit-row   { background: var(--blue-bg); color: var(--blue); margin-right: 6px; }
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
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="eyebrow">Административная система</div>
    <h1 class="site-title">Зоопарк <em>Admin</em></h1>
    <p class="site-sub">Размещение животных</p>
</header>

<nav>
    <a href="index.php"     class="nav-btn"><i class="ti ti-layout-dashboard"></i> Главная</a>
    <a href="complexes.php" class="nav-btn"><i class="ti ti-building-estate"></i> Комплексы</a>
    <a href="rooms.php"     class="nav-btn"><i class="ti ti-door"></i> Вольеры</a>
    <a href="animals.php"   class="nav-btn active"><i class="ti ti-paw"></i> Животные</a>
</nav>

<main class="content">

    <?php if ($error): ?>
    <div style="background: var(--red-bg); color: var(--red); border: 1px solid #F7C1C1; border-radius: var(--radius-md); padding: 14px 18px; font-size: 14px;">
        <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($edit_row): ?>
    <!-- Форма переселения -->
    <div class="card card-edit">
        <div class="card-header">
            <div class="card-icon icon-blue"><i class="ti ti-replace"></i></div>
            <div>
                <div class="card-title">Переселить животное</div>
                <div class="card-subtitle">Выберите новый вольер для размещения</div>
            </div>
        </div>
        <div class="animal-banner">
            <div class="animal-avatar"><i class="ti ti-paw"></i></div>
            <div>
                <div class="animal-name"><?= htmlspecialchars($edit_row['species_name']) ?></div>
                <div class="animal-hint">Текущий вольер: <?= htmlspecialchars($edit_row['id_room']) ?> — выберите новый ниже</div>
            </div>
        </div>
        <form method="POST" action="animals.php">
            <input type="hidden" name="edit_specie_id" value="<?= $edit_row['id_species'] ?>">
            <input type="hidden" name="old_room_id"    value="<?= $edit_row['id_room'] ?>">
            <div class="form-grid">
                <div class="field-group">
                    <label class="field-label">Новый вольер</label>
                    <select class="field-input blue" name="new_room_id" required>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id_room'] ?>" <?= $r['id_room'] == $edit_row['id_room'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['room_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="edit_placement" class="btn-submit btn-submit-blue">
                    <i class="ti ti-replace"></i> Переселить
                </button>
                <a href="animals.php" class="btn-cancel">
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
                <div class="card-title">Расселить животное</div>
                <div class="card-subtitle">Назначьте вид животного в вольер</div>
            </div>
        </div>
        <form method="POST" action="animals.php">
            <div class="form-grid">
                <div class="field-group">
                    <label class="field-label">Вид животного</label>
                    <select class="field-input" name="id_specie" required>
                        <option value="">— Выберите животное —</option>
                        <?php foreach ($species as $s): ?>
                            <option value="<?= $s['id_specie'] ?>">
                                <?= htmlspecialchars($s['species_name']) ?> (<?= htmlspecialchars($s['family']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Вольер</label>
                    <select class="field-input" name="id_room" required>
                        <option value="">— Выберите вольер —</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id_room'] ?>"><?= htmlspecialchars($r['room_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_placement" class="btn-submit">
                    <i class="ti ti-plus"></i> Назначить вольер
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon icon-green"><i class="ti ti-paw"></i></div>
            <div>
                <div class="card-title">Текущее размещение</div>
                <div class="card-subtitle">Все животные и их вольеры</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Животное</th>
                        <th>Семейство</th>
                        <th>Континент</th>
                        <th>Корм/сутки</th>
                        <th>Вольер</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $has_rows = false; while ($row = $result->fetch()): $has_rows = true; ?>
                        <tr>
                            <td><span class="species-name"><?= htmlspecialchars($row['species_name']) ?></span></td>
                            <td><span class="tag tag-family"><?= htmlspecialchars($row['family']) ?></span></td>
                            <td><span class="tag tag-continent"><?= htmlspecialchars($row['continent']) ?></span></td>
                      <!-- Колонки Континент и Форма изменения корма -->
                    <td>
                        <form method="POST" action="" style="display: flex; align-items: center; gap: 6px;">
                            <input type="hidden" name="species_id_food" value="<?= htmlspecialchars($row['id_species']) ?>">
                            <div style="position: relative; display: inline-block;">
                                <input type="number" name="new_daily_food" value="<?= htmlspecialchars($row['daily_food']) ?>" step="0.01" min="0.01" required style="width: 75px; padding: 4px 20px 4px 6px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 13.5px; background: var(--bg); color: var(--text-1);">
                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 11px; color: var(--text-3); font-weight: 500;">кг</span>
                            </div>
                            <button type="submit" name="update_food_submit" class="action-btn" style="background: var(--green-bg); color: var(--green); padding: 4px 8px; border: none; border-radius: var(--radius-sm);">
                                <i class="ti ti-device-floppy" style="font-size: 15px;"></i>
                            </button>
                        </form>
                    </td>

                            <td><span class="tag tag-room"><i class="ti ti-door" style="font-size:12px;vertical-align:-1px;margin-right:3px"></i><?= htmlspecialchars($row['room_number']) ?></span></td>
                            <td>
                                <a href="animals.php?action=edit&specie_id=<?= $row['id_species'] ?>&room_id=<?= $row['id_room'] ?>" class="action-btn btn-edit-row">
                                    <i class="ti ti-replace"></i> Переселить
                                </a>
                                <a href="animals.php?delete_specie_id=<?= $row['id_species'] ?>&delete_room_id=<?= $row['id_room'] ?>"
                                   class="action-btn btn-delete-row"
                                   onclick="return confirm('Убрать «<?= htmlspecialchars($row['species_name']) ?>» из вольера?')">
                                    <i class="ti ti-trash"></i> Убрать
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_rows): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="ti ti-paw-off"></i>
                                    Животные ещё не размещены. Назначьте первого!
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