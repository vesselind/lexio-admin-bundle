<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\AdminCore\Listing\EntitySortabilityPropertiesResolver;
use Lexio\AdminBundle\Api\Output\FileOutput;
use Lexio\AdminBundle\Api\Query\FileListQuery;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableFile;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableFileController;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableImageController;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Validator\Validation;

final class ApiControllerPortabilityTest extends TestCase
{
    public function test_host_prefixes_wrap_inherited_actions_and_route_names(): void
    {
        $loader = new AttributeRouteControllerLoader();
        $files = $loader->load(PortableFileController::class);
        $images = $loader->load(PortableImageController::class);
        self::assertCount(4, $files);
        self::assertSame('/bridge/v2/files/{id}/show', $files->get('api.v1.file.show')->getPath());
        self::assertSame(['DELETE'], $files->get('api.v1.file.delete')->getMethods());
        self::assertSame('/bridge/v2/images/upload', $images->get('api.v1.image.upload')->getPath());
        self::assertSame(['POST'], $images->get('api.v1.image.upload')->getMethods());
    }

    public function test_show_loads_the_host_entity_and_maps_a_dto(): void
    {
        $file = new PortableFile();
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with(42)->willReturn($file);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getRepository')->with(PortableFile::class)->willReturn($repository);
        $controller = new PortableFileController(new EntitySortabilityPropertiesResolver($manager), 'en', ['en']);
        $mapper = $this->createMock(ObjectMapperInterface::class);
        $mapper->expects(self::once())->method('map')->with($file, self::isInstanceOf(FileOutput::class))
            ->willReturnCallback(static function (object $source, object $target): object { $target->id = $source->getId(); return $target; });
        $controller->setContainer($this->services($manager, $mapper));
        $data = json_decode($controller->show(42)->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(42, $data['id']);
    }

    public function test_image_endpoint_rejects_a_plain_host_file(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn(new PortableFile());
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $controller = new PortableImageController(new EntitySortabilityPropertiesResolver($manager), 'en', ['en']);
        $controller->setContainer($this->services($manager, $this->createStub(ObjectMapperInterface::class)));
        $this->expectException(NotFoundHttpException::class);
        $controller->show(42);
    }

    public function test_pagination_accepts_iterable_items_and_returns_a_json_list(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $builder = new \Doctrine\ORM\QueryBuilder($manager);
        $builder->select('f')->from(PortableFile::class, 'f');
        $repository->method('createQueryBuilder')->willReturn($builder);
        $pagination = $this->createStub(PaginationInterface::class);
        $pagination->method('getItems')->willReturn(new \ArrayIterator([7 => new PortableFile(42), 9 => new PortableFile(43)]));
        $pagination->method('getTotalItemCount')->willReturn(2);
        $paginator = $this->createStub(PaginatorInterface::class);
        $paginator->method('paginate')->willReturn($pagination);
        $mapper = $this->createStub(ObjectMapperInterface::class);
        $mapper->method('map')->willReturnCallback(static function (object $source, object $target): object { $target->id = $source->getId(); return $target; });
        $services = $this->services($manager, $mapper);
        $services->set(\Lexio\AdminBundle\Service\EntityFilterer::class, new \Lexio\AdminBundle\Service\EntityFilterer($manager));
        $services->set(PaginatorInterface::class, $paginator);
        $controller = new PortableFileController(new EntitySortabilityPropertiesResolver($manager), 'en', ['en']);
        $controller->setContainer($services);
        $data = json_decode($controller->list(new FileListQuery())->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([42, 43], array_column($data['data'], 'id'));
        self::assertTrue(array_is_list($data['data']));
        self::assertSame(2, $data['total']);
        self::assertSame(20, $data['limit']);
    }

    private function services(EntityManagerInterface $manager, ObjectMapperInterface $mapper): Container
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($manager);
        $container = new Container();
        $container->set('doctrine', $registry);
        $container->set(ObjectMapperInterface::class, $mapper);
        $container->set(\Symfony\Component\Validator\Validator\ValidatorInterface::class, Validation::createValidator());
        $container->set(LocaleSwitcher::class, new LocaleSwitcher('en', []));
        $container->set('serializer', new Serializer([new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()))], [new JsonEncoder()]));

        return $container;
    }
}
