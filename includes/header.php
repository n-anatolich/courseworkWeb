<?php
// includes/header.php
// Предполагается, что db.php уже подключен, сессия стартована
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
$role = $isLoggedIn ? $_SESSION['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Физический калькулятор - PhysCalc</title>
    <!-- Современные шрифты Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Базовые стили -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="main-header glass-panel">
        <div class="container header-container">
            <h1 class="logo">
                <a href="/index.php" class="logo">PhysCalc
                </a>
            </h1>
            <nav class="main-nav">
                <button id="burger-btn" class="burger-menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <ul>
                    <li><a href="/calculator.php">Калькулятор</a></li>
                    <li><a href="/reference.php">Справочник</a></li> 
                    <?php if ($isLoggedIn): ?>
                        <li><a href="/history.php">История</a></li>
                        <li><a href="/constructor.php" class="nav-link">Конструктор</a></li>
                    <?php endif; ?>
                    <?php if ($isLoggedIn): ?>
                        <?php if ($role === 'admin'): ?>
                            <li><a href="/admin/index.php" class="admin-link">Админ-панель</a></li>
                        <?php endif; ?>
                        <li class="user-greeting">Привет, <strong><?= $username ?></strong></li>
                        <li><a href="/logout.php" class="btn-logout">Выйти</a></li>
                    <?php else: ?>
                        <li><a href="/login.php" class="btn-login">Вход</a></li>
                        <li><a href="/register.php" class="btn-register">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container content">
