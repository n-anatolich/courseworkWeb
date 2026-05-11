<?php
class PhysicsCalculator {
    public static function calculate($problemId, $inputs) {
        $result = [
            'success' => false,
            'error' => '',
            'results' => [],
            'steps' => [],
            'formula' => ''
        ];

        try {
            switch ($problemId) {
                case 1: // Равномерное движение (s, v, t)
                    $v = $inputs['v'] ?? null;
                    $t = $inputs['t'] ?? null;
                    $s = $inputs['s'] ?? null;

                    $providedCount = (!is_null($v) ? 1 : 0) + (!is_null($t) ? 1 : 0) + (!is_null($s) ? 1 : 0);
                    if ($providedCount !== 2) throw new Exception("Для расчета равномерного движения введите ровно 2 известных значения.");
                    if ((!is_null($t) && $t < 0) || (!is_null($s) && $s < 0)) throw new Exception("Время и расстояние не могут быть отрицательными.");

                    if (!is_null($v) && !is_null($t)) {
                        $res = $v * $t;
                        $result['formula'] = "s = v × t";
                        $result['results']['s'] = $res;
                        $result['steps'][] = "Формула расстояния: s = v × t";
                        $result['steps'][] = "Подставляем значения: s = {$v} × {$t}";
                        $result['steps'][] = "Результат: s = " . round($res, 4) . " м";
                    } elseif (!is_null($s) && !is_null($t)) {
                        if ($t == 0) throw new Exception("Деление на ноль! Время не может быть равно 0.");
                        $res = $s / $t;
                        $result['formula'] = "v = s / t";
                        $result['results']['v'] = $res;
                        $result['steps'][] = "Формула скорости: v = s / t";
                        $result['steps'][] = "Подставляем значения: v = {$s} / {$t}";
                        $result['steps'][] = "Результат: v = " . round($res, 4) . " м/с";
                    } elseif (!is_null($s) && !is_null($v)) {
                        if ($v == 0) throw new Exception("Деление на ноль! Скорость не может быть равна 0.");
                        $res = $s / $v;
                        $result['formula'] = "t = s / v";
                        $result['results']['t'] = $res;
                        $result['steps'][] = "Формула времени: t = s / v";
                        $result['steps'][] = "Подставляем значения: t = {$s} / {$v}";
                        $result['steps'][] = "Результат: t = " . round($res, 4) . " с";
                    }
                    $result['success'] = true;
                    break;

                case 2: // Равноускоренное движение (s = at²/2, v = at)
                    $a = $inputs['a'] ?? null;
                    $t = $inputs['t'] ?? null;

                    if (is_null($a) || is_null($t)) throw new Exception("Введите ускорение и время.");
                    if ($t < 0) throw new Exception("Время не может быть отрицательным.");

                    $s = ($a * pow($t, 2)) / 2;
                    $v = $a * $t;

                    $result['formula'] = "s = a·t²/2, v = a·t";
                    $result['results']['s'] = $s;
                    $result['results']['v'] = $v;
                    $result['steps'][] = "Расчет расстояния: s = ({$a} × {$t}²) / 2 = " . round($s, 4) . " м";
                    $result['steps'][] = "Расчет скорости: v = {$a} × {$t} = " . round($v, 4) . " м/с";
                    $result['success'] = true;
                    break;

                case 3: // Второй закон Ньютона (F, m, a)
                    $F = $inputs['F'] ?? null;
                    $m = $inputs['m'] ?? null;
                    $a = $inputs['a'] ?? null;

                    $providedCount = (!is_null($F) ? 1 : 0) + (!is_null($m) ? 1 : 0) + (!is_null($a) ? 1 : 0);
                    if ($providedCount !== 2) throw new Exception("Введите ровно 2 значения для применения Второго закона Ньютона.");
                    if (!is_null($m) && $m <= 0) throw new Exception("Масса должна быть больше нуля.");

                    if (!is_null($m) && !is_null($a)) {
                        $res = $m * $a;
                        $result['formula'] = "F = m × a";
                        $result['results']['F'] = $res;
                        $result['steps'][] = "Используем базовую формулу: F = m × a";
                        $result['steps'][] = "F = {$m} × {$a} = " . round($res, 4) . " Н";
                    } elseif (!is_null($F) && !is_null($m)) {
                        $res = $F / $m;
                        $result['formula'] = "a = F / m";
                        $result['results']['a'] = $res;
                        $result['steps'][] = "Выражаем ускорение: a = F / m";
                        $result['steps'][] = "a = {$F} / {$m} = " . round($res, 4) . " м/с²";
                    } elseif (!is_null($F) && !is_null($a)) {
                        if ($a == 0) throw new Exception("Ускорение не может быть 0 при поиске массы (деление на ноль).");
                        $res = $F / $a;
                        $result['formula'] = "m = F / a";
                        $result['results']['m'] = $res;
                        $result['steps'][] = "Выражаем массу: m = F / a";
                        $result['steps'][] = "m = {$F} / {$a} = " . round($res, 4) . " кг";
                    }
                    $result['success'] = true;
                    break;

                case 4: // Сила тяжести (F = mg)
                    $m = $inputs['m'] ?? null;
                    if (is_null($m)) throw new Exception("Введите массу.");
                    if ($m < 0) throw new Exception("Масса не может быть отрицательной.");

                    $g = 9.8; // Для расширения можно тянуть из БД
                    $F = $m * $g;
                    
                    $result['formula'] = "F = m × g";
                    $result['results']['F'] = $F;
                    $result['steps'][] = "Формула: F = m × g (где g ≈ 9.8 м/с²)";
                    $result['steps'][] = "Подставляем значения: F = {$m} × 9.8";
                    $result['steps'][] = "Результат: F = " . round($F, 4) . " Н";
                    $result['success'] = true;
                    break;

                case 5: // Кинетическая энергия (E = mv²/2)
                    $m = $inputs['m'] ?? null;
                    $v = $inputs['v'] ?? null;
                    if (is_null($m) || is_null($v)) throw new Exception("Введите массу и скорость.");
                    if ($m < 0) throw new Exception("Масса не может быть отрицательной.");

                    $E = ($m * pow($v, 2)) / 2;
                    
                    $result['formula'] = "E = m·v²/2";
                    $result['results']['E'] = $E;
                    $result['steps'][] = "Формула: E = (m × v²) / 2";
                    $result['steps'][] = "Подставляем: E = ({$m} × {$v}²) / 2";
                    $result['steps'][] = "Результат: E = " . round($E, 4) . " Дж";
                    $result['success'] = true;
                    break;

                default:
                    throw new Exception("Неизвестный тип задачи.");
            }
        } catch (Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}