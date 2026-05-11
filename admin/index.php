<?php
require_once __DIR__ . '/../includes/db.php';

// Проверка прав доступа
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero-section glass-panel">
    <h2 class="hero-title">Панель администратора</h2>
    <p class="hero-subtitle">Управление данными приложения PhysCalc</p>
    
    <div class="admin-modules" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 30px;">
        <div class="glass-panel" style="padding: 20px; width: 250px; text-align: center;">
            <h3>Константы</h3>
            <p style="color: var(--text-secondary); margin: 10px 0; font-size: 0.9rem;">Управление справочником физических величин.</p>
            <a href="/admin/constants.php" class="btn btn-primary btn-block">Управлять</a>
        </div>
        <div class="glass-panel" style="padding: 20px; width: 250px; text-align: center; opacity: 0.5;">
            <h3>Типы задач</h3>
            <p style="color: var(--text-secondary); margin: 10px 0; font-size: 0.9rem;">В разработке (КТ-5).</p>
            <a href="#" class="btn btn-secondary btn-block">Недоступно</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>