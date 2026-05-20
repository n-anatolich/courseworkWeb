<?php
require_once __DIR__ . '/../includes/db.php';

// Проверка прав (только админ)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Обработка удаления задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    // Благодаря ON DELETE CASCADE, если бы у задачи были зависимости, они бы удалились,
    // но в данном случае мы просто удаляем запись из user_problems
    $stmt = $pdo->prepare("DELETE FROM user_problems WHERE id = ?");
    $stmt->execute([$delete_id]);
    
    header("Location: /admin/user_problems.php?msg=deleted");
    exit;
}

// Получение списка всех пользовательских задач с именами авторов и базовых формул
$stmt = $pdo->query("
    SELECT up.*, u.username, pt.name as base_type_name 
    FROM user_problems up 
    JOIN users u ON up.user_id = u.id 
    JOIN problem_types pt ON up.problem_type_id = pt.id 
    ORDER BY up.created_at DESC
");
$userProblems = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <h2>Пользовательские задачи</h2>
        <a href="/admin/index.php" class="btn btn-secondary">Назад в панель</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Задача была успешно удалена.</div>
    <?php endif; ?>

    <div style="overflow-x: auto;">
        <table class="glass-table" style="min-width: 800px;">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Базовая формула</th>
                    <th style="text-align: center;">Доступ</th>
                    <th>Дата создания</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($userProblems as $up): ?>
                    <tr>
                        <td style="color: var(--text-secondary);">#<?= $up['id'] ?></td>
                        <td style="font-weight: 500; color: #fff;"><?= htmlspecialchars($up['name']) ?></td>
                        <td style="color: var(--primary-color);"><?= htmlspecialchars($up['username']) ?></td>
                        <td><span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($up['base_type_name']) ?></span></td>
                        <td style="text-align: center;">
                            <?= $up['is_public'] 
                                ? '<span style="color: #6ee7b7; background: rgba(16, 185, 129, 0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">Публичная</span>' 
                                : '<span style="color: var(--text-secondary); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">Приватная</span>' ?>
                        </td>
                        <td style="color: var(--text-secondary); font-size: 0.9rem;"><?= date('d.m.Y', strtotime($up['created_at'])) ?></td>
                        <td style="text-align: right;">
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Вы уверены, что хотите удалить задачу «<?= htmlspecialchars($up['name']) ?>»? Это действие нельзя отменить.');">
                                <input type="hidden" name="delete_id" value="<?= $up['id'] ?>">
                                <button type="submit" class="btn" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; font-size: 0.85rem;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if(empty($userProblems)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 30px; color: var(--text-secondary);">Пользователи еще не создали ни одной задачи.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
