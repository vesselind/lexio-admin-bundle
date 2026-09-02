<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Model;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * Parameters returned by SearchableEntityInterface::getSearchableParameters().
 */
final class SearchableParam
{
    /**
     * @param string|null $title
     * @param list<string> $searchableFields
     * @param string|null $routeName
     * @param array<string, mixed> $routeParams
     * @param string|null $icon
     * @param string|null $subTitle
     */
    public function __construct(
        public ?string $title           = null,
        public array   $searchableFields = [],
        public ?string $routeName       = null,
        public array   $routeParams     = [],
        public ?string $icon            = null,
        public ?string $subTitle        = null,
    ) {
    }


    public function generateUrl(UrlGeneratorInterface $urlGenerator, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        Assert::notNull($this->routeName, 'Route name must be set to generate a URL.');

        return $urlGenerator->generate($this->routeName, $this->routeParams, $referenceType);
    }
}

