<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures;

use Lexio\AdminBundle\Attributes\FieldType;
use Lexio\AdminBundle\Page\BasePage;
use Lexio\AdminBundle\Page\ContentItemTypes;

final class IconPage extends BasePage
{
    #[FieldType(ContentItemTypes::ICON)]
    protected ?string $icon = 'mdi:home';

    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
