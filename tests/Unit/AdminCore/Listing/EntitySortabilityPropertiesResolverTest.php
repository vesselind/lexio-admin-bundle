<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Listing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Lexio\AdminBundle\AdminCore\Listing\EntitySortabilityPropertiesResolver;
use PHPUnit\Framework\TestCase;

final class EntitySortabilityPropertiesResolverTest extends TestCase
{
    public function test_resolves_a_list_of_mapped_property_names_without_associations(): void
    {
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->mapField(['fieldName' => 'id', 'columnName' => 'entity_id', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'displayName', 'columnName' => 'display_name', 'type' => 'string']);
        $metadata->mapManyToOne(['fieldName' => 'category', 'targetEntity' => \stdClass::class]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('getClassMetadata')
            ->with(\stdClass::class)->willReturn($metadata);
        $resolver = new EntitySortabilityPropertiesResolver($entityManager);

        self::assertSame(['id', 'displayName'], $resolver->resolve(\stdClass::class));
    }

    public function test_returns_an_empty_list_when_no_properties_are_mapped(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn(new ClassMetadata(\stdClass::class));
        $resolver = new EntitySortabilityPropertiesResolver($entityManager);

        self::assertSame([], $resolver->resolve(\stdClass::class));
    }
}
