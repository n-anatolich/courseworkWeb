<?php
require_once __DIR__ . '/../includes/db.php';

// Проверка прав
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Удаление задачи
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM problem_types WHERE id = ?")->execute([$id]);
    header("Location: /admin/problems.php");
    exit;
}

// Добавление новой задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $stmt = $pdo->prepare("INSERT INTO problem_types (category_id, name, description, formula_text, formula_expression, input_fields, output_fields) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['category_id'], trim($_POST['name']), trim($_POST['description']), 
        trim($_POST['formula_text']), trim($_POST['formula_expression']), 
        trim($_POST['input_fields']), trim($_POST['output_fields'])
    ]);
    header("Location: /admin/problems.php");
    exit;
}

// Получаем категории и задачи
$categories = $pdo->query("SELECT * FROM problem_categories ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$problems = $pdo->query("SELECT pt.*, pc.name as cat_name FROM problem_types pt JOIN problem_categories pc ON pt.category_id = pc.id ORDER BY pc.sort_order, pt.sort_order")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Управление типами задач</h2>
        <a href="/admin/index.php" class="btn btn-secondary">Назад в панель</a>
    </div>

    <div class="glass-panel" style="background: rgba(0,0,0,0.2); padding: 20px; margin-bottom: 30px;">
        <h3 style="margin-bottom: 15px;">Добавить новую задачу</h3>
        <form method="POST" action="/admin/problems.php" class="form-modern">
            <input type="hidden" name="action" value="add">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Категория</label>
                    <select name="category_id" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px;">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название задачи</label>
                    <input type="text" name="name" required placeholder="Например: Сила упругости">
                </div>
                <div class="form-group">
                    <label>Описание (формула текстом)</label>
                    <input type="text" name="description" required placeholder="F = k·x">
                </div>
                <div class="form-group">
                    <label>Формула MathJax (без скобок \( \))</label>
                    <input type="text" name="formula_text" required placeholder="F = k \cdot x">
                </div>
            </div>

            <div class="form-group">
                <label>JSON: Input fields (Входные данные)</label>
                <textarea name="input_fields" required rows="3" placeholder='{"fields":[{"name":"k","label":"Жесткость","unit":"Н/м","required":true}]}'></textarea>
            </div>
            
            <div class="form-group">
                <label>JSON: Output fields (Результат)</label>
                <textarea name="output_fields" required rows="3" placeholder='{"fields":[{"name":"F","label":"Сила","unit":"Н"}]}'></textarea>
            </div>
            
            <div class="form-group">
                <label>JSON: Formula expression (Внутренние переменные)</label>
                <input type="text" name="formula_expression" required placeholder='{"F":"k*x"}'>
            </div>

            <button type="submit" class="btn btn-primary">Добавить задачу</button>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px;">Внимание: Для добавления задачи также потребуется дописать её математическую логику в <code>PhysicsCalculator.php</code></p>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Категория</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problems as $p): ?>
                    <tr>
                        <td style="color: var(--text-secondary);"><?= $p['id'] ?></td>
                        <td><span style="background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($p['name']) ?></td>
                        <td style="font-family: monospace; color: #a5b4fc;"><?= htmlspecialchars($p['description']) ?></td>
                        <td style="text-align: right;">
                            <a href="/admin/problems.php?delete=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="return confirm('Точно удалить эту задачу?');">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>