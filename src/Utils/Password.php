<?php
// src/Utils/Password.php

namespace App\Utils;

class Password
{
    public static function generate(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*_-';
        $bytes = random_bytes($length);
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
        return $out;
    }
}
