<?php
require_once __DIR__ . '/includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? ''); 
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = "Пожалуйста, введите логин и пароль.";
    } else {
        // Ищем по логину ИЛИ email (добавлено поле is_blocked)
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role, is_blocked FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Проверка блокировки аккаунта
            if ($user['is_blocked']) {
                $error = "Ваш аккаунт был заблокирован администратором.";
            } else {
                // Инициализация сессии
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: /index.php");
                exit;
            }
        } else {
            $error = "Неверный логин или пароль.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card glass-panel">
        <h2 class="auth-title">Вход в систему</h2>
        <p class="auth-subtitle">Добро пожаловать обратно в PhysCalc</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/login.php" class="form-modern">
            <div class="form-group">
                <label for="login">Логин или Email</label>
                <input type="text" id="login" name="login" placeholder="Ваш логин" value="<?= htmlspecialchars($login ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>
        <div class="auth-footer">
            Нет аккаунта? <a href="/register.php">Создать</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
