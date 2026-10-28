<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\MapTransformer;

use Lexio\AdminBundle\Model\SearchableParam;
use Symfony\Component\ObjectMapper\TransformCallableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @implements TransformCallableInterface<object, object>
 */
final readonly class ApiRoutesTransformer implements TransformCallableInterface
{
    public function __construct(private readonly UrlGeneratorInterface $router)
    {
    }

    /**
     * @param mixed $value
     * @param object $source
     * @param object|null $target
     */
    public function __invoke(mixed $value, object $source, ?object $target): mixed
    {
        if ($value instanceof SearchableParam) {
            return $value->generateUrl($this->router, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        throw new \RuntimeException(sprintf('ApiRoutesTransformer can only transform SearchableParam objects, got %s', get_debug_type($value)));
    }

}
