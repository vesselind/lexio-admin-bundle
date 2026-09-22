<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Functional\EventListener;

use Lexio\AdminBundle\EventListener\PageAttributeListener;
use Lexio\AdminBundle\Tests\Fixtures\LocalizedTestPage;
use Lexio\AdminBundle\Tests\Fixtures\PageAttributeController;
use Lexio\AdminBundle\Tests\Fixtures\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

final class PageAttributeListenerTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $exceptionHandler = get_exception_handler();
        if (is_array($exceptionHandler) && ($exceptionHandler[0] ?? null) instanceof ErrorHandler) {
            restore_exception_handler();
        }
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    #[DataProvider('requestLocaleProvider')]
    public function test_it_registers_page_content_for_the_request_locale(string $locale, string $expectedTitle): void
    {
        $kernel = self::bootKernel();
        $controller = new PageAttributeController();
        $request = Request::create('/' . $locale);
        $request->setLocale($locale);
        $event = new ControllerEvent(
            $kernel,
            [$controller, 'show'],
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener = self::getContainer()->get(PageAttributeListener::class);
        self::assertInstanceOf(PageAttributeListener::class, $listener);
        $listener($event);

        $page = self::getContainer()->get(Environment::class)->getGlobals()['page'] ?? null;

        self::assertInstanceOf(LocalizedTestPage::class, $page);
        self::assertSame($expectedTitle, $page->getTitle());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function requestLocaleProvider(): iterable
    {
        yield 'default locale' => ['bg', 'Bulgarian page'];
        yield 'localized route' => ['en', 'English page'];
    }
}
