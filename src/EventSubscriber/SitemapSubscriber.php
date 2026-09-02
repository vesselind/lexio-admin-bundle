<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base sitemap subscriber.
 *
 * Extend this class in your application and register for
 * {@see \Presta\SitemapBundle\Event\SitemapPopulateEvent}.
 *
 * Requires `presta/sitemap-bundle` to be installed.
 */
abstract class SitemapSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<int, string> $locales
     */
    public function __construct(
        protected readonly EntityManagerInterface $manager,
        protected readonly array $locales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Extend in your subclass to register for SitemapPopulateEvent::class
        ];
    }
}
