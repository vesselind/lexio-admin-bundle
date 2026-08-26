<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Page;

/**
 * Contract for services that create/retrieve page objects from the database.
 *
 * Implement this interface in your application to integrate with the
 * PageAttributeListener, which reads #[Page] attributes on controller
 * methods and injects the corresponding page object as a Twig global.
 */
interface PageManagerInterface
{
    /**
     * Returns a populated page object for the given class name.
     *
     * @param string $pageClass FQCN of the page class
     * @param string|null $locale Locale for translatable content
     */
    public function getPageObject(string $pageClass, ?string $locale = null): ?object;
}
