<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Получаем все константы из БД
$stmt = $pdo->query("SELECT * FROM constants ORDER BY name ASC");
$constants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="glass-panel" style="padding: 30px; width: 100%; max-width: 1000px; margin: 0 auto;">
    <h2 style="margin-bottom: 10px;">Справочник физических констант</h2>
    <p style="color: var(--text-secondary); margin-bottom: 25px;">Фундаментальные величины, доступные для использования в расчётах.</p>

    <div class="form-modern" style="margin-bottom: 20px;">
        <input type="text" id="searchInput" placeholder="Поиск по названию или символу..." 
               style="width: 100%; padding: 12px 16px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid var(--glass-border); border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.3s;">
    </div>

    <div style="overflow-x: auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Символ</th>
                    <th>Значение</th>
                    <th>Ед. изм.</th>
                    <th>Описание</th>
                </tr>
            </thead>
            <tbody id="constantsBody">
                <?php if (empty($constants)): ?>
                    <tr><td colspan="5" style="text-align: center;">Справочник пуст.</td></tr>
                <?php else: ?>
                    <?php foreach ($constants as $c): 
                        // Форматируем вывод, чтобы убрать лишние нули у decimal (float убирает их автоматически)
                        $val = (float)$c['value'];
                    ?>
                        <tr class="constant-row">
                            <td style="font-weight: 500;"><?= htmlspecialchars($c['name']) ?></td>
                            <td><code style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; padding: 3px 6px; border-radius: 4px; font-size: 0.9rem;"><?= htmlspecialchars($c['symbol']) ?></code></td>
                            <td style="font-family: monospace; color: #6ee7b7; font-size: 1.1rem;"><?= htmlspecialchars($val) ?></td>
                            <td style="color: #94a3b8;"><?= htmlspecialchars($c['unit']) ?></td>
                            <td style="font-size: 0.9rem; color: var(--text-secondary);"><?= htmlspecialchars($c['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="noResultsRow" style="display: none;"><td colspan="5" style="text-align: center; color: #ef4444;">По вашему запросу ничего не найдено</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Клиентский поиск по таблице (Шаг 3.2)
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('.constant-row');
    let visibleCount = 0;

    // Визуальный отклик поля при вводе
    this.style.borderColor = filter ? 'var(--primary-color)' : 'var(--glass-border)';

    rows.forEach(row => {
        // Ищем совпадения по названию (1-я ячейка) и символу (2-я ячейка)
        const name = row.cells[0].textContent.toLowerCase();
        const symbol = row.cells[1].textContent.toLowerCase();
        
        if (name.includes(filter) || symbol.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Показываем/скрываем сообщение "Ничего не найдено"
    document.getElementById('noResultsRow').style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>