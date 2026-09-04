<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:BulkActions', template: '@LexioAdmin/components/Admin/BulkActions.html.twig')]
final class BulkActions
{
    /** @var ?ArrayCollection<int,BulkAction> */
    public ?ArrayCollection $bulkActions = null;
}

