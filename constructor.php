<?php
require_once __DIR__ . '/includes/db.php';

// Только для авторизованных
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $problem_type_id = (int)$_POST['problem_type_id'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Собираем предзаполненные данные (значения, которые станут константами в задаче)
    $input_data = [];
    if (isset($_POST['inputs']) && is_array($_POST['inputs'])) {
        foreach ($_POST['inputs'] as $key => $val) {
            if (trim($val) !== '') {
                $input_data[$key] = (float)str_replace(',', '.', $val);
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO user_problems (user_id, name, description, problem_type_id, input_data, result_data, is_public) VALUES (?, ?, ?, ?, ?, '{}', ?)");
    $stmt->execute([
        $_SESSION['user_id'], $name, $description, $problem_type_id, 
        json_encode($input_data, JSON_UNESCAPED_UNICODE), $is_public
    ]);
    
    header("Location: /calculator.php?msg=constructor_success");
    exit;
}

// Получаем базовые типы задач для селекта
$stmt = $pdo->query("SELECT pt.id, pt.name, pc.name as cat_name, pt.input_fields FROM problem_types pt JOIN problem_categories pc ON pt.category_id = pc.id ORDER BY pc.sort_order, pt.sort_order");
$problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Формируем JSON для подстановки полей при выборе базовой формулы
$problemsJson = [];
foreach ($problems as $p) {
    $problemsJson[$p['id']] = json_decode($p['input_fields'], true)['fields'] ?? [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="padding: 40px; max-width: 800px; margin: 0 auto;">
    <h2 class="hero-title" style="font-size: 2rem;">Конструктор задач</h2>
    <p class="hero-subtitle" style="margin-bottom: 25px;">Создайте свою текстовую задачу на базе существующих формул, задав постоянные значения.</p>

    <form method="POST" action="/constructor.php" class="form-modern">
        <div class="form-group">
            <label>Название задачи</label>
            <input type="text" name="name" required placeholder="Например: Автомобиль на трассе" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px;">
        </div>

        <div class="form-group">
            <label>Текст задачи (условие)</label>
            <textarea name="description" required rows="3" placeholder="Автомобиль движется со скоростью 60 км/ч. Какое расстояние он проедет за 2 часа?" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px;"></textarea>
        </div>

        <div class="form-group">
            <label>Базовая формула (тип расчёта)</label>
            <select id="base_problem_select" name="problem_type_id" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px; cursor: pointer;">
                <option value="">Выберите формулу...</option>
                <?php 
                $currCat = '';
                foreach($problems as $p) {
                    if ($currCat !== $p['cat_name']) {
                        if ($currCat !== '') echo "</optgroup>";
                        $currCat = $p['cat_name'];
                        echo "<optgroup label=\"" . htmlspecialchars($currCat) . "\">";
                    }
                    echo "<option value=\"{$p['id']}\">" . htmlspecialchars($p['name']) . "</option>";
                }
                echo "</optgroup>";
                ?>
            </select>
        </div>

        <div id="constructor-fields" style="display: none; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed var(--primary-color);">
            <h4 style="margin-bottom: 10px; color: #fff;">Предзаполнение данных</h4>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 20px;">Введите значения, которые <strong>даны</strong> в вашей задаче. Оставьте пустыми поля, которые нужно будет <strong>найти</strong> пользователю.</p>
            <div id="dynamic-inputs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;"></div>
        </div>

        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 20px;">
            <input type="checkbox" id="is_public" name="is_public" value="1" style="width: 18px; height: 18px; accent-color: var(--primary-color);">
            <label for="is_public" style="margin: 0; cursor: pointer; color: #cbd5e1;">Сделать задачу публичной (доступно всем)</label>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Сохранить задачу</button>
    </form>
</div>

<script>
const problemsConfig = <?= json_encode($problemsJson, JSON_UNESCAPED_UNICODE) ?>;
const select = document.getElementById('base_problem_select');
const fieldsContainer = document.getElementById('constructor-fields');
const inputsContainer = document.getElementById('dynamic-inputs');

select.addEventListener('change', function() {
    const id = this.value;
    inputsContainer.innerHTML = '';
    
    if (!id || !problemsConfig[id]) {
        fieldsContainer.style.display = 'none';
        return;
    }
    
    problemsConfig[id].forEach(field => {
        const div = document.createElement('div');
        div.className = 'form-group';
        div.style.marginBottom = '0';
        div.innerHTML = `
            <label style="color: #cbd5e1; font-size: 0.9rem;">${field.label}</label>
            <div style="display:flex; margin-top: 8px;">
                <input type="number" step="any" name="inputs[${field.name}]" placeholder="Оставить пустым" 
                    style="flex:1; padding:10px; background:rgba(0,0,0,0.4); color:#fff; border:1px solid var(--glass-border); border-right:none; border-radius:6px 0 0 6px;">
                <span style="background:rgba(255,255,255,0.1); padding:10px; border:1px solid var(--glass-border); border-radius:0 6px 6px 0; color:#94a3b8; font-size:0.9rem;">${field.unit}</span>
            </div>
        `;
        inputsContainer.appendChild(div);
    });
    fieldsContainer.style.display = 'block';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>