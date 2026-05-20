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

// Очистка и преобразование входных данных от пользователя
$cleanInputs = [];
foreach ($inputs as $key => $value) {
    if (trim($value) !== '') {
        $cleanInputs[$key] = (float)str_replace(',', '.', $value);
    }
}

// --- БЕЗОПАСНОСТЬ: Защита от подмены данных ---
// Если это задача из конструктора, принудительно перезаписываем отправленные значения теми, что лежат в БД
if (isset($inputData['custom_problem_id']) && $inputData['custom_problem_id']) {
    $customProblemId = (int)$inputData['custom_problem_id'];
    $stmtCustom = $pdo->prepare("SELECT input_data FROM user_problems WHERE id = ?");
    $stmtCustom->execute([$customProblemId]);
    $customProblem = $stmtCustom->fetch(PDO::FETCH_ASSOC);
    
    if ($customProblem) {
        $hardcodedInputs = json_decode($customProblem['input_data'], true) ?? [];
        foreach ($hardcodedInputs as $key => $val) {
            // Перезаписываем то, что прислал пользователь, истинными значениями констант задачи
            $cleanInputs[$key] = (float)$val;
        }
    }
}

// Получаем конфигурацию базовой задачи из БД (формулы и настройки полей)
$stmt = $pdo->prepare("SELECT formula_text, formula_expression, output_fields FROM problem_types WHERE id = ?");
$stmt->execute([$problemId]);
$problemConfig = $stmt->fetch(PDO::FETCH_ASSOC);

if ($problemConfig) {
    // Декодируем JSON для удобной работы в калькуляторе
    $problemConfig['formula_expression'] = json_decode($problemConfig['formula_expression'], true);
    $problemConfig['output_fields'] = json_decode($problemConfig['output_fields'], true)['fields'] ?? [];
} else {
    echo json_encode(['success' => false, 'error' => 'Задача не найдена в базе данных']);
    exit;
}

// Подтягиваем все физические константы из базы данных
$stmtConst = $pdo->query("SELECT symbol, value FROM constants");
$dbConstants = [];
while ($row = $stmtConst->fetch(PDO::FETCH_ASSOC)) {
    // Сохраняем символ (например, 'g') как ключ, а значение как число
    $dbConstants[$row['symbol']] = (float)$row['value'];
}
$problemConfig['constants'] = $dbConstants;


// Выполняем расчёт, передавая конфигурацию третьим параметром
$result = PhysicsCalculator::calculate($problemId, $cleanInputs, $problemConfig);


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