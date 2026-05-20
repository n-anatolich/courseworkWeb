<?php
require_once __DIR__ . '/../includes/db.php';

// Проверка прав (только админ)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$error = '';
$msg = $_GET['msg'] ?? '';

// --- ШАГ 5.1 и 5.2: Обработка добавления и редактирования с валидацией JSON ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $id = $_POST['id'] ?? null;
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $formula_text = trim($_POST['formula_text']);
    $formula_expression = trim($_POST['formula_expression']);
    $input_fields = trim($_POST['input_fields']);
    $output_fields = trim($_POST['output_fields']);
    
    // Функция проверки валидности JSON
    function is_valid_json($string) {
        if (empty($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    if (!is_valid_json($formula_expression) || !is_valid_json($input_fields) || !is_valid_json($output_fields)) {
        $error = "Ошибка сохранения: Поля 'Математическое выражение', 'Поля ввода' и 'Поля вывода' должны содержать строгий и валидный JSON!";
    } else {
        if ($id) {
            // Обновление существующей задачи
            $stmt = $pdo->prepare("UPDATE problem_types SET category_id=?, name=?, description=?, formula_text=?, formula_expression=?, input_fields=?, output_fields=? WHERE id=?");
            $stmt->execute([$category_id, $name, $description, $formula_text, $formula_expression, $input_fields, $output_fields, $id]);
            header("Location: /admin/problems.php?msg=updated");
            exit;
        } else {
            // Добавление новой задачи (вычисляем порядок сортировки)
            $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM problem_types WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $sort_order = (int)$stmt->fetchColumn() + 10;

            $stmt = $pdo->prepare("INSERT INTO problem_types (category_id, name, description, formula_text, formula_expression, input_fields, output_fields, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $formula_text, $formula_expression, $input_fields, $output_fields, $sort_order]);
            header("Location: /admin/problems.php?msg=added");
            exit;
        }
    }
}

// --- Обработка удаления ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM problem_types WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: /admin/problems.php?msg=deleted");
    exit;
}

// Получение данных для отрисовки интерфейса
$categories = $pdo->query("SELECT * FROM problem_categories ORDER BY sort_order")->fetchAll();
$problems = $pdo->query("
    SELECT pt.*, pc.name as cat_name 
    FROM problem_types pt 
    JOIN problem_categories pc ON pt.category_id = pc.id 
    ORDER BY pc.sort_order, pt.sort_order
")->fetchAll();

// Загрузка данных для режима редактирования
$editData = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM problem_types WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $editData = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <h2>Управление системными задачами</h2>
        <a href="/admin/index.php" class="btn btn-secondary">Назад в панель</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success">Новая задача успешно добавлена в базу!</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success">Задача успешно обновлена!</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success">Задача удалена.</div>
    <?php endif; ?>

    <div style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 12px; border: 1px solid var(--glass-border); margin-bottom: 40px;">
        <h3 style="margin-bottom: 20px; color: var(--primary-color);">
            <?= $editData ? 'Редактировать задачу ID: ' . $editData['id'] : 'Добавить новую задачу' ?>
        </h3>
        
        <form method="POST" action="/admin/problems.php" class="form-modern">
            <input type="hidden" name="action" value="save">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label>Категория (Раздел физики)</label>
                    <select name="category_id" required style="width: 100%; padding: 12px; background: #000; color: #fff; border: 1px solid #3f3f46; border-radius: 8px;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editData && $editData['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название задачи</label>
                    <input type="text" name="name" required value="<?= $editData ? htmlspecialchars($editData['name']) : '' ?>" placeholder="Например: Второй закон Ньютона">
                </div>
            </div>

            <div class="form-group">
                <label>Описание задачи</label>
                <input type="text" name="description" required value="<?= $editData ? htmlspecialchars($editData['description']) : '' ?>" placeholder="Краткая суть того, что считает эта формула">
            </div>

            <div class="form-group">
                <label>Формула для отображения (MathJax/LaTeX)</label>
                <input type="text" name="formula_text" required value="<?= $editData ? htmlspecialchars($editData['formula_text']) : '' ?>" placeholder="Например: F = m \cdot a">
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label>Математическое выражение (JSON)</label>
                    <textarea name="formula_expression" required rows="4" style="width: 100%; padding: 12px; background: #000; color: var(--primary-color); border: 1px solid #3f3f46; border-radius: 8px; font-family: monospace;" placeholder='{"F": "m*a", "m": "F/a", "a": "F/m"}'><?= $editData ? htmlspecialchars($editData['formula_expression']) : '' ?></textarea>
                    <small style="color: var(--text-secondary);">Укажите все вариации формул.</small>
                </div>
                <div class="form-group">
                    <label>Поля ввода (JSON)</label>
                    <textarea name="input_fields" required rows="4" style="width: 100%; padding: 12px; background: #000; color: #6ee7b7; border: 1px solid #3f3f46; border-radius: 8px; font-family: monospace;" placeholder='{"fields": [{"name":"m", "label":"Масса", "unit":"кг", "required":true}]}'><?= $editData ? htmlspecialchars($editData['input_fields']) : '' ?></textarea>
                </div>
                <div class="form-group">
                    <label>Поля вывода (JSON)</label>
                    <textarea name="output_fields" required rows="4" style="width: 100%; padding: 12px; background: #000; color: #6ee7b7; border: 1px solid #3f3f46; border-radius: 8px; font-family: monospace;" placeholder='{"fields": [{"name":"F", "label":"Сила", "unit":"Н"}]}'><?= $editData ? htmlspecialchars($editData['output_fields']) : '' ?></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary"><?= $editData ? 'Сохранить изменения' : 'Добавить задачу' ?></button>
                <?php if ($editData): ?>
                    <a href="/admin/problems.php" class="btn btn-secondary">Отменить</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h3>Существующие задачи в базе</h3>
    <div style="overflow-x: auto;">
        <table class="glass-table" style="min-width: 900px; margin-top: 15px;">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Категория</th>
                    <th>Название</th>
                    <th>Выражения парсера</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($problems as $p): ?>
                    <tr>
                        <td style="color: var(--text-secondary);">#<?= $p['id'] ?></td>
                        <td><span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                        <td style="font-weight: 500; color: #fff;"><?= htmlspecialchars($p['name']) ?></td>
                        <td>
                            <?php 
                                $expr = json_decode($p['formula_expression'], true);
                                if (is_array($expr)) {
                                    foreach ($expr as $k => $v) {
                                        echo "<code style='margin-right: 5px;'>{$k}={$v}</code>";
                                    }
                                } else {
                                    echo "<span style='color: #ef4444;'>Ошибка JSON</span>";
                                }
                            ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="/admin/problems.php?edit_id=<?= $p['id'] ?>" class="btn" style="background: transparent; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 4px 10px; font-size: 0.85rem; margin-right: 5px;">Изменить</a>
                            
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Удалить задачу? Это приведет к удалению всех пользовательских задач, связанных с ней!');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 4px 10px; font-size: 0.85rem;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
