<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures;

use Lexio\AdminBundle\Attributes\Page;
use Symfony\Component\HttpFoundation\Response;

final class PageAttributeController
{
    #[Page(LocalizedTestPage::class)]
    public function show(): Response
    {
        return new Response();
    }
}
