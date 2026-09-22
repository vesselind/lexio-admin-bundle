<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures;

use Lexio\AdminBundle\Contract\Page\PageManagerInterface;

final readonly class LocaleAwarePageManager implements PageManagerInterface
{
    public function getPageObject(string $pageClass, ?string $locale = null): ?object
    {
        if ($pageClass !== LocalizedTestPage::class) {
            return null;
        }

        $titles = [
            'bg' => 'Bulgarian page',
            'en' => 'English page',
        ];

        return (new LocalizedTestPage())->setTitle($titles[$locale ?? 'bg'] ?? $titles['bg']);
    }
}
