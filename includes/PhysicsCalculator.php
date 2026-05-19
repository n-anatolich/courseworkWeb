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
                        $result['formula'] = "\\( s = v \\cdot t \\)";
                        $result['results']['s'] = $res;
                        $result['steps'][] = "Формула расстояния: \\( s = v \\cdot t \\)";
                        $result['steps'][] = "Подставляем значения: \\( s = {$v} \\cdot {$t} \\)";
                        $result['steps'][] = "Результат: \\( s = " . round($res, 4) . " \\) м";
                    } elseif (!is_null($s) && !is_null($t)) {
                        if ($t == 0) throw new Exception("Деление на ноль! Время не может быть равно 0.");
                        $res = $s / $t;
                        $result['formula'] = "\\( v = \\frac{s}{t} \\)";
                        $result['results']['v'] = $res;
                        $result['steps'][] = "Формула скорости: \\( v = \\frac{s}{t} \\)";
                        $result['steps'][] = "Подставляем значения: \\( v = \\frac{{$s}}{{{$t}}} \\)";
                        $result['steps'][] = "Результат: \\( v = " . round($res, 4) . " \\) м/с";
                    } elseif (!is_null($s) && !is_null($v)) {
                        if ($v == 0) throw new Exception("Деление на ноль! Скорость не может быть равна 0.");
                        $res = $s / $v;
                        $result['formula'] = "\\( t = \\frac{s}{v} \\)";
                        $result['results']['t'] = $res;
                        $result['steps'][] = "Формула времени: \\( t = \\frac{s}{v} \\)";
                        $result['steps'][] = "Подставляем значения: \\( t = \\frac{{$s}}{{{$v}}} \\)";
                        $result['steps'][] = "Результат: \\( t = " . round($res, 4) . " \\) с";
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

                    $result['formula'] = "\\( s = \\frac{a \\cdot t^2}{2}, \\; v = a \\cdot t \\)";
                    $result['results']['s'] = $s;
                    $result['results']['v'] = $v;
                    $result['steps'][] = "Расчет расстояния: \\( s = \\frac{{$a} \\cdot {$t}^2}{2} = " . round($s, 4) . " \\) м";
                    $result['steps'][] = "Расчет скорости: \\( v = {$a} \\cdot {$t} = " . round($v, 4) . " \\) м/с";
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
                        $result['formula'] = "\\( F = m \\cdot a \\)";
                        $result['results']['F'] = $res;
                        $result['steps'][] = "Используем базовую формулу: \\( F = m \\cdot a \\)";
                        $result['steps'][] = "Расчет: \\( F = {$m} \\cdot {$a} = " . round($res, 4) . " \\) Н";
                    } elseif (!is_null($F) && !is_null($m)) {
                        $res = $F / $m;
                        $result['formula'] = "\\( a = \\frac{F}{m} \\)";
                        $result['results']['a'] = $res;
                        $result['steps'][] = "Выражаем ускорение: \\( a = \\frac{F}{m} \\)";
                        $result['steps'][] = "Расчет: \\( a = \\frac{{$F}}{{{$m}}} = " . round($res, 4) . " \\) м/с²";
                    } elseif (!is_null($F) && !is_null($a)) {
                        if ($a == 0) throw new Exception("Ускорение не может быть 0 при поиске массы (деление на ноль).");
                        $res = $F / $a;
                        $result['formula'] = "\\( m = \\frac{F}{a} \\)";
                        $result['results']['m'] = $res;
                        $result['steps'][] = "Выражаем массу: \\( m = \\frac{F}{a} \\)";
                        $result['steps'][] = "Расчет: \\( m = \\frac{{$F}}{{{$a}}} = " . round($res, 4) . " \\) кг";
                    }
                    $result['success'] = true;
                    break;

                case 4: // Сила тяжести (F = mg)
                    $m = $inputs['m'] ?? null;
                    if (is_null($m)) throw new Exception("Введите массу.");
                    if ($m < 0) throw new Exception("Масса не может быть отрицательной.");

                    $g = 9.8;
                    $F = $m * $g;
                    
                    $result['formula'] = "\\( F = m \\cdot g \\)";
                    $result['results']['F'] = $F;
                    $result['steps'][] = "Формула: \\( F = m \\cdot g \\) (где \\( g \\approx 9.8 \\) м/с²)";
                    $result['steps'][] = "Подставляем значения: \\( F = {$m} \\cdot 9.8 \\)";
                    $result['steps'][] = "Результат: \\( F = " . round($F, 4) . " \\) Н";
                    $result['success'] = true;
                    break;

                case 5: // Кинетическая энергия (E = mv²/2)
                    $m = $inputs['m'] ?? null;
                    $v = $inputs['v'] ?? null;
                    if (is_null($m) || is_null($v)) throw new Exception("Введите массу и скорость.");
                    if ($m < 0) throw new Exception("Масса не может быть отрицательной.");

                    $E = ($m * pow($v, 2)) / 2;
                    
                    $result['formula'] = "\\( E = \\frac{m \\cdot v^2}{2} \\)";
                    $result['results']['E'] = $E;
                    $result['steps'][] = "Формула: \\( E = \\frac{m \\cdot v^2}{2} \\)";
                    $result['steps'][] = "Подставляем: \\( E = \\frac{{$m} \\cdot {$v}^2}{2} = " . round($E, 4) . " \\) Дж";
                    $result['success'] = true;
                    break;

                case 6: // Механическая работа (A = Fs)
                    $F = $inputs['F'] ?? null;
                    $s = $inputs['s'] ?? null;
                    if (is_null($F) || is_null($s)) throw new Exception("Введите силу и перемещение.");
                    
                    $result['formula'] = "\\( A = F \\cdot s \\)";
                    $result['results']['A'] = $F * $s;
                    $result['steps'][] = "Формула: \\( A = F \\cdot s \\)";
                    $result['steps'][] = "Расчет: \\( A = {$F} \\cdot {$s} = " . round($result['results']['A'], 4) . " \\) Дж";
                    $result['success'] = true;
                    break;

                case 7: // Импульс (p = mv)
                    $m = $inputs['m'] ?? null;
                    $v = $inputs['v'] ?? null;
                    if (is_null($m) || is_null($v)) throw new Exception("Введите массу и скорость.");
                    
                    $result['formula'] = "\\( p = m \\cdot v \\)";
                    $result['results']['p'] = $m * $v;
                    $result['steps'][] = "Формула: \\( p = m \\cdot v \\)";
                    $result['steps'][] = "Расчет: \\( p = {$m} \\cdot {$v} = " . round($result['results']['p'], 4) . " \\) кг·м/с";
                    $result['success'] = true;
                    break;
                
                case 8: // Равноускоренное движение (с нач. скоростью)
                    $v0 = $inputs['v0'] ?? null; $a = $inputs['a'] ?? null; $t = $inputs['t'] ?? null;
                    if (is_null($v0) || is_null($a) || is_null($t)) throw new Exception("Введите начальную скорость, ускорение и время.");
                    if ($t < 0) throw new Exception("Время не может быть отрицательным.");
                    $s = $v0 * $t + ($a * pow($t, 2)) / 2;
                    $v = $v0 + $a * $t;
                    $result['formula'] = "\\( s = v_0 \\cdot t + \\frac{a \\cdot t^2}{2} \\)";
                    $result['results']['s'] = $s; $result['results']['v'] = $v;
                    $result['steps'][] = "Расстояние: \\( s = {$v0} \\times {$t} + \\frac{{$a} \\times {$t}^2}{2} = " . round($s, 4) . " \\) м";
                    $result['steps'][] = "Скорость: \\( v = {$v0} + {$a} \\times {$t} = " . round($v, 4) . " \\) м/с";
                    $result['success'] = true; 
                    break;

                case 9: // Свободное падение
                    $t = $inputs['t'] ?? null;
                    if (is_null($t)) throw new Exception("Введите время.");
                    if ($t < 0) throw new Exception("Время не может быть отрицательным.");
                    $g = 9.8; $h = ($g * pow($t, 2)) / 2; $v = $g * $t;
                    $result['formula'] = "\\( h = \\frac{g \\cdot t^2}{2} \\)";
                    $result['results']['h'] = $h; $result['results']['v'] = $v;
                    $result['steps'][] = "Высота: \\( h = \\frac{9.8 \\times {$t}^2}{2} = " . round($h, 4) . " \\) м";
                    $result['steps'][] = "Скорость: \\( v = 9.8 \\times {$t} = " . round($v, 4) . " \\) м/с";
                    $result['success'] = true; 
                    break;

                case 10: // Движение брошенного тела
                    $v0 = $inputs['v0'] ?? null; $t = $inputs['t'] ?? null;
                    if (is_null($v0) || is_null($t)) throw new Exception("Введите начальную скорость и время.");
                    if ($t < 0) throw new Exception("Время не может быть отрицательным.");
                    $g = 9.8; $h = $v0 * $t - ($g * pow($t, 2)) / 2;
                    $result['formula'] = "\\( h = v_0 \\cdot t - \\frac{g \\cdot t^2}{2} \\)";
                    $result['results']['h'] = $h;
                    $result['steps'][] = "Высота: \\( h = {$v0} \\times {$t} - \\frac{9.8 \\times {$t}^2}{2} = " . round($h, 4) . " \\) м";
                    $result['success'] = true; 
                    break;

                case 11: // Сила трения
                    $mu = $inputs['mu'] ?? null; $N = $inputs['N'] ?? null;
                    if (is_null($mu) || is_null($N)) throw new Exception("Введите коэффициент трения и силу реакции.");
                    $F = $mu * $N;
                    $result['formula'] = "\\( F_{тр} = \\mu \\cdot N \\)";
                    $result['results']['F'] = $F;
                    $result['steps'][] = "Расчет: \\( F_{тр} = {$mu} \\times {$N} = " . round($F, 4) . " \\) Н";
                    $result['success'] = true; 
                    break;

                case 12: // Закон всемирного тяготения
                    $m1 = $inputs['m1'] ?? null; $m2 = $inputs['m2'] ?? null; $r = $inputs['r'] ?? null;
                    if (is_null($m1) || is_null($m2) || is_null($r)) throw new Exception("Введите обе массы и расстояние.");
                    if ($r <= 0) throw new Exception("Расстояние должно быть больше нуля.");
                    $G = 6.6743e-11; $F = $G * $m1 * $m2 / pow($r, 2);
                    $result['formula'] = "\\( F = G \\frac{m_1 \\cdot m_2}{r^2} \\)";
                    $result['results']['F'] = $F;
                    $result['steps'][] = "Расчет: \\( F = 6.674 \\times 10^{-11} \\frac{{$m1} \\times {$m2}}{{$r}^2} = " . sprintf("%.4e", $F) . " \\) Н";
                    $result['success'] = true; 
                    break;

                case 13: // Движение по окружности
                    $m = $inputs['m'] ?? null; $v = $inputs['v'] ?? null; $r = $inputs['r'] ?? null;
                    if (is_null($m) || is_null($v) || is_null($r)) throw new Exception("Введите массу, скорость и радиус.");
                    if ($r <= 0) throw new Exception("Радиус должен быть больше нуля.");
                    $ac = pow($v, 2) / $r; $Fc = $m * $ac;
                    $result['formula'] = "\\( a_ц = \\frac{v^2}{r}, \\; F_ц = m \\cdot a_ц \\)";
                    $result['results']['ac'] = $ac; $result['results']['Fc'] = $Fc;
                    $result['steps'][] = "Ускорение: \\( a_ц = \\frac{{$v}^2}{{$r}} = " . round($ac, 4) . " \\) м/с²";
                    $result['steps'][] = "Сила: \\( F_ц = {$m} \\times " . round($ac, 4) . " = " . round($Fc, 4) . " \\) Н";
                    $result['success'] = true; 
                    break;

                case 14: // Потенциальная энергия
                    $m = $inputs['m'] ?? null; $h = $inputs['h'] ?? null;
                    if (is_null($m) || is_null($h)) throw new Exception("Введите массу и высоту.");
                    $g = 9.8; $Ep = $m * $g * $h;
                    $result['formula'] = "\\( E_п = m \\cdot g \\cdot h \\)";
                    $result['results']['Ep'] = $Ep;
                    $result['steps'][] = "Расчет: \\( E_п = {$m} \\times 9.8 \\times {$h} = " . round($Ep, 4) . " \\) Дж";
                    $result['success'] = true; 
                    break;

                case 15: // Мощность
                    $A = $inputs['A'] ?? null; $t = $inputs['t'] ?? null;
                    if (is_null($A) || is_null($t)) throw new Exception("Введите работу и время.");
                    if ($t == 0) throw new Exception("Время не может быть равно нулю.");
                    $P = $A / $t;
                    $result['formula'] = "\\( P = \\frac{A}{t} \\)";
                    $result['results']['P'] = $P;
                    $result['steps'][] = "Расчет: \\( P = \\frac{{$A}}{{$t}} = " . round($P, 4) . " \\) Вт";
                    $result['success'] = true; 
                    break;

                case 16: // Изменение импульса
                    $F = $inputs['F'] ?? null; $t = $inputs['t'] ?? null;
                    if (is_null($F) || is_null($t)) throw new Exception("Введите силу и время.");
                    $dp = $F * $t;
                    $result['formula'] = "\\( \\Delta p = F \\cdot \\Delta t \\)";
                    $result['results']['dp'] = $dp;
                    $result['steps'][] = "Расчет: \\( \\Delta p = {$F} \\times {$t} = " . round($dp, 4) . " \\) кг·м/с";
                    $result['success'] = true; 
                    break;

                case 17: // Абсолютно неупругий удар
                    $m1 = $inputs['m1'] ?? null; $v1 = $inputs['v1'] ?? null;
                    $m2 = $inputs['m2'] ?? null; $v2 = $inputs['v2'] ?? null;
                    if (is_null($m1) || is_null($v1) || is_null($m2) || is_null($v2)) throw new Exception("Введите все массы и скорости.");
                    if ($m1 + $m2 == 0) throw new Exception("Суммарная масса не может быть равна нулю.");
                    $u = ($m1 * $v1 + $m2 * $v2) / ($m1 + $m2);
                    $result['formula'] = "\\( u = \\frac{m_1 v_1 + m_2 v_2}{m_1 + m_2} \\)";
                    $result['results']['u'] = $u;
                    $result['steps'][] = "Расчет: \\( u = \\frac{{$m1}\\times{$v1} + {$m2}\\times{$v2}}{{$m1} + {$m2}} = " . round($u, 4) . " \\) м/с";
                    $result['success'] = true; 
                    break;

                case 18: // Давление твёрдого тела
                    $F = $inputs['F'] ?? null; $S = $inputs['S'] ?? null;
                    if (is_null($F) || is_null($S)) throw new Exception("Введите силу и площадь.");
                    if ($S == 0) throw new Exception("Площадь не может быть равна нулю.");
                    $p = $F / $S;
                    $result['formula'] = "\\( p = \\frac{F}{S} \\)";
                    $result['results']['p'] = $p;
                    $result['steps'][] = "Расчет: \\( p = \\frac{{$F}}{{$S}} = " . round($p, 4) . " \\) Па";
                    $result['success'] = true; 
                    break;

                case 19: // Гидростатическое давление
                    $rho = $inputs['rho'] ?? null; $h = $inputs['h'] ?? null;
                    if (is_null($rho) || is_null($h)) throw new Exception("Введите плотность и глубину.");
                    $g = 9.8; $p = $rho * $g * $h;
                    $result['formula'] = "\\( p = \\rho \\cdot g \\cdot h \\)";
                    $result['results']['p'] = $p;
                    $result['steps'][] = "Расчет: \\( p = {$rho} \\times 9.8 \\times {$h} = " . round($p, 4) . " \\) Па";
                    $result['success'] = true; 
                    break;

                case 20: // Сила Архимеда
                    $rho = $inputs['rho'] ?? null; $V = $inputs['V'] ?? null;
                    if (is_null($rho) || is_null($V)) throw new Exception("Введите плотность и объём.");
                    $g = 9.8; $Fa = $rho * $g * $V;
                    $result['formula'] = "\\( F_a = \\rho \\cdot g \\cdot V \\)";
                    $result['results']['Fa'] = $Fa;
                    $result['steps'][] = "Расчет: \\( F_a = {$rho} \\times 9.8 \\times {$V} = " . round($Fa, 4) . " \\) Н";
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