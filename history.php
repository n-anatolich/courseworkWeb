<?php
require_once __DIR__ . '/includes/db.php';

// Доступ только для авторизованных
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Удаление записи
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM calculations WHERE id = ? AND user_id = ?");
    $stmt->execute([$delId, $_SESSION['user_id']]);
    header("Location: /history.php?msg=deleted");
    exit;
}

// Пагинация
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Подсчет записей
$userId = $_SESSION['user_id'];
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM calculations WHERE user_id = ?");
$countStmt->execute([$userId]);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Получение истории
$stmt = $pdo->prepare("
    SELECT c.*, pt.name as problem_name 
    FROM calculations c 
    JOIN problem_types pt ON c.problem_type_id = pt.id 
    WHERE c.user_id = :user_id 
    ORDER BY c.created_at DESC 
    LIMIT :limit OFFSET :offset
");
// Теперь все параметры именованные
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$history = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1000px; margin: 0 auto;">
    <h2 style="margin-bottom: 20px;">История расчётов</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Запись удалена из истории.</div>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Задача</th>
                    <th>Входные данные</th>
                    <th>Результат</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="5" style="text-align: center;">История пуста. Сделайте свой первый расчёт!</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $row): 
                        $inputs = json_decode($row['input_data'], true) ?? [];
                        $results = json_decode($row['result_data'], true) ?? [];
                        
                        $inStr = [];
                        foreach($inputs as $k => $v) $inStr[] = "{$k}={$v}";
                        
                        $outStr = [];
                        foreach($results as $k => $v) $outStr[] = "{$k}=" . round($v, 4);
                    ?>
                        <tr>
                            <td style="font-size: 0.9rem; color: var(--text-secondary);"><?= date('d.m.Y H:i', strtotime($row['created_at'])) ?></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($row['problem_name']) ?></td>
                            <td><code style="background: rgba(0,0,0,0.2);"><?= implode(', ', $inStr) ?></code></td>
                            <td><code style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7;"><?= implode(', ', $outStr) ?></code></td>
                            <td style="text-align: right;">
                                <a href="/calculator.php?reuse_id=<?= $row['problem_type_id'] ?>&reuse_data=<?= urlencode($row['input_data']) ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">Повторить</a>
                                
                                <a href="/history.php?delete=<?= $row['id'] ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="return confirm('Удалить этот расчёт?');">Удалить</a>
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
                <a href="/history.php?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 5px 12px;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>