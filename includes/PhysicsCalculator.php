<?php
class PhysicsCalculator {
      /**
     * Основной метод для динамического расчета физических величин.
     * Анализирует доступные формулы и известные переменные (ввод пользователя + константы БД),
     * пошагово вычисляя все возможные неизвестные до полного решения задачи.
     *
     * @param int $problemId ID базового типа задачи из базы данных.
     * @param array $inputs Ассоциативный массив введенных пользователем данных (ключ => значение).
     * @param array|null $problemConfig Конфигурация задачи (формулы, поля вывода, константы).
     * @return array Ассоциативный массив с результатами: флаг успеха, ошибки, вычисленные значения и пошаговое решение (HTML/LaTeX).
     * @throws Exception Если недостаточно данных для расчета или обнаружена ошибка в конфигурации.
     */
    public static function calculate($problemId, $inputs, $problemConfig = null) {
        $result = [
            'success' => false,
            'error' => '',
            'results' => [],
            'steps' => [],
            'formula' => ''
        ];

        try {
                        // ЭТАП 1 и 3: Анализ конфигурации и поиск ВСЕХ возможных переменных
            if ($problemConfig && !empty($problemConfig['formula_expression'])) {
                $expressions = $problemConfig['formula_expression'];
                $formulasToCalculate = [];
                $constants = $problemConfig['constants'] ?? [];
                
                // Объединяем константы и ввод пользователя в единый массив известных данных
                $knownVariables = array_merge($constants, $inputs);

                // Проходим по формулам в цикле (вычисление одной переменной может открыть путь к другой)
                $calculatedSomething = true;
                while ($calculatedSomething) {
                    $calculatedSomething = false;
                    foreach ($expressions as $target => $formula) {
                        // Если переменная еще не найдена и не добавлена в очередь на расчет
                        if (!isset($knownVariables[$target]) && !isset($formulasToCalculate[$target])) {
                            
                            preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);
                            $requiredVars = array_unique($matches[0]);
                            
                            $canCalculate = true;
                            foreach ($requiredVars as $var) {
                                $mathFunctions = ['sin', 'cos', 'tan', 'sqrt', 'pow', 'pi'];
                                if (in_array($var, $mathFunctions)) continue;
                                
                                // Проверяем, есть ли нужная переменная в известных
                                if (!isset($knownVariables[$var])) {
                                    $canCalculate = false;
                                    break;
                                }
                            }

                            if ($canCalculate) {
                                $formulasToCalculate[$target] = $formula;
                                // Временно помечаем как "известную", чтобы алгоритм мог использовать её для поиска следующих формул
                                $knownVariables[$target] = 0; 
                                $calculatedSomething = true;
                            }
                        }
                    }
                }

                if (empty($formulasToCalculate)) {
                    throw new Exception("Недостаточно данных для расчета. Проверьте введенные значения.");
                }

                // ЭТАП 2: Подстановка, вычисление и генерация шагов решения
                $result['success'] = true;
                // Сбрасываем пул переменных к реальным значениям для начала математики
                $allVariables = array_merge($constants, $inputs); 

                foreach ($formulasToCalculate as $target => $formulaToUse) {
                    $substitutedString = $formulaToUse;
                    // Сортируем ключи по длине (чтобы "v0" заменялось раньше "v")
                    uksort($allVariables, function($a, $b) { return strlen($b) - strlen($a); });
                    
                    foreach ($allVariables as $key => $val) {
                        $strVal = $val < 0 ? "(" . $val . ")" : $val;
                        $substitutedString = str_replace($key, $strVal, $substitutedString);
                    }

                    // Вызов нашего ядра
                    $calculatedValue = self::evaluateExpression($formulaToUse, $allVariables);

                    // Добавляем вычисленный результат в пул переменных. 
                    // Если следующей формуле понадобится эта переменная, она уже будет тут!
                    $allVariables[$target] = $calculatedValue;

                    // Форматирование для красивого вывода
                    $displayValue = (abs($calculatedValue) > 10000 || (abs($calculatedValue) < 0.01 && $calculatedValue != 0)) 
                        ? sprintf("%.4e", $calculatedValue) 
                        : round($calculatedValue, 4);
                        
                    // Шаг 3.2: Динамическая генерация подробных шагов решения
                    // Заменяем программистские символы на математические для красивого рендера MathJax
                    $prettyFormula = str_replace(['*', '/'], [' \cdot ', ' \div '], $formulaToUse);
                    // Делаем красивые индексы, например, v0 превращаем в v_0
                    $prettyFormula = preg_replace('/([a-zA-Z])(\d+)/', '$1_$2', $prettyFormula);
                    
                    $prettySubst = str_replace(['*', '/'], [' \cdot ', ' \div '], $substitutedString);
                    $prettySubst = preg_replace('/([a-zA-Z])(\d+)/', '$1_$2', $prettySubst);
                    
                    $result['steps'][] = "<span style='color: var(--primary-color);'><b>► Находим {$target}:</b></span>";
                    $result['steps'][] = "Формула: \\( {$target} = {$prettyFormula} \\)";
                    $result['steps'][] = "Подставляем значения: \\( {$target} = {$prettySubst} \\)";
                    $result['steps'][] = "Результат: \\( {$target} = {$displayValue} \\)<br>";
                    
                    $result['results'][$target] = $calculatedValue;
                }
                
                return $result; // Успешный выход из метода
            }

            // Если мы дошли сюда, значит формула не была найдена
            throw new Exception("Тип задачи не настроен для динамического расчета.");

        } catch (Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
            return $result;
        }
    }

        /**
     * Подготавливает математическое выражение к вычислению.
     * Заменяет буквенные переменные (например, "m", "v0") на их числовые значения.
     * Сортирует ключи по убыванию длины во избежание конфликтов частичных замен (например, чтобы "v" не заменилось внутри "v0").
     *
     * @param string $expr Математическое выражение в строковом виде.
     * @param array $vars Ассоциативный массив известных переменных со значениями.
     * @return float Результат вычисления математического выражения.
     * @throws Exception Если после подстановки остались нераспознанные буквенные символы (неизвестные переменные).
     */
    private static function evaluateExpression($expr, $vars = []) {
        uksort($vars, function($a, $b) { return strlen($b) - strlen($a); });
        foreach ($vars as $key => $val) {
            $valStr = $val < 0 ? "(" . $val . ")" : $val;
            $expr = str_replace($key, $valStr, $expr);
        }
        $expr = str_replace([' ', ','], ['', '.'], $expr);
        $testExpr = preg_replace('/(?<=\d)[eE][+-]?\d+/', '', $expr);
        if (preg_match('/[a-df-zA-DF-Z_]/', $testExpr, $matches)) {
            throw new Exception("Неизвестная переменная в формуле: " . $matches[0]);
        }
        return self::calculateNode($expr);
    }
    
        /**
     * Рекурсивный парсер и вычислитель математических строк.
     * Безопасная альтернатива функции eval(). Учитывает приоритет операций: 
     * сначала раскрывает скобки, затем выполняет умножение/деление, в конце — сложение/вычитание.
     *
     * @param string $expr Строка с математическим выражением (только числа и операторы).
     * @return float Итоговый вычисленный результат.
     * @throws Exception При обнаружении попытки деления на ноль.
     */
    private static function calculateNode($expr) {
        while (preg_match('/\(([^\(\)]+)\)/', $expr, $matches)) {
            $evaluated = self::calculateNode($matches[1]);
            $expr = str_replace($matches[0], $evaluated, $expr);
        }
        if (str_starts_with($expr, '-')) {
            $expr = '0' . $expr;
        }
        $expr = str_replace(['--', '+-', '-+'], ['+', '-', '-'], $expr);
        $n = '(-?\d+(?:\.\d+)?(?:e[-+]?\d+)?)';

        while (preg_match("/{$n}\s*([\*\/])\s*{$n}/i", $expr, $matches)) {
            $val1 = (float)$matches[1];
            $val2 = (float)$matches[3];
            if ($matches[2] === '/') {
                if ($val2 == 0) throw new Exception("Обнаружено деление на ноль!");
                $res = $val1 / $val2;
            } else {
                $res = $val1 * $val2;
            }
            $expr = preg_replace('/' . preg_quote($matches[0], '/') . '/', (string)$res, $expr, 1);
            $expr = str_replace(['--', '+-'], ['+', '-'], $expr);
        }

        while (preg_match("/{$n}\s*([\+\-])\s*{$n}/i", $expr, $matches)) {
            $val1 = (float)$matches[1];
            $val2 = (float)$matches[3];
            $res = ($matches[2] === '+') ? ($val1 + $val2) : ($val1 - $val2);
            $expr = preg_replace('/' . preg_quote($matches[0], '/') . '/', (string)$res, $expr, 1);
            $expr = str_replace(['--', '+-'], ['+', '-'], $expr);
        }
        return (float)$expr;
    }
}