-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_blocked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица физических констант
CREATE TABLE IF NOT EXISTS constants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    value DECIMAL(20,10) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    description TEXT
);

-- Таблица категорий задач
CREATE TABLE IF NOT EXISTS problem_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0
);

-- Таблица типов задач
CREATE TABLE IF NOT EXISTS problem_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    formula_text TEXT,
    formula_expression TEXT,
    input_fields JSON,
    output_fields JSON,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES problem_categories(id) ON DELETE CASCADE
);

-- Таблица истории расчётов
CREATE TABLE IF NOT EXISTS calculations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    problem_type_id INT NOT NULL,
    input_data JSON NOT NULL,
    result_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (problem_type_id) REFERENCES problem_types(id) ON DELETE CASCADE
);

-- Таблица пользовательских задач (конструктор)
CREATE TABLE IF NOT EXISTS user_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    problem_type_id INT NOT NULL,
    input_data JSON NOT NULL,
    result_data JSON NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (problem_type_id) REFERENCES problem_types(id) ON DELETE CASCADE
);

-- Очистка перед вставкой тестовых данных (чтобы скрипт можно было запускать повторно)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE calculations;
TRUNCATE TABLE user_problems;
TRUNCATE TABLE problem_types;
TRUNCATE TABLE problem_categories;
TRUNCATE TABLE constants;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ================= НАПОЛНЕНИЕ ДАННЫМИ =================

-- Пользователи: пароль для всех '123456'. Захеширован встроенной функцией PHP password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO users (id, username, email, password_hash, role) VALUES
(1, 'admin', 'admin@mail.test', '$2y$10$uOBfmBxTveAsSjO6uxPcXOTkMkeQPH9YJF2sOKyub9aBrktq6svnW', 'admin'),
(2, 'ivan_student', 'ivan@mail.test', '$2y$10$uOBfmBxTveAsSjO6uxPcXOTkMkeQPH9YJF2sOKyub9aBrktq6svnW', 'user'),
(3, 'teacher', 'teacher@mail.test', '$2y$10$uOBfmBxTveAsSjO6uxPcXOTkMkeQPH9YJF2sOKyub9aBrktq6svnW', 'user');

-- Категории задач (Жестко задаем ID для предотвращения ошибок связей)
INSERT INTO problem_categories (id, name, description, sort_order) VALUES
(1, 'Кинематика', 'Движение тел, скорость, ускорение, время, расстояние', 1),
(2, 'Динамика', 'Силы, масса, ускорение, законы Ньютона', 2),
(3, 'Работа и энергия', 'Механическая работа, кинетическая и потенциальная энергия, мощность', 3),
(4, 'Импульс и столкновения', 'Импульс тела, законы сохранения, удары', 4),
(5, 'Гидростатика', 'Давление, сила Архимеда, гидростатическое давление', 5);

-- Физические константы
INSERT INTO constants (id, name, symbol, value, unit, description) VALUES
(1, 'Ускорение свободного падения', 'g', 9.8000000000, 'м/с²', 'Стандартное ускорение свободного падения на Земле'),
(2, 'Гравитационная постоянная', 'G', 0.0000000001, 'Н·м²/кг²', 'Фундаментальная физическая константа'),
(3, 'Плотность воды', 'ρ_вода', 1000.0000000000, 'кг/м³', 'Плотность воды при 4°C'),
(4, 'Стандартное атмосферное давление', 'P_атм', 101325.0000000000, 'Па', 'Давление на уровне моря'),
(5, 'Скорость света в вакууме', 'c', 299792458.0000000000, 'м/с', 'Физическая константа скорости света');

-- Типы задач (Формулы).
INSERT INTO problem_types (id, category_id, name, description, formula_text, formula_expression, input_fields, output_fields, sort_order) VALUES
(1, 1, 'Равномерное движение', 's = v·t', 's = v × t', 's=v*t', 
 '{"fields":[{"name":"v","label":"Скорость (v)","unit":"м/с","required":false},{"name":"t","label":"Время (t)","unit":"с","required":false},{"name":"s","label":"Расстояние (s)","unit":"м","required":false}]}',
 '{"fields":[{"name":"v","label":"Скорость","unit":"м/с"},{"name":"t","label":"Время","unit":"с"},{"name":"s","label":"Расстояние","unit":"м"}]}', 1),

(2, 1, 'Равноускоренное движение', 's = a·t²/2', 's = a·t²/2, v = a·t', 
 '{"s":"a*t*t/2","v":"a*t"}',
 '{"fields":[{"name":"a","label":"Ускорение (a)","unit":"м/с²","required":false},{"name":"t","label":"Время (t)","unit":"с","required":false}]}',
 '{"fields":[{"name":"s","label":"Расстояние","unit":"м"},{"name":"v","label":"Скорость","unit":"м/с"}]}', 2),

(3, 2, 'Второй закон Ньютона', 'F = m·a', 'F = m × a', 
 '{"F":"m*a","m":"F/a","a":"F/m"}',
 '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":false},{"name":"a","label":"Ускорение (a)","unit":"м/с²","required":false},{"name":"F","label":"Сила (F)","unit":"Н","required":false}]}',
 '{"fields":[{"name":"m","label":"Масса","unit":"кг"},{"name":"a","label":"Ускорение","unit":"м/с²"},{"name":"F","label":"Сила","unit":"Н"}]}', 1),

(4, 2, 'Сила тяжести', 'F = m·g', 'F = m × g', 
 '{"F":"m*9.8"}',
 '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":true}]}',
 '{"fields":[{"name":"F","label":"Сила тяжести","unit":"Н"}]}', 2),

(5, 3, 'Кинетическая энергия', 'E = m·v²/2', 'E = m·v²/2', 
 '{"E":"m*v*v/2"}',
 '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":true},{"name":"v","label":"Скорость (v)","unit":"м/с","required":true}]}',
 '{"fields":[{"name":"E","label":"Энергия","unit":"Дж"}]}', 1),

(6, 3, 'Механическая работа', 'A = F·s', 'A = F × s', '{"A":"F*s"}', 
 '{"fields":[{"name":"F","label":"Сила (F)","unit":"Н","required":true},{"name":"s","label":"Перемещение (s)","unit":"м","required":true}]}',
 '{"fields":[{"name":"A","label":"Работа","unit":"Дж"}]}', 2),

(7, 4, 'Импульс тела', 'p = m·v', 'p = m × v', '{"p":"m*v"}', 
 '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":true},{"name":"v","label":"Скорость (v)","unit":"м/с","required":true}]}',
 '{"fields":[{"name":"p","label":"Импульс","unit":"кг·м/с"}]}', 1),

(8, 1, 'Равноускоренное движение (с нач. скоростью)', 's = v₀·t + a·t²/2', 's = v_0 \cdot t + \frac{a \cdot t^2}{2}', '{"s":"v0*t + a*t*t/2"}', '{"fields":[{"name":"v0","label":"Нач. скорость (v₀)","unit":"м/с","required":true},{"name":"a","label":"Ускорение (a)","unit":"м/с²","required":true},{"name":"t","label":"Время (t)","unit":"с","required":true}]}', '{"fields":[{"name":"s","label":"Расстояние","unit":"м"},{"name":"v","label":"Конечная скорость","unit":"м/с"}]}', 3),

(9, 1, 'Свободное падение', 'h = g·t²/2', 'h = \frac{g \cdot t^2}{2}', '{"h":"g*t*t/2"}', '{"fields":[{"name":"t","label":"Время (t)","unit":"с","required":true}]}', '{"fields":[{"name":"h","label":"Высота","unit":"м"},{"name":"v","label":"Скорость","unit":"м/с"}]}', 4),

(10, 1, 'Движение брошенного тела', 'h = v₀·t - g·t²/2', 'h = v_0 \cdot t - \frac{g \cdot t^2}{2}', '{"h":"v0*t - g*t*t/2"}', '{"fields":[{"name":"v0","label":"Нач. скорость (v₀)","unit":"м/с","required":true},{"name":"t","label":"Время (t)","unit":"с","required":true}]}', '{"fields":[{"name":"h","label":"Высота","unit":"м"}]}', 5),

(11, 2, 'Сила трения', 'Fтр = μ·N', 'F_{тр} = \mu \cdot N', '{"F":"mu*N"}', '{"fields":[{"name":"mu","label":"Коэф. трения (μ)","unit":"","required":true},{"name":"N","label":"Сила реакции (N)","unit":"Н","required":true}]}', '{"fields":[{"name":"F","label":"Сила трения","unit":"Н"}]}', 3),

(12, 2, 'Закон всемирного тяготения', 'F = G·m₁·m₂/r²', 'F = G \frac{m_1 \cdot m_2}{r^2}', '{"F":"G*m1*m2/(r*r)"}', '{"fields":[{"name":"m1","label":"Масса 1 (m₁)","unit":"кг","required":true},{"name":"m2","label":"Масса 2 (m₂)","unit":"кг","required":true},{"name":"r","label":"Расстояние (r)","unit":"м","required":true}]}', '{"fields":[{"name":"F","label":"Сила","unit":"Н"}]}', 4),

(13, 2, 'Движение по окружности', 'aц = v²/r', 'a_ц = \frac{v^2}{r}', '{"ac":"v*v/r"}', '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":true},{"name":"v","label":"Скорость (v)","unit":"м/с","required":true},{"name":"r","label":"Радиус (r)","unit":"м","required":true}]}', '{"fields":[{"name":"ac","label":"Ускорение (aц)","unit":"м/с²"},{"name":"Fc","label":"Сила (Fц)","unit":"Н"}]}', 5),

(14, 3, 'Потенциальная энергия', 'Eп = m·g·h', 'E_п = m \cdot g \cdot h', '{"Ep":"m*g*h"}', '{"fields":[{"name":"m","label":"Масса (m)","unit":"кг","required":true},{"name":"h","label":"Высота (h)","unit":"м","required":true}]}', '{"fields":[{"name":"Ep","label":"Потенц. энергия","unit":"Дж"}]}', 3),

(15, 3, 'Мощность', 'P = A/t', 'P = \frac{A}{t}', '{"P":"A/t"}', '{"fields":[{"name":"A","label":"Работа (A)","unit":"Дж","required":true},{"name":"t","label":"Время (t)","unit":"с","required":true}]}', '{"fields":[{"name":"P","label":"Мощность","unit":"Вт"}]}', 4),

(16, 4, 'Изменение импульса', 'Δp = F·Δt', '\Delta p = F \cdot \Delta t', '{"dp":"F*t"}', '{"fields":[{"name":"F","label":"Сила (F)","unit":"Н","required":true},{"name":"t","label":"Время (Δt)","unit":"с","required":true}]}', '{"fields":[{"name":"dp","label":"Изм. импульса","unit":"кг·м/с"}]}', 2),

(17, 4, 'Абсолютно неупругий удар', 'u = (m₁v₁ + m₂v₂)/(m₁+m₂)', 'u = \frac{m_1 v_1 + m_2 v_2}{m_1 + m_2}', '{"u":"(m1*v1+m2*v2)/(m1+m2)"}', '{"fields":[{"name":"m1","label":"Масса 1 (m₁)","unit":"кг","required":true},{"name":"v1","label":"Скорость 1 (v₁)","unit":"м/с","required":true},{"name":"m2","label":"Масса 2 (m₂)","unit":"кг","required":true},{"name":"v2","label":"Скорость 2 (v₂)","unit":"м/с","required":true}]}', '{"fields":[{"name":"u","label":"Общая скорость","unit":"м/с"}]}', 3),

(18, 5, 'Давление твёрдого тела', 'p = F/S', 'p = \frac{F}{S}', '{"p":"F/S"}', '{"fields":[{"name":"F","label":"Сила (F)","unit":"Н","required":true},{"name":"S","label":"Площадь (S)","unit":"м²","required":true}]}', '{"fields":[{"name":"p","label":"Давление","unit":"Па"}]}', 1),

(19, 5, 'Гидростатическое давление', 'p = ρ·g·h', 'p = \rho \cdot g \cdot h', '{"p":"rho*g*h"}', '{"fields":[{"name":"rho","label":"Плотность (ρ)","unit":"кг/м³","required":true},{"name":"h","label":"Глубина (h)","unit":"м","required":true}]}', '{"fields":[{"name":"p","label":"Давление","unit":"Па"}]}', 2),

(20, 5, 'Сила Архимеда', 'Fₐ = ρ·g·V', 'F_a = \rho \cdot g \cdot V', '{"Fa":"rho*g*V"}', '{"fields":[{"name":"rho","label":"Плотность (ρ)","unit":"кг/м³","required":true},{"name":"V","label":"Объём (V)","unit":"м³","required":true}]}', '{"fields":[{"name":"Fa","label":"Сила Архимеда","unit":"Н"}]}', 3);

-- 20 тестовых расчетов для демонстрации дашборда администратора и страниц истории
INSERT INTO calculations (user_id, problem_type_id, input_data, result_data) VALUES
(2, 1, '{"v": 10, "t": 5}', '{"s": 50}'),
(2, 1, '{"s": 100, "t": 20}', '{"v": 5}'),
(3, 2, '{"a": 2, "t": 10}', '{"s": 100, "v": 20}'),
(2, 3, '{"m": 15, "a": 2}', '{"F": 30}'),
(3, 3, '{"F": 100, "m": 25}', '{"a": 4}'),
(2, 4, '{"m": 5}', '{"F": 49}'),
(3, 4, '{"m": 12}', '{"F": 117.6}'),
(2, 5, '{"m": 10, "v": 5}', '{"E": 125}'),
(2, 6, '{"F": 50, "s": 10}', '{"A": 500}'),
(3, 6, '{"F": 120, "s": 5}', '{"A": 600}'),
(2, 7, '{"m": 80, "v": 3}', '{"p": 240}'),
(2, 7, '{"m": 1500, "v": 20}', '{"p": 30000}'),
(2, 1, '{"v": 60, "t": 2}', '{"s": 120}'),
(3, 1, '{"s": 500, "v": 25}', '{"t": 20}'),
(2, 2, '{"a": 9.8, "t": 3}', '{"s": 44.1, "v": 29.4}'),
(2, 3, '{"m": 1000, "a": 1.5}', '{"F": 1500}'),
(3, 4, '{"m": 60}', '{"F": 588}'),
(2, 5, '{"m": 2, "v": 10}', '{"E": 100}'),
(2, 6, '{"F": 10, "s": 100}', '{"A": 1000}'),
(3, 7, '{"m": 0.5, "v": 400}', '{"p": 200}');