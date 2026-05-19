<?php
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Сбор статистики
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'constants' => $pdo->query("SELECT COUNT(*) FROM constants")->fetchColumn(),
    'problems' => $pdo->query("SELECT COUNT(*) FROM problem_types")->fetchColumn(),
    'calculations' => $pdo->query("SELECT COUNT(*) FROM calculations")->fetchColumn()
];

// Топ-5 популярных задач
$topProblems = $pdo->query("
    SELECT pt.name, COUNT(c.id) as calc_count 
    FROM calculations c 
    JOIN problem_types pt ON c.problem_type_id = pt.id 
    GROUP BY pt.id 
    ORDER BY calc_count DESC 
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2>Админ панель</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
            <a href="/admin/users.php" class="btn btn-secondary">Пользователи</a>
            <a href="/admin/problems.php" class="btn btn-secondary">Задачи</a>
            <a href="/admin/constants.php" class="btn btn-primary">Константы</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="glass-panel" style="padding: 20px; text-align: center; background: rgba(99, 102, 241, 0.1);">
            <div style="font-size: 2.5rem; font-weight: 700; color: #a5b4fc;"><?= $stats['calculations'] ?></div>
            <div style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase;">Всего расчётов</div>
        </div>
        <div class="glass-panel" style="padding: 20px; text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #fff;"><?= $stats['users'] ?></div>
            <div style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase;">Пользователей</div>
        </div>
        <div class="glass-panel" style="padding: 20px; text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #fff;"><?= $stats['problems'] ?></div>
            <div style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase;">Типов задач</div>
        </div>
        <div class="glass-panel" style="padding: 20px; text-align: center;">
            <div style="font-size: 2.5rem; font-weight: 700; color: #fff;"><?= $stats['constants'] ?></div>
            <div style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase;">Констант</div>
        </div>
    </div>

    <h3>Самые популярные задачи</h3>
    <table class="glass-table">
        <thead>
            <tr>
                <th>Название задачи</th>
                <th style="text-align: right;">Количество использований</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($topProblems as $tp): ?>
                <tr>
                    <td><?= htmlspecialchars($tp['name']) ?></td>
                    <td style="text-align: right;"><span style="background: var(--primary-color); padding: 2px 10px; border-radius: 12px; font-weight: bold;"><?= $tp['calc_count'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($topProblems)): ?>
                <tr><td colspan="2" style="text-align:center;">Расчётов пока нет</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>