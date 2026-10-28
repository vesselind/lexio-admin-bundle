<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Lexio\AdminBundle\Contract\Api\ApiKeyUserInterface;
use function Symfony\Component\String\u;

/**
 * Generates and hashes the raw API keys used for stateless Bearer authentication.
 *
 * The raw key is displayed once to the operator (or handed to the caller) and
 * only its deterministic SHA-256 hash is persisted on the User.
 */
final readonly class ApiKeyGenerator
{
    private const int KEY_BYTES = 16;
    public const int RAW_KEY_LENGTH = self::KEY_BYTES * 2;

    public function __construct(
        private string $siteBaseUrl,
    )
    {
    }

    private function keyPrefix(): string
    {
        $host = parse_url($this->siteBaseUrl, PHP_URL_HOST);
        if (!is_string($host)) {
            return '';
        }

        $host = u($host)
            ->replaceMatches("/wwww/i", '')
            ->replaceMatches("/\.[a-z]+$/i", '')
            ->replaceMatches('/[^a-z0-9]+/i', '_')
            ->trim('_')
            ->lower()
            ->toString();

        return $host ? $host . '_' : '';
    }

    public function generate(): string
    {
        return $this->keyPrefix() . bin2hex(random_bytes(self::KEY_BYTES));
    }

    public function hash(#[\SensitiveParameter] string $rawKey): string
    {
        return hash('sha256', $rawKey);
    }

    /**
     * Assigns a newly generated key to the user and returns the raw secret.
     * Rotation invalidates any previously issued key immediately.
     */
    public function assign(ApiKeyUserInterface $user): string
    {
        $rawKey = $this->generate();
        $user->setApiKeyHash($this->hash($rawKey));

        return $rawKey;
    }
}
