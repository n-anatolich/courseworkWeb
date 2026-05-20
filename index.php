<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero-section glass-panel">
    <div class="hero-content">
        <h2 class="hero-title">Откройте вселенную<br>классической механики</h2>
        <p class="hero-subtitle">Автоматизированная система для молниеносного решения физических задач.</p>
        
        <?php if ($isLoggedIn): ?>
            <div class="auth-welcome">
                <p>Вы успешно авторизованы как <strong style="color: #facc15;"><?= $username ?></strong> (Роль: <?= $role ?>).</p>
                <p style="margin: 15px 0;">Вам полностью доступен весь функционал системы: сохранение истории, использование конструктора задач и персонализированные расчеты.</p>
                <div class="hero-actions">
                    <a href="/calculator.php" class="btn btn-primary">Перейти к калькулятору</a>
                    <a href="/history.php" class="btn btn-secondary">Моя история</a>
                </div>
            </div>
        <?php else: ?>
            <div class="guest-welcome">
                <p>В гостевом режиме вы можете просматривать справочник и делать базовые вычисления (без опции сохранения истории).</p>
                <div class="hero-actions">
                    <a href="/register.php" class="btn btn-primary">Создать аккаунт</a>
                    <a href="/login.php" class="btn btn-secondary">Войти</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>