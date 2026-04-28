<?php
require_once __DIR__ . '/includes/db.php';

// Очистка массива сессии
$_SESSION = array();

// Удаление куки сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожение сессии на сервере
session_destroy();

// Редирект на главную гостевую страницу
header("Location: /index.php");
exit;
