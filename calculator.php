<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// 1. Получаем системные задачи из БД
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
        'schema_type' => $p['schema_type'] ?? null, // Добавлено поле
        'input_fields' => json_decode($p['input_fields'], true)['fields'] ?? [],
        'output_fields' => json_decode($p['output_fields'], true)['fields'] ?? [],
        'prefilled' => []
    ];
}

// 2. Получаем пользовательские задачи (Конструктор)
$userProblems = [];
if (isset($_SESSION['user_id'])) {
    // В SELECT добавлено поле pt.schema_type
    $stmt = $pdo->prepare("
        SELECT up.*, pt.input_fields, pt.output_fields, pt.schema_type 
        FROM user_problems up 
        JOIN problem_types pt ON up.problem_type_id = pt.id 
        WHERE up.user_id = ? OR up.is_public = TRUE
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userProblems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($userProblems as $up) {
        $idKey = 'custom_' . $up['id'];
        $problemsData[$idKey] = [
            'name' => $up['name'],
            'category' => 'Пользовательские задачи',
            'description' => $up['description'],
            'base_id' => $up['problem_type_id'],
            'schema_type' => $up['schema_type'] ?? null, // Добавлено поле
            'input_fields' => json_decode($up['input_fields'], true)['fields'] ?? [],
            'output_fields' => json_decode($up['output_fields'], true)['fields'] ?? [],
            'prefilled' => json_decode($up['input_data'], true) ?? []
        ];
    }
}

?>

<div class="glass-panel" style="padding: 40px; max-width: 800px; margin: 0 auto;">
    <h2 class="hero-title" style="font-size: 2rem;">Решение задач</h2>
    <p class="hero-subtitle">Выберите задачу из списка и заполните известные параметры.</p>
    
    <div class="form-modern">
        <div class="form-group">
            <label for="problem-select">Тип задачи</label>
            
            <input type="text" id="problem-search" placeholder="Поиск задачи по названию..." autocomplete="off" style="width: 100%; padding: 12px 16px; margin-bottom: 12px; background: rgba(0,0,0,0.4); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px; font-size: 1rem; outline: none;">
            
            <select id="problem-select" style="width: 100%; padding: 14px 16px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px; font-size: 1rem; cursor: pointer; outline: none;">

                <option value="">-- Выберите задачу --</option>
                
                <?php
                // Вывод системных задач по категориям
                $currentCategory = '';
                foreach ($problems as $p) {
                    if ($currentCategory !== $p['category_name']) {
                        if ($currentCategory !== '') echo "</optgroup>";
                        $currentCategory = $p['category_name'];
                        echo "<optgroup label=\"" . htmlspecialchars($currentCategory) . "\">";
                    }
                    echo "<option value=\"{$p['id']}\">" . htmlspecialchars($p['name']) . "</option>";
                }
                if ($currentCategory !== '') echo "</optgroup>";
                ?>
                
                <?php
                // Вывод пользовательских задач
                if (!empty($userProblems)): 
                ?>
                    <optgroup label="Пользовательские задачи (Конструктор)">
                        <?php foreach ($userProblems as $up): ?>
                            <option value="custom_<?= $up['id'] ?>">⭐ <?= htmlspecialchars($up['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
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
        
        <div id="result-area" style="display: none; margin-top: 30px; padding: 25px; border-radius: 12px; border-left: 4px solid var(--primary-color); background: rgba(250, 204, 21, 0.05); border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div id="error-msg" class="alert alert-error" style="display: none;"></div>
            
            <div id="success-result" style="display: none;">
                <h3 style="margin-bottom: 20px; color: #6ee7b7; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;">✓</span> Результат:
                </h3>
                <div id="final-answers" style="font-size: 1.3rem; color: #fff; font-weight: 600; margin-bottom: 25px; background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px;"></div>
                
                <h4 style="color: var(--text-secondary); margin-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Пошаговое решение:</h4>
                <div id="solution-steps" style="background: rgba(0,0,0,0.4); padding: 20px; border-radius: 8px; font-family: monospace; color: #cbd5e1; line-height: 1.8; border: 1px solid rgba(255,255,255,0.05);"></div>
                
                <div id="canvas-container" style="display: none; margin-top: 25px; background: rgba(0,0,0,0.4); padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                    <h4 style="color: var(--text-secondary); margin-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;">Визуализация:</h4>
                    <canvas id="schema-canvas" width="600" height="200" style="background: #e2e8f0; border-radius: 4px; max-width: 100%; height: auto;"></canvas>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
  window.MathJax = {
    tex: { inlineMath: [['\\(', '\\)']] },
    startup: { typeset: false }
  };
</script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>

<script>
// --- ИНИЦИАЛИЗАЦИЯ ДАННЫХ ---
// Получаем структуру задач (поля ввода/вывода, типы схем, формулы) из PHP в формате JSON.
// JSON_UNESCAPED_UNICODE используется для корректного отображения кириллицы.
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
const canvasContainer = document.getElementById('canvas-container');
const canvas = document.getElementById('schema-canvas');
const ctx = canvas ? canvas.getContext('2d') : null;

// Функция отрисовки схем на Canvas (Без хардкода ID)
function drawSchema(schemaType) {
    if (!ctx) return;
    
    // Очищаем холст
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Включаем отображение только если у задачи есть тип схемы
    if (schemaType === 'kinematics_car' || schemaType === 'dynamics_block') {
        canvasContainer.style.display = 'block';
    } else {
        canvasContainer.style.display = 'none';
        return;
    }

    if (schemaType === 'kinematics_car') { 
        // Схема: Равномерное движение
        // Дорога
        ctx.beginPath(); ctx.moveTo(50, 150); ctx.lineTo(550, 150);
        ctx.lineWidth = 3; ctx.strokeStyle = '#475569'; ctx.stroke();
        // Автомобиль / Блок
        ctx.fillStyle = '#6366f1'; ctx.fillRect(100, 110, 80, 40);
        // Вектор скорости
        ctx.beginPath(); ctx.moveTo(190, 130); ctx.lineTo(280, 130); ctx.lineTo(270, 125);
        ctx.moveTo(280, 130); ctx.lineTo(270, 135);
        ctx.strokeStyle = '#ef4444'; ctx.lineWidth = 3; ctx.stroke();
        // Подписи
        ctx.fillStyle = '#ef4444'; ctx.font = 'bold 18px Arial'; ctx.fillText('v', 230, 115);
        ctx.fillStyle = '#475569'; ctx.fillText('s', 320, 175);
    } 
    else if (schemaType === 'dynamics_block') { 
        // Схема: Второй закон Ньютона
        // Поверхность
        ctx.beginPath(); ctx.moveTo(100, 150); ctx.lineTo(500, 150);
        ctx.lineWidth = 3; ctx.strokeStyle = '#475569'; ctx.stroke();
        // Блок массы
        ctx.fillStyle = '#10b981'; ctx.fillRect(250, 90, 100, 60);
        ctx.fillStyle = '#fff'; ctx.font = 'bold 20px Arial'; ctx.fillText('m', 290, 128);
        // Вектор силы F
        ctx.beginPath(); ctx.moveTo(350, 120); ctx.lineTo(460, 120); ctx.lineTo(450, 115);
        ctx.moveTo(460, 120); ctx.lineTo(450, 125);
        ctx.strokeStyle = '#ef4444'; ctx.lineWidth = 4; ctx.stroke();
        ctx.fillStyle = '#ef4444'; ctx.fillText('F', 410, 110);
        // Вектор ускорения a
        ctx.beginPath(); ctx.moveTo(250, 60); ctx.lineTo(350, 60); ctx.lineTo(340, 55);
        ctx.moveTo(350, 60); ctx.lineTo(340, 65);
        ctx.strokeStyle = '#f59e0b'; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = '#f59e0b'; ctx.fillText('a', 295, 50);
    }
}


    // --- ДИНАМИЧЕСКИЙ РЕНДЕРИНГ ФОРМЫ ---
    // Очищаем контейнер и перебираем массив требуемых полей (input_fields) для выбранной задачи.
    // Если задача вызвана из "Конструктора", подставляем сохраненные значения (prefilledVal)
    // и блокируем их от изменения (readonly). Значения экранируются от XSS атак.
select.addEventListener('change', function() {
    const idKey = this.value;
    resultArea.style.display = 'none';
    
    if (!idKey || !problemsData[idKey]) {
        formArea.style.display = 'none';
        return;
    }
    
    const data = problemsData[idKey];
    document.getElementById('task-title').textContent = data.name;
    document.getElementById('task-desc').textContent = data.description;
    
    container.innerHTML = '';
    
        data.input_fields.forEach(field => {
        // Проверяем, есть ли предзаполненное значение из конструктора
        const rawPrefilledVal = data.prefilled && data.prefilled[field.name] !== undefined ? data.prefilled[field.name] : '';
        
        // Экранируем значение для защиты от XSS (вдруг злоумышленник сохранил в БД скрипт вместо числа)
        const escapeHTML = (str) => String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
        const prefilledVal = escapeHTML(rawPrefilledVal);
        
        const isReadonly = prefilledVal !== '' ? 'readonly' : '';
        
        const inputStyle = prefilledVal !== '' 
            ? 'background: rgba(250, 204, 21, 0.15); border-color: var(--primary-color); opacity: 0.8;' 
            : 'background: rgba(0,0,0,0.3);';


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
        
        // Визуальный отклик только для активных полей
        if (!isReadonly) {
            const input = div.querySelector('input');
            input.addEventListener('focus', () => {
                input.style.borderColor = 'var(--primary-color)';
                input.nextElementSibling.style.borderColor = 'var(--primary-color)';
            });
            input.addEventListener('blur', () => {
                input.style.borderColor = 'var(--glass-border)';
                input.nextElementSibling.style.borderColor = 'var(--glass-border)';
            });
        }
    });
    
    formArea.style.display = 'block';
});

// 2. Отправка данных на сервер
calcForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(calcForm);
    const inputs = {};
    formData.forEach((value, key) => { inputs[key] = value; });
    
        const problemIdKey = select.value;
        const problemId = problemsData[problemIdKey].base_id; // Базовый ID формулы
        // Определяем, решаем ли мы сейчас задачу из конструктора
        const customId = problemIdKey.startsWith('custom_') ? problemIdKey.replace('custom_', '') : null;
        
        const btn = calcForm.querySelector('button');
        const originalBtnText = btn.textContent;
        btn.textContent = 'Вычисление...';
        btn.disabled = true;
        
    // --- АСИНХРОННЫЙ РАСЧЕТ ---
    // Формируем полезную нагрузку: базовый ID формулы, флаг кастомной задачи (если есть) 
    // и введенные пользователем переменные. Отправляем POST-запрос на сервер без перезагрузки страницы.
        fetch('/ajax_calculate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ problem_id: problemId, custom_problem_id: customId, inputs: inputs })
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
            const outputDef = problemsData[problemIdKey].output_fields;
            
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
            
            resultArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Запуск рендера MathJax
            if (window.MathJax && MathJax.typesetPromise) {
                MathJax.typesetPromise([solutionSteps]).catch(err => console.log('MathJax error: ', err.message));
            }
            // Отрисовка схемы на основе schema_type, переданного с бекенда
            drawSchema(problemsData[problemIdKey].schema_type);

        } else {
            successResult.style.display = 'none';
            errorMsg.style.display = 'block';
            errorMsg.textContent = data.error || 'Произошла ошибка при вычислении.';
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

// 3. Автоподстановка данных из Истории
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const reuseId = urlParams.get('reuse_id');
    const reuseDataStr = urlParams.get('reuse_data');

    if (reuseId && reuseDataStr) {
        // Проверяем, есть ли такой ID в нашем селекте
        const optionExists = Array.from(select.options).some(opt => opt.value === reuseId);
        
        if (optionExists) {
            select.value = reuseId;
            select.dispatchEvent(new Event('change')); // Генерирует поля
            
            try {
                const reuseData = JSON.parse(reuseDataStr);
                for (const key in reuseData) {
                    const inputField = document.querySelector(`input[name="${key}"]`);
                    if (inputField && !inputField.readOnly) {
                        inputField.value = reuseData[key];
                        inputField.style.borderColor = 'var(--primary-color)';
                        inputField.nextElementSibling.style.borderColor = 'var(--primary-color)';
                    }
                }
            } catch (e) {
                console.error('Ошибка парсинга истории:', e);
            }
            
            // Очищаем URL
            window.history.replaceState(null, '', window.location.pathname);
        }
    }
});

// --- ШАГ 2.1: Логика живого поиска по селекту ---
const searchInput = document.getElementById('problem-search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const optgroups = select.querySelectorAll('optgroup');
        
        optgroups.forEach(group => {
            let hasVisibleOptions = false;
            const options = group.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === "") return; // Пропускаем дефолтную опцию
                
                const text = option.textContent.toLowerCase();
                // Если текст опции содержит введенный фильтр - показываем, иначе скрываем
                if (text.includes(filter)) {
                    option.style.display = '';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Если в категории нет подходящих задач, скрываем всю категорию
            group.style.display = hasVisibleOptions ? '' : 'none';
        });

        // Если пользователь ввел текст, и текущая выбранная задача скрылась - сбрасываем выбор
        if (filter !== '' && select.value !== '') {
            const selectedOption = select.querySelector(`option[value="${select.value}"]`);
            if (selectedOption && selectedOption.style.display === 'none') {
                select.value = '';
                select.dispatchEvent(new Event('change')); // Принудительно скрываем поля формы
            }
        }
    });
}


</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>