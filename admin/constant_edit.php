<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';

// Массив по умолчанию для новой записи
$c = [
    'name' => '',
    'symbol' => '',
    'value' => '',
    'unit' => '',
    'description' => ''
];

// Если есть ID, загружаем данные из БД
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM constants WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $c = $fetched;
    } else {
        header("Location: /admin/constants.php");
        exit;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $c['name'] = trim($_POST['name'] ?? '');
    $c['symbol'] = trim($_POST['symbol'] ?? '');
    $c['value'] = str_replace(',', '.', trim($_POST['value'] ?? '')); // Замена запятой на точку
    $c['unit'] = trim($_POST['unit'] ?? '');
    $c['description'] = trim($_POST['description'] ?? '');

    // Валидация
    if (empty($c['name']) || empty($c['symbol']) || $c['value'] === '' || empty($c['unit'])) {
        $error = "Пожалуйста, заполните все обязательные поля.";
    } elseif (!is_numeric($c['value'])) {
        $error = "Поле 'Значение' должно быть числом.";
    } else {
        if ($id) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE constants SET name = ?, symbol = ?, value = ?, unit = ?, description = ? WHERE id = ?");
            $stmt->execute([$c['name'], $c['symbol'], $c['value'], $c['unit'], $c['description'], $id]);
        } else {
            // Добавление
            $stmt = $pdo->prepare("INSERT INTO constants (name, symbol, value, unit, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$c['name'], $c['symbol'], $c['value'], $c['unit'], $c['description']]);
        }
        header("Location: /admin/constants.php?msg=saved");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card glass-panel" style="max-width: 600px;">
        <h2 class="auth-title"><?= $id ? 'Редактирование' : 'Новая константа' ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/admin/constant_edit.php<?= $id ? '?id='.$id : '' ?>" class="form-modern">
            <div class="form-group">
                <label>Название <span style="color:#ef4444">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" placeholder="Например: Плотность воды" required>
            </div>
            
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Символ <span style="color:#ef4444">*</span></label>
                    <input type="text" name="symbol" value="<?= htmlspecialchars($c['symbol']) ?>" placeholder="Например: ρ_вода" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label>Значение <span style="color:#ef4444">*</span></label>
                    <input type="text" name="value" value="<?= htmlspecialchars($c['value']) ?>" placeholder="Например: 1000" required>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label>Ед. изм. <span style="color:#ef4444">*</span></label>
                    <input type="text" name="unit" value="<?= htmlspecialchars($c['unit']) ?>" placeholder="Например: кг/м³" required>
                </div>
            </div>

            <div class="form-group">
                <label>Описание</label>
                <input type="text" name="description" value="<?= htmlspecialchars($c['description']) ?>" placeholder="Краткое описание константы">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Сохранить</button>
                <a href="/admin/constants.php" class="btn btn-secondary" style="flex: 1;">Отмена</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>