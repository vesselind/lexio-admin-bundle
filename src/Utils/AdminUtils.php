<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Utils;

final class AdminUtils
{
    /**
     * Converts a fully-qualified class name to snake_case using only the short class name.
     *
     * Examples:
     *   App\Entity\BlogPost  → blog_post
     *   Acme\Entity\File → file
     */
    public static function classNameToSnake(string $fqcn): string
    {
        $shortName = self::getClassName($fqcn);

        return self::toSnakeCase($shortName);
    }

    /**
     * Returns the short (unqualified) class name from a FQCN or a class name string.
     *
     * Examples:
     *   App\Entity\BlogPost → BlogPost
     *   BlogPost            → BlogPost
     */
    public static function getClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Converts a camelCase or PascalCase string to snake_case.
     *
     * Examples:
     *   BlogPost  → blog_post
     *   blogPost  → blog_post
     *   myField   → my_field
     */
    public static function toSnakeCase(string $string): string
    {
        $result = preg_replace('/[A-Z]/', '_$0', lcfirst($string));

        return strtolower($result ?? $string);
    }

    /**
     * Generates a cryptographically-safe random alphanumeric string.
     *
     * @param int $length Number of characters to generate (default 11).
     */
    public static function randomString(int $length = 11): string
    {
        $bytesNeeded = max(1, (int) ceil($length * 0.75));

        return substr(
            str_replace(['+', '/', '='], '', base64_encode(random_bytes($bytesNeeded))),
            0,
            $length
        );
    }
}

