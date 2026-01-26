<?php
// src/Utils/Validation.php

namespace App\Utils;

class Validation
{
    public static function string(?string $v, bool $required = true, int $maxLen = 255): ?string
    {
        if ($v === null) return $required ? null : null;
        $v = trim($v);
        if ($required && $v === '') return null;
        if (mb_strlen($v) > $maxLen) return null;
        return $v;
    }

    public static function intVal($v, bool $required = true, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int
    {
        if ($v === null || $v === '') return $required ? null : null;
        if (!is_numeric($v)) return null;
        $i = (int)$v;
        if ($i < $min || $i > $max) return null;
        return $i;
    }
}
