<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/PhysicsCalculator.php';

header('Content-Type: application/json');

// Получаем JSON-данные из fetch-запроса
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData || !isset($inputData['problem_id']) || !isset($inputData['inputs'])) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные запроса']);
    exit;
}

$problemId = (int)$inputData['problem_id'];
$inputs = $inputData['inputs'];

// Очистка и преобразование входных данных
$cleanInputs = [];
foreach ($inputs as $key => $value) {
    if (trim($value) !== '') {
        $cleanInputs[$key] = (float)str_replace(',', '.', $value);
    }
}

// Выполняем расчёт
$result = PhysicsCalculator::calculate($problemId, $cleanInputs);

// Если расчёт успешен и пользователь авторизован - сохраняем в историю
if ($result['success'] && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO calculations (user_id, problem_type_id, input_data, result_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $problemId,
            json_encode($cleanInputs, JSON_UNESCAPED_UNICODE),
            json_encode($result['results'], JSON_UNESCAPED_UNICODE)
        ]);
    } catch (PDOException $e) {
        // Логируем ошибку, но не прерываем выдачу ответа пользователю
        error_log("Ошибка сохранения истории: " . $e->getMessage());
    }
}

echo json_encode($result);