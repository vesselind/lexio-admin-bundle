<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Symfony\Component\Clock\ClockInterface;

final readonly class TranslationPackageRequestAuthenticator
{
    public const TIMESTAMP_HEADER = 'X-Lexio-Timestamp';
    public const SIGNATURE_HEADER = 'X-Lexio-Signature';

    public function __construct(
        private TranslationSynchronizationOptions $options,
        private ClockInterface $clock,
    ) {
    }

    /** @return array<string, string> */
    public function createHeaders(string $method, string $path, string $body): array
    {
        if (!$this->options->isEnabled()) {
            throw new TranslationSynchronizationException('Translation synchronization is disabled.');
        }

        $timestamp = (string) $this->clock->now()->getTimestamp();

        return [
            self::TIMESTAMP_HEADER => $timestamp,
            self::SIGNATURE_HEADER => $this->signature($timestamp, $method, $path, $body),
        ];
    }

    public function isValid(
        string $method,
        string $path,
        string $body,
        ?string $timestamp,
        ?string $signature,
    ): bool {
        if (
            !$this->options->isEnabled()
            || null === $timestamp
            || null === $signature
            || 1 !== preg_match('/^[0-9]+$/D', $timestamp)
            || 1 !== preg_match('/^[a-fA-F0-9]{64}$/D', $signature)
        ) {
            return false;
        }

        $requestTimestamp = filter_var($timestamp, FILTER_VALIDATE_INT);
        if (!is_int($requestTimestamp) || abs($this->clock->now()->getTimestamp() - $requestTimestamp) > $this->options->getSignatureTtl()) {
            return false;
        }

        $expected = $this->signature($timestamp, $method, $path, $body);

        return hash_equals($expected, strtolower($signature));
    }

    private function signature(string $timestamp, string $method, string $path, string $body): string
    {
        $derivedKey = hash_hmac('sha256', $this->options->getAuthSalt(), $this->options->getAppSecret(), true);
        $canonical = implode("\n", [
            $timestamp,
            strtoupper($method),
            $path,
            hash('sha256', $body),
        ]);

        return hash_hmac('sha256', $canonical, $derivedKey);
    }
}
