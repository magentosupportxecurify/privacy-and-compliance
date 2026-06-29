<?php

declare(strict_types=1);

namespace MiniOrange\PDProtect\Helper;

class Encryption
{
    public static function encrypt(string $value, string $key): string
    {
        if ($key === '' || $value === '') {
            return $value;
        }
        $result = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $char    = substr($value, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $result .= chr(ord($char) + ord($keychar));
        }
        return base64_encode($result);
    }

    public static function decrypt(string $value, string $key): string
    {
        if ($key === '' || $value === '') {
            return $value;
        }
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return $value;
        }
        $result = '';
        for ($i = 0; $i < strlen($decoded); $i++) {
            $char    = substr($decoded, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $result .= chr(ord($char) - ord($keychar));
        }
        return $result;
    }
}
