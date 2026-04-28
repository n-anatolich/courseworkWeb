<?php
require_once __DIR__ . '/includes/db.php';

// Перенаправление авторизованных на главную
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Пожалуйста, заполните все обязательные поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный формат email адреса.";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать минимум 6 символов.";
    } else {
        // Проверка уникальности
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Пользователь с таким именем или email уже существует в системе.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hash])) {
                $success = "Регистрация прошла успешно! Теперь вы можете авторизоваться.";
                // Автоматически очищаем поля после успеха
                $username = '';
                $email = '';
            } else {
                $error = "Произошла системная ошибка при регистрации. Попробуйте позже.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card glass-panel">
        <h2 class="auth-title">Создать аккаунт</h2>
        <p class="auth-subtitle">Присоединяйтесь к платформе PhysCalc</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
                <div style="margin-top: 15px;">
                    <a href="/login.php" class="btn btn-primary btn-block">Перейти ко входу</a>
                </div>
            </div>
        <?php else: ?>
        
        <form method="POST" action="/register.php" class="form-modern">
            <div class="form-group">
                <label for="username">Логин пользователя</label>
                <input type="text" id="username" name="username" placeholder="ivan_student" value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Электронная почта</label>
                <input type="email" id="email" name="email" placeholder="example@mail.ru" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Новый пароль</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
        </form>
        <div class="auth-footer">
            Уже есть аккаунт? <a href="/login.php">Войти</a>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
