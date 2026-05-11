<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Обработка удаления записи
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM constants WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: /admin/constants.php?msg=deleted");
    exit;
}

// Поиск и пагинация
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];

if ($search) {
    $where = "WHERE name LIKE ? OR symbol LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Подсчет общего количества записей для пагинации
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM constants $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Получение данных
$sql = "SELECT * FROM constants $where ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$constants = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Справочник констант</h2>
        <a href="/admin/constant_edit.php" class="btn btn-primary">+ Добавить константу</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Запись успешно удалена!</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success">Запись успешно сохранена!</div>
    <?php endif; ?>

    <form method="GET" action="/admin/constants.php" style="display: flex; gap: 10px; margin-bottom: 20px;" class="form-modern">
        <input type="text" name="search" placeholder="Поиск по названию или символу..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
        <button type="submit" class="btn btn-secondary">Найти</button>
        <?php if ($search): ?>
            <a href="/admin/constants.php" class="btn btn-secondary">Сбросить</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x: auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Символ</th>
                    <th>Значение</th>
                    <th>Ед. изм.</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($constants)): ?>
                    <tr><td colspan="6" style="text-align: center;">Записи не найдены</td></tr>
                <?php else: ?>
                    <?php foreach ($constants as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><code><?= htmlspecialchars($c['symbol']) ?></code></td>
                            <td><?= htmlspecialchars($c['value']) ?></td>
                            <td><?= htmlspecialchars($c['unit']) ?></td>
                            <td style="text-align: right;">
                                <a href="/admin/constant_edit.php?id=<?= $c['id'] ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Ред.</a>
                                <a href="/admin/constants.php?delete=<?= $c['id'] ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="return confirm('Вы уверены, что хотите удалить эту константу?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/admin/constants.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 5px 12px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>