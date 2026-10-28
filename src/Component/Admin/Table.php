<?php

namespace Lexio\AdminBundle\Component\Admin;

use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:Table', template: '@LexioAdmin/components/Admin/Table.html.twig')]
class Table
{
    public ?ListingContext $listingContext = null;

    /**
     * @var SlidingPagination<array-key, mixed>|null
     * Paginator results for the current listing.
     * This is a KnpPaginatorBundle object that contains the paginated results and pagination information.
     */
    public ?SlidingPagination $items = null;
}