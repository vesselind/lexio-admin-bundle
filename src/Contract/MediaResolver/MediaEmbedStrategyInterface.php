<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\MediaResolver;

/**
 * Extension point for resolving media URLs found in CKEditor content.
 *
 * Implementations are autoconfigured with the `lexio_admin.media_embed_strategy` tag.
 * The first registered strategy that supports a URL resolves it; host applications
 * can register additional strategies for custom providers.
 */
interface MediaEmbedStrategyInterface
{
    /**
     * Unique, URL-safe provider name (e.g. "youtube"). Used for CSS modifier classes.
     */
    public function getName(): string;

    /**
     * Whether this strategy handles the given media URL.
     */
    public function supports(string $url): bool;

    /**
     * Builds the embed for a supported URL, or null when the URL cannot be embedded.
     */
    public function createEmbed(string $url): ?MediaEmbed;
}
