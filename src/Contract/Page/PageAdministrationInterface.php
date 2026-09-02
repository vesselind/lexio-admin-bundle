<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Page;

use Lexio\AdminBundle\Page\BasePage;

/**
 * Contract for the page administration controller's read/write operations.
 *
 * This is separate from PageManagerInterface so applications that only expose
 * page objects to the public site are not forced to implement persistence.
 */
interface PageAdministrationInterface
{
    public function getPageObject(string $pageClass, ?string $locale = null): ?BasePage;

    public function createOrUpdatePage(BasePage $page, ?string $locale = null): void;
}
