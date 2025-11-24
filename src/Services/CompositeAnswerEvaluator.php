<?php

namespace Amryami\Assessments\Services;

class CompositeAnswerEvaluator
{
    public function evaluate(array $inputDef, ?string $value): ?bool
    {
        // Default: manual review (no auto-scoring). Return null to indicate no auto-eval.
        $mode = $inputDef['eval']['mode'] ?? 'manual';
        if ($mode === 'manual') return null;
        $val = (string)($value ?? '');
        if ($mode === 'exact') {
            return $val === (string)($inputDef['eval']['expected'] ?? '');
        }
        if ($mode === 'regex') {
            $pattern = '#'.str_replace('#','\#', (string)($inputDef['eval']['expected'] ?? '')).'#u';
            return @preg_match($pattern, '') !== false ? (preg_match($pattern, $val) === 1) : null;
        }
        if ($mode === 'contains') {
            return mb_stripos($val, (string)($inputDef['eval']['expected'] ?? '')) !== false;
        }
        if ($mode === 'numeric_range') {
            $bounds = (string)($inputDef['eval']['expected'] ?? ''); // e.g., "10-20"
            if (!preg_match('/^(\-?\d+(?:\.\d+)?)\s*\-\s*(\-?\d+(?:\.\d+)?)$/', $bounds, $m)) return null;
            $n = is_numeric($val) ? (float)$val : null; if ($n === null) return false;
            return $n >= (float)$m[1] && $n <= (float)$m[2];
        }
        return null;
    }
}

