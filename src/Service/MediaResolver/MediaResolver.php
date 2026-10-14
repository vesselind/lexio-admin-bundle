<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbedStrategyInterface;

/**
 * Resolves media URLs to embeddable media using the registered strategies.
 *
 * The first strategy that supports a URL resolves it. Host applications can add
 * strategies by implementing MediaEmbedStrategyInterface.
 */
final readonly class MediaResolver
{
    /** @var array<string, MediaEmbedStrategyInterface> */
    private array $strategies;

    /** @param iterable<MediaEmbedStrategyInterface> $strategies */
    public function __construct(iterable $strategies)
    {
        $registered = [];

        foreach ($strategies as $strategy) {
            $name = $strategy->getName();

            if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
                throw new \LogicException(sprintf('Invalid media embed strategy name "%s".', $name));
            }

            if (isset($registered[$name])) {
                throw new \LogicException(sprintf('A media embed strategy named "%s" is already registered.', $name));
            }

            $registered[$name] = $strategy;
        }

        $this->strategies = $registered;
    }

    public function resolve(string $url): ?MediaEmbed
    {
        foreach ($this->strategies as $strategy) {
            if (!$strategy->supports($url)) {
                continue;
            }

            $embed = $strategy->createEmbed($url);

            if (null !== $embed) {
                return $embed;
            }
        }

        return null;
    }
}
