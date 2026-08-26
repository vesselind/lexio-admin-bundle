<?php

namespace Lexio\AdminBundle\Service;

use Symfony\Component\String\UnicodeString;

class Utils
{
    public static function toSnakeCase(string $string): string
    {
        return (new UnicodeString($string))->snake()->toString();

    }

    public static function classNameToSnake(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);

        return self::toSnakeCase($className);
    }

    public static function getClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }


    public static function randomString(int $length = 32, bool $includeSpecialChars = false): string
    {
        $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        if ($includeSpecialChars) {
            $permittedChars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }

        return substr(str_shuffle($permittedChars), 0, min($length, 62));
    }
}
