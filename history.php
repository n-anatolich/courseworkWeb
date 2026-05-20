<?php
require_once __DIR__ . '/includes/db.php';

// Только для авторизованных
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// --- ШАГ 2.2: Обработка удаления расчета ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    // Удаляем ТОЛЬКО если это расчет текущего авторизованного пользователя (защита)
    $stmt = $pdo->prepare("DELETE FROM calculations WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $_SESSION['user_id']]);
    
    header("Location: /history.php?msg=deleted");
    exit;
}

// --- ШАГ 2.2: Настройки пагинации ---
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10; // Показывать по 10 записей на странице
$offset = ($page - 1) * $limit;

// Подсчет общего количества записей текущего пользователя
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM calculations WHERE user_id = ?");
$countStmt->execute([$_SESSION['user_id']]);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Получение записей с учетом лимита и сдвига
$stmt = $pdo->prepare("
    SELECT c.*, pt.name as problem_name 
    FROM calculations c 
    JOIN problem_types pt ON c.problem_type_id = pt.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset
);
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="padding: 40px; max-width: 1000px; margin: 0 auto;">
    <h2 class="hero-title" style="font-size: 2rem;">История расчётов</h2>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #6ee7b7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            Расчет успешно удален из истории.
        </div>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table class="glass-table" style="width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <th style="padding: 15px; color: var(--text-secondary);">Дата</th>
                    <th style="padding: 15px; color: var(--text-secondary);">Тип задачи</th>
                    <th style="padding: 15px; color: var(--text-secondary);">Введенные данные</th>
                    <th style="padding: 15px; color: var(--text-secondary);">Результат</th>
                    <th style="padding: 15px; color: var(--text-secondary); text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history as $row): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 15px; font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap;">
                            <?= date('d.m.Y H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td style="padding: 15px; font-weight: 500; color: #fff;">
                            <?= htmlspecialchars($row['problem_name']) ?>
                        </td>
                        <td style="padding: 15px; font-size: 0.9rem; font-family: monospace; color: #cbd5e1;">
                            <?php 
                            $inputs = json_decode($row['input_data'], true) ?? [];
                            foreach ($inputs as $k => $v) { echo "{$k} = {$v}<br>"; }
                            ?>
                        </td>
                        <td style="padding: 15px; font-size: 0.9rem; font-family: monospace; color: #6ee7b7; font-weight: bold;">
                            <?php 
                            $results = json_decode($row['result_data'], true) ?? [];
                            foreach ($results as $k => $v) { echo "{$k} = " . round($v, 4) . "<br>"; }
                            ?>
                        </td>
                        <td style="padding: 15px; text-align: right; white-space: nowrap;">
                            <a href="/calculator.php?reuse_id=<?= $row['problem_type_id'] ?>&reuse_data=<?= urlencode($row['input_data']) ?>" class="btn" style="background: rgba(250, 204, 21, 0.1); border: 1px solid rgba(250, 204, 21, 0.4); color: #facc15; padding: 6px 12px; font-size: 0.85rem; margin-right: 5px;">Повторить</a>
                            
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Удалить этот расчет из истории?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; font-size: 0.85rem;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                    <tr><td colspan="5" style="padding: 30px; text-align: center; color: var(--text-secondary);">Ваша история пуста. Выполните свой первый расчет!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/history.php?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : '' ?>" style="<?= $i !== $page ? 'background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--glass-border);' : '' ?> padding: 8px 15px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
