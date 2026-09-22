<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Breadcrumbs;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Lexio\AdminBundle\AdminCore\Breadcrumbs\FrontBreadcrumbs;
use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Page\BasePage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FrontBreadcrumbsTest extends TestCase
{
    public function test_dynamic_page_title_is_added_as_an_untranslated_unlinked_item(): void
    {
        $page = (new BasePage())->setTitle('Appointment preparation');
        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, null)
            ->willReturn($page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::never())->method('trans');

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, $requestStack, $breadcrumbs, translator: $translator);

        self::assertSame($service, $service->forDynamicPage(BasePage::class));

        $items = $breadcrumbs->getNamespaceBreadcrumbs();
        self::assertCount(1, $items);
        self::assertSame('Appointment preparation', $items[0]->text);
        self::assertSame('', $items[0]->url);
        self::assertFalse($items[0]->translate);
    }

    public function test_explicit_locale_is_forwarded_to_the_page_manager(): void
    {
        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, 'en')
            ->willReturn((new BasePage())->setTitle('Preparation'));

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::never())->method('getCurrentRequest');

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, $requestStack, $breadcrumbs);

        $service->forDynamicPage(BasePage::class, 'en');

        self::assertCount(1, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    public function test_null_locale_uses_the_current_request_locale(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, 'en')
            ->willReturn((new BasePage())->setTitle('Preparation'));

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, $requestStack, $breadcrumbs);

        $service->forDynamicPage(BasePage::class);

        self::assertCount(1, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    public function test_null_locale_is_preserved_when_there_is_no_current_request(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, null)
            ->willReturn((new BasePage())->setTitle('Preparation'));

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, $requestStack, $breadcrumbs);

        $service->forDynamicPage(BasePage::class);

        self::assertCount(1, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    public function test_missing_page_is_skipped_and_the_service_remains_chainable(): void
    {
        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, 'bg')
            ->willReturn(null);

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, breadcrumbs: $breadcrumbs);

        self::assertSame($service, $service->forDynamicPage(BasePage::class, 'bg'));
        self::assertCount(0, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    #[DataProvider('emptyTitleProvider')]
    public function test_null_or_blank_title_is_skipped(?string $title): void
    {
        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, 'bg')
            ->willReturn((new BasePage())->setTitle($title));

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, breadcrumbs: $breadcrumbs);

        self::assertSame($service, $service->forDynamicPage(BasePage::class, 'bg'));
        self::assertCount(0, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    public static function emptyTitleProvider(): iterable
    {
        yield 'null title' => [null];
        yield 'empty title' => [''];
        yield 'whitespace title' => ['   '];
    }

    public function test_non_base_page_objects_are_skipped(): void
    {
        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, 'bg')
            ->willReturn(new \stdClass());

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService($pageManager, breadcrumbs: $breadcrumbs);

        self::assertSame($service, $service->forDynamicPage(BasePage::class, 'bg'));
        self::assertCount(0, $breadcrumbs->getNamespaceBreadcrumbs());
    }

    public function test_dynamic_page_can_be_chained_after_for_home(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('front_home')
            ->willReturn('/');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('home', [], 'breadcrumbs')
            ->willReturn('Home');

        $pageManager = $this->createMock(PageManagerInterface::class);
        $pageManager
            ->expects(self::once())
            ->method('getPageObject')
            ->with(BasePage::class, null)
            ->willReturn((new BasePage())->setTitle('Preparation'));

        $breadcrumbs = new Breadcrumbs();
        $service = $this->createService(
            $pageManager,
            breadcrumbs: $breadcrumbs,
            router: $router,
            translator: $translator,
        );

        self::assertSame($service, $service->forHome()->forDynamicPage(BasePage::class));

        $items = $breadcrumbs->getNamespaceBreadcrumbs();
        self::assertCount(2, $items);
        self::assertSame('Home', $items[0]->text);
        self::assertSame('/', $items[0]->url);
        self::assertSame('Preparation', $items[1]->text);
        self::assertSame('', $items[1]->url);
        self::assertFalse($items[1]->translate);
    }

    public function test_front_breadcrumbs_resolves_through_the_base_controller_service_subscriber(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('lexio_admin.front_home_page_route', 'front_home');
        $router = $this->createStub(RouterInterface::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $pageManager = $this->createStub(PageManagerInterface::class);
        $this->registerSyntheticService(
            $container,
            'test.router',
            RouterInterface::class,
        );
        $this->registerSyntheticService(
            $container,
            'test.translator',
            TranslatorInterface::class,
        );
        $this->registerSyntheticService(
            $container,
            'test.page_manager',
            PageManagerInterface::class,
        );
        $container->register(RequestStack::class)->setPublic(true);
        $container->register(Breadcrumbs::class)->setPublic(true);
        $container
            ->registerForAutoconfiguration(ServiceSubscriberInterface::class)
            ->addTag('container.service_subscriber');
        $container
            ->register(FrontBreadcrumbs::class)
            ->setAutowired(true)
            ->setPublic(true);
        $container
            ->register(FrontBreadcrumbsHostController::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setPublic(true);

        $container->compile();
        $container->set('test.router', $router);
        $container->set('test.translator', $translator);
        $container->set('test.page_manager', $pageManager);

        $controller = $container->get(FrontBreadcrumbsHostController::class);

        self::assertSame($container->get(FrontBreadcrumbs::class), $controller->breadcrumbs());
    }

    private function registerSyntheticService(
        ContainerBuilder $container,
        string $serviceId,
        string $interface,
    ): void {
        $container->register($serviceId)->setSynthetic(true)->setPublic(true);
        $container->setAlias($interface, $serviceId);
    }

    private function createService(
        ?PageManagerInterface $pageManager = null,
        ?RequestStack $requestStack = null,
        ?Breadcrumbs $breadcrumbs = null,
        ?RouterInterface $router = null,
        ?TranslatorInterface $translator = null,
    ): FrontBreadcrumbs {
        return new FrontBreadcrumbs(
            $router ?? $this->createStub(RouterInterface::class),
            $breadcrumbs ?? new Breadcrumbs(),
            $translator ?? $this->createStub(TranslatorInterface::class),
            'front_home',
            $pageManager ?? $this->createStub(PageManagerInterface::class),
            $requestStack ?? $this->createStub(RequestStack::class),
        );
    }
}

final class FrontBreadcrumbsHostController extends BaseController
{
}
