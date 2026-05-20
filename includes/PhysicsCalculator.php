<?php
class PhysicsCalculator {
    public static function calculate($problemId, $inputs, $problemConfig = null) {
        $result = [
            'success' => false,
            'error' => '',
            'results' => [],
            'steps' => [],
            'formula' => ''
        ];

        try {
            // ЭТАП 1: Анализ динамической конфигурации
            if ($problemConfig && !empty($problemConfig['formula_expression'])) {
                $expressions = $problemConfig['formula_expression'];
                $targetVariable = null;
                $formulaToUse = null;

                foreach ($expressions as $target => $formula) {
                    if (!isset($inputs[$target])) {
                        preg_match_all('/[a-zA-Z_][a-zA-Z0-9_]*/', $formula, $matches);
                        $requiredVars = array_unique($matches[0]);
                        
                        $canCalculate = true;
                        foreach ($requiredVars as $var) {
                            $mathFunctions = ['sin', 'cos', 'tan', 'sqrt', 'pow', 'pi'];
                            if (in_array($var, $mathFunctions)) continue;
                            if (!isset($inputs[$var])) {
                                $canCalculate = false;
                                break;
                            }
                        }

                        if ($canCalculate) {
                            $targetVariable = $target;
                            $formulaToUse = $formula;
                            break; 
                        }
                    }
                }

                if (!$formulaToUse) {
                    throw new Exception("Недостаточно данных для расчета. Проверьте введенные значения.");
                }

                // ЭТАП 2: Подстановка и вычисление
                $constants = $problemConfig['constants'] ?? [];
                $allVariables = array_merge($constants, $inputs);

                $substitutedString = $formulaToUse;
                uksort($allVariables, function($a, $b) { return strlen($b) - strlen($a); });
                
                foreach ($allVariables as $key => $val) {
                    $strVal = $val < 0 ? "(" . $val . ")" : $val;
                    $substitutedString = str_replace($key, $strVal, $substitutedString);
                }

                $calculatedValue = self::evaluateExpression($formulaToUse, $allVariables);

                $result['success'] = true;
                $result['formula'] = "\\( " . $targetVariable . " = " . $formulaToUse . " \\)";
                $result['steps'][] = "Базовая формула: \\( " . $targetVariable . " = " . $formulaToUse . " \\)";
                $result['steps'][] = "Подставляем значения: \\( " . $targetVariable . " = " . $substitutedString . " \\)";
                
                $displayValue = (abs($calculatedValue) > 10000 || (abs($calculatedValue) < 0.01 && $calculatedValue != 0)) 
                    ? sprintf("%.4e", $calculatedValue) 
                    : round($calculatedValue, 4);
                    
                $result['steps'][] = "Результат: \\( " . $targetVariable . " = " . $displayValue . " \\)";
                $result['results'][$targetVariable] = $calculatedValue;
                
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