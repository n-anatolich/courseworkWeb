<?php
require_once __DIR__ . '/../includes/db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Обработка переключения статуса блокировки
if (isset($_GET['toggle_block'])) {
    $targetId = (int)$_GET['toggle_block'];
    
    // Администратор не может заблокировать сам себя
    if ($targetId !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = NOT is_blocked WHERE id = ?");
        $stmt->execute([$targetId]);
    }
    header("Location: /admin/users.php");
    exit;
}

// Получаем список пользователей
$stmt = $pdo->query("SELECT id, username, email, role, is_blocked, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Управление пользователями</h2>
        <a href="/admin/index.php" class="btn btn-secondary">Назад в панель</a>
    </div>

    <div style="overflow-x: auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Дата регистрации</th>
                    <th style="text-align: right;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="color: var(--text-secondary);"><?= $u['id'] ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; <?= $u['role'] === 'admin' ? 'background: rgba(99, 102, 241, 0.2); color: #a5b4fc;' : 'background: rgba(255,255,255,0.1); color: #cbd5e1;' ?>">
                                <?= htmlspecialchars(strtoupper($u['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_blocked']): ?>
                                <span style="color: #ef4444; font-weight: bold;">Заблокирован</span>
                            <?php else: ?>
                                <span style="color: #6ee7b7;">Активен</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.9rem; color: var(--text-secondary);"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                        <td style="text-align: right;">
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="/admin/users.php?toggle_block=<?= $u['id'] ?>" class="btn <?= $u['is_blocked'] ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 5px 10px; font-size: 0.8rem; <?= !$u['is_blocked'] ? 'color: #ef4444; border-color: rgba(239, 68, 68, 0.3);' : '' ?>">
                                    <?= $u['is_blocked'] ? 'Разблокировать' : 'Заблокировать' ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 0.8rem;">(Вы)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>