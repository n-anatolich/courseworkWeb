<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Получаем категории и типы задач из БД
$stmt = $pdo->query("
    SELECT pt.*, pc.name as category_name 
    FROM problem_types pt 
    JOIN problem_categories pc ON pt.category_id = pc.id 
    ORDER BY pc.sort_order, pt.sort_order
");
$problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Формируем структуру данных для JavaScript
$problemsData = [];
foreach ($problems as $p) {
    $problemsData[$p['id']] = [
        'name' => $p['name'],
        'category' => $p['category_name'],
        'description' => $p['description'],
        'base_id' => $p['id'],
        'input_fields' => json_decode($p['input_fields'], true)['fields'] ?? [],
        'output_fields' => json_decode($p['output_fields'], true)['fields'] ?? [],
        'prefilled' => []
    ];
}

$userProblems = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT up.*, pt.input_fields, pt.output_fields FROM user_problems up JOIN problem_types pt ON up.problem_type_id = pt.id WHERE up.user_id = ? OR up.is_public = TRUE");
    $stmt->execute([$_SESSION['user_id']]);
    $userProblems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($userProblems as $up) {
        $idKey = 'custom_' . $up['id'];
        $problemsData[$idKey] = [
            'name' => $up['name'],
            'category' => 'Пользовательские задачи',
            'description' => $up['description'],
            'base_id' => $up['problem_type_id'],
            'input_fields' => json_decode($up['input_fields'], true)['fields'] ?? [],
            'output_fields' => json_decode($up['output_fields'], true)['fields'] ?? [],
            'prefilled' => json_decode($up['input_data'], true) ?? []
        ];
    }
}
?>

<style>
    .custom-dropdown {
        position: relative;
        width: 100%;
        user-select: none;
        z-index: 50; /* Чтобы меню было поверх других элементов */
    }
    .dropdown-selected {
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border);
        padding: 14px 16px;
        border-radius: 8px;
        color: #fff;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
        font-size: 1rem;
    }
    .dropdown-selected:hover, .custom-dropdown.active .dropdown-selected {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
    }
    .dropdown-arrow {
        transition: transform 0.3s ease;
        font-size: 0.8rem;
        color: var(--text-secondary);
    }
    .custom-dropdown.active .dropdown-arrow {
        transform: rotate(180deg);
    }
    .dropdown-options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 8px;
        background: rgba(15, 23, 42, 0.95); /* Более плотный фон для читаемости */
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        max-height: 350px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .dropdown-options.show {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }
    .dropdown-group {
        padding: 12px 16px 8px;
        font-size: 0.8rem;
        color: var(--primary-color);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        background: rgba(255, 255, 255, 0.02);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .dropdown-item {
        padding: 12px 16px 12px 24px;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.95rem;
    }
    .dropdown-item:hover {
        background: rgba(99, 102, 241, 0.15);
        color: #fff;
        padding-left: 28px; /* Легкий эффект сдвига при наведении */
    }
    
    /* Красивый скроллбар для меню */
    .dropdown-options::-webkit-scrollbar { width: 6px; }
    .dropdown-options::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 8px; }
    .dropdown-options::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 8px; }
</style>

<div class="glass-panel" style="padding: 40px; max-width: 800px; margin: 0 auto;">
    <h2 class="hero-title" style="font-size: 2rem;">Решение задач</h2>
    <p class="hero-subtitle">Выберите тип задачи и заполните известные параметры.</p>
    
    <div class="form-modern">
        <div class="form-group">
            <label>Тип задачи</label>
            
            <div class="custom-dropdown" id="custom-task-dropdown">
                <div class="dropdown-selected">
                    <span id="dropdown-selected-text">Выберите задачу</span>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="dropdown-options" id="dropdown-options-container">
                    <?php
                    $currentCategory = '';
                    foreach ($problems as $p) {
                        if ($currentCategory !== $p['category_name']) {
                            $currentCategory = $p['category_name'];
                            echo "<div class=\"dropdown-group\">" . htmlspecialchars($currentCategory) . "</div>";
                        }
                        echo "<div class=\"dropdown-item\" data-value=\"{$p['id']}\">" . htmlspecialchars($p['name']) . "</div>";
                    }
                    ?>
                    
                    <?php if (!empty($userProblems)): ?>
                        <div class="dropdown-group" style="background: rgba(99, 102, 241, 0.1); color: #fff; border-top: 1px solid var(--primary-color);">Пользовательские задачи</div>
                        <?php foreach ($userProblems as $up): ?>
                            <div class="dropdown-item" data-value="custom_<?= $up['id'] ?>">⭐ <?= htmlspecialchars($up['name']) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <select id="problem-select" style="display: none;">
                <option value="">Выберите задачу</option>
                <?php foreach ($problems as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
                <?php foreach ($userProblems as $up): ?>
                    <option value="custom_<?= $up['id'] ?>"></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="dynamic-form-area" style="display: none; margin-top: 30px; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
            <h3 id="task-title" style="margin-bottom: 5px; color: #fff;"></h3>
            <p id="task-desc" style="color: var(--primary-color); font-family: monospace; font-size: 1.1rem; margin-bottom: 25px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 6px; display: inline-block;"></p>
            
            <form id="calc-form">
                <div id="input-fields-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;"></div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px; font-size: 1.1rem; padding: 12px;">Выполнить расчёт</button>
            </form>
        </div>
        
        <div id="result-area" style="display: none; margin-top: 30px; padding: 25px; border-radius: 12px; border-left: 4px solid var(--primary-color); background: rgba(99, 102, 241, 0.05); border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div id="error-msg" class="alert alert-error" style="display: none;"></div>
            
            <div id="success-result" style="display: none;">
                <h3 style="margin-bottom: 20px; color: #6ee7b7; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">✓</span> Результат:
                </h3>
                <div id="final-answers" style="font-size: 1.3rem; color: #fff; font-weight: 600; margin-bottom: 25px; background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px;"></div>
                
                <h4 style="color: var(--text-secondary); margin-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Пошаговое решение:</h4>
                <div id="solution-steps" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 8px; font-family: monospace; color: #cbd5e1; line-height: 1.8; border: 1px solid rgba(255,255,255,0.05);"></div>
            </div>
        </div>
    </div>
</div>

<script>
  window.MathJax = {
    tex: { inlineMath: [['\\(', '\\)']] },
    startup: { typeset: false } // Отключаем авторендеринг при загрузке, будем рендерить вручную
  };
</script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>

<script>
// Передаем данные из PHP в JS
const problemsData = <?= json_encode($problemsData, JSON_UNESCAPED_UNICODE) ?>;
const select = document.getElementById('problem-select');
const formArea = document.getElementById('dynamic-form-area');
const container = document.getElementById('input-fields-container');
const calcForm = document.getElementById('calc-form');
const resultArea = document.getElementById('result-area');
const errorMsg = document.getElementById('error-msg');
const successResult = document.getElementById('success-result');
const finalAnswers = document.getElementById('final-answers');
const solutionSteps = document.getElementById('solution-steps');

// ЛОГИКА КАСТОМНОГО DROPDOWN
const dropdownContainer = document.getElementById('custom-task-dropdown');
const dropdownSelected = document.querySelector('.dropdown-selected');
const dropdownOptions = document.querySelector('.dropdown-options');
const dropdownSelectedText = document.getElementById('dropdown-selected-text');

// Открытие/закрытие меню
dropdownSelected.addEventListener('click', function() {
    dropdownContainer.classList.toggle('active');
    dropdownOptions.classList.toggle('show');
});

// Выбор элемента
document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', function() {
        const val = this.getAttribute('data-value');
        const text = this.textContent;
        
        // Обновляем текст в кастомном UI
        dropdownSelectedText.textContent = text;
        dropdownSelectedText.style.color = '#fff';
        
        // Закрываем меню
        dropdownContainer.classList.remove('active');
        dropdownOptions.classList.remove('show');
        
        // Передаем значение в скрытый select и вызываем событие change
        select.value = val;
        select.dispatchEvent(new Event('change'));
    });
});

// Закрытие при клике вне области
document.addEventListener('click', function(e) {
    if (!dropdownContainer.contains(e.target)) {
        dropdownContainer.classList.remove('active');
        dropdownOptions.classList.remove('show');
    }
});

// ОСНОВНАЯ ЛОГИКА КАЛЬКУЛЯТОРА
select.addEventListener('change', function() {
    const id = this.value;
    resultArea.style.display = 'none';
    
    if (!id) {
        formArea.style.display = 'none';
        return;
    }
    
    const data = problemsData[id];
    document.getElementById('task-title').textContent = data.name;
    document.getElementById('task-desc').textContent = data.description;
    
    // Генерируем поля ввода
    container.innerHTML = '';
    data.input_fields.forEach(field => {
        const prefilledVal = data.prefilled && data.prefilled[field.name] !== undefined ? data.prefilled[field.name] : '';
        const isReadonly = prefilledVal !== '' ? 'readonly' : '';
        const inputStyle = prefilledVal !== '' ? 'background: rgba(99, 102, 241, 0.15); border-color: var(--primary-color); opacity: 0.8;' : 'background:rgba(0,0,0,0.3);';

        const div = document.createElement('div');
        div.className = 'form-group';
        div.innerHTML = `
            <label style="color: #cbd5e1; font-weight: 500;">${field.label} ${field.required && prefilledVal === '' ? '<span style="color:#ef4444">*</span>' : ''}</label>
            <div style="display:flex; align-items:stretch; margin-top: 8px;">
                <input type="number" step="any" name="${field.name}" placeholder="0.0" ${field.required && prefilledVal === '' ? 'required' : ''} value="${prefilledVal}" ${isReadonly}
                    style="flex:1; padding:12px 16px; color:#fff; border:1px solid var(--glass-border); border-right:none; border-radius:8px 0 0 8px; font-size: 1rem; transition: border-color 0.3s; ${inputStyle}">
                <span style="background:rgba(255,255,255,0.05); padding:12px 16px; border:1px solid var(--glass-border); border-radius:0 8px 8px 0; color:#94a3b8; display:flex; align-items:center; font-weight:600;">${field.unit}</span>
            </div>
        `;
        container.appendChild(div);
        
        // Добавляем эффект фокуса на инпут
        const input = div.querySelector('input');
        input.addEventListener('focus', () => {
            input.style.borderColor = 'var(--primary-color)';
            input.nextElementSibling.style.borderColor = 'var(--primary-color)';
        });
        input.addEventListener('blur', () => {
            input.style.borderColor = 'var(--glass-border)';
            input.nextElementSibling.style.borderColor = 'var(--glass-border)';
        });
    });
    
    formArea.style.display = 'block';
});

// Обработка отправки формы
calcForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(calcForm);
    const inputs = {};
    formData.forEach((value, key) => { inputs[key] = value; });
    
    const problemId = select.value;
    
    const problemIdKey = select.value;
    const problemId = problemsData[problemIdKey].base_id; // Используем базовый ID для PHP-вычислений

    // Анимация загрузки на кнопке
    const btn = calcForm.querySelector('button');
    const originalBtnText = btn.textContent;
    btn.textContent = 'Вычисление...';
    btn.disabled = true;
    
    fetch('/ajax_calculate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ problem_id: problemId, inputs: inputs })
    })
    .then(response => response.json())
    .then(data => {
        btn.textContent = originalBtnText;
        btn.disabled = false;
        resultArea.style.display = 'block';
        
        if (data.success) {
            errorMsg.style.display = 'none';
            successResult.style.display = 'block';
            
            let answersHtml = '';
            const outputDef = problemsData[problemId].output_fields;
            
            for (const [key, val] of Object.entries(data.results)) {
                let unit = '';
                let label = key;
                const fieldDef = outputDef.find(f => f.name === key);
                if (fieldDef) {
                    unit = fieldDef.unit;
                    label = fieldDef.label;
                }
                answersHtml += `<div style="margin-bottom: 5px;">${label} = <span style="color: #fff;">${Number(val).toFixed(4).replace(/\.?0+$/, '')}</span> <span style="color: #94a3b8; font-size: 0.9em;">${unit}</span></div>`;
            }
            finalAnswers.innerHTML = answersHtml;
            solutionSteps.innerHTML = data.steps.join('<br><br>');
            
            // Плавный скролл к результату
            resultArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Рендерим формулы MathJax в пошаговом решении
            if (window.MathJax && MathJax.typesetPromise) {
                MathJax.typesetPromise([solutionSteps]).catch(function (err) {
                    console.log('MathJax error: ', err.message);
                });
            }

        } else {
            successResult.style.display = 'none';
            errorMsg.style.display = 'block';
            errorMsg.textContent = data.error || 'Произошла неизвестная ошибка при расчетах.';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.textContent = originalBtnText;
        btn.disabled = false;
        resultArea.style.display = 'block';
        successResult.style.display = 'none';
        errorMsg.style.display = 'block';
        errorMsg.textContent = 'Ошибка соединения с сервером.';
    });
});

// АВТОПОДСТАНОВКА ДАННЫХ ИЗ ИСТОРИИ
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const reuseId = urlParams.get('reuse_id');
    const reuseDataStr = urlParams.get('reuse_data');

    if (reuseId && reuseDataStr) {
        // Ищем нужный элемент в кастомном выпадающем списке
        const dropdownItem = document.querySelector(`.dropdown-item[data-value="${reuseId}"]`);
        
        if (dropdownItem) {
            // 1. Обновляем текст в UI
            dropdownSelectedText.textContent = dropdownItem.textContent;
            dropdownSelectedText.style.color = '#fff';
            
            // 2. Устанавливаем значение в select и вызываем change (это сгенерирует форму)
            select.value = reuseId;
            select.dispatchEvent(new Event('change'));
            
            // 3. Парсим JSON и заполняем поля ввода
            try {
                const reuseData = JSON.parse(reuseDataStr);
                for (const key in reuseData) {
                    const inputField = document.querySelector(`input[name="${key}"]`);
                    if (inputField) {
                        inputField.value = reuseData[key];
                        // Имитируем фокус для красоты (чтобы сработали стили, если они зависят от заполненности)
                        inputField.style.borderColor = 'var(--primary-color)';
                        inputField.nextElementSibling.style.borderColor = 'var(--primary-color)';
                    }
                }
            } catch (e) {
                console.error('Ошибка обработки данных истории:', e);
            }
            
            // 4. Очищаем параметры из URL, чтобы данные не подставлялись заново при обновлении страницы (F5)
            window.history.replaceState(null, '', window.location.pathname);
        }
    }
});

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>