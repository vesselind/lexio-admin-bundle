<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\MediaResolver;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbedStrategyInterface;
use Lexio\AdminBundle\Service\MediaResolver\MediaResolver;
use PHPUnit\Framework\TestCase;

final class MediaResolverTest extends TestCase
{
    public function test_it_resolves_with_the_first_supporting_strategy(): void
    {
        $first = $this->strategy('first', true, new MediaEmbed('https://example.com/first', 'first'));
        $second = $this->strategy('second', true, new MediaEmbed('https://example.com/second', 'second'));

        $resolver = new MediaResolver([$first, $second]);

        $embed = $resolver->resolve('https://example.com/media');

        self::assertNotNull($embed);
        self::assertSame('https://example.com/first', $embed->src);
    }

    public function test_it_continues_when_a_supporting_strategy_cannot_embed_the_url(): void
    {
        $failing = $this->strategy('failing', true, null);
        $fallback = $this->strategy('fallback', true, new MediaEmbed('https://example.com/fallback', 'fallback'));

        $resolver = new MediaResolver([$failing, $fallback]);

        $embed = $resolver->resolve('https://example.com/media');

        self::assertNotNull($embed);
        self::assertSame('https://example.com/fallback', $embed->src);
    }

    public function test_it_returns_null_when_no_strategy_supports_the_url(): void
    {
        $resolver = new MediaResolver([
            $this->strategy('youtube', false, new MediaEmbed('https://example.com/media', 'youtube')),
        ]);

        self::assertNull($resolver->resolve('https://example.com/media'));
    }

    public function test_it_rejects_duplicate_strategy_names(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A media embed strategy named "youtube" is already registered.');

        new MediaResolver([
            $this->strategy('youtube', true, null),
            $this->strategy('youtube', true, null),
        ]);
    }

    public function test_it_rejects_names_that_cannot_be_used_in_css_classes(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid media embed strategy name "invalid name".');

        new MediaResolver([$this->strategy('invalid name', true, null)]);
    }

    private function strategy(string $name, bool $supports, ?MediaEmbed $embed): MediaEmbedStrategyInterface
    {
        return new class($name, $supports, $embed) implements MediaEmbedStrategyInterface {
            public function __construct(
                private readonly string $name,
                private readonly bool $supports,
                private readonly ?MediaEmbed $embed,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function supports(string $url): bool
            {
                return $this->supports;
            }

            public function createEmbed(string $url): ?MediaEmbed
            {
                return $this->embed;
            }
        };
    }
}
