<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventListener;

use Lexio\AdminBundle\Attributes\Page;
use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::CONTROLLER, priority: 0)]
final readonly class PageAttributeListener
{
    public function __construct(
        private Environment          $twig,
        private PageManagerInterface $pageManager,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (\is_array($controller)) {
            [$controllerInstance, $methodName] = $controller;
        } elseif (\is_object($controller) && \method_exists($controller, '__invoke')) {
            $controllerInstance = $controller;
            $methodName = '__invoke';
        } else {
            return;
        }

        $reflectionMethod = new ReflectionMethod($controllerInstance, $methodName);
        $attributes = $reflectionMethod->getAttributes(Page::class);

        if (empty($attributes)) {
            return;
        }

        $pageAttribute = $attributes[0];
        $className = $pageAttribute->getArguments()[0] ?? null;

        if (!$className) {
            throw new NotFoundHttpException('Page attribute must have a class name.');
        }

        if (!\class_exists($className)) {
            throw new NotFoundHttpException(\sprintf('Page class "%s" does not exist.', $className));
        }

        $page = $this->pageManager->getPageObject($className) ?? new $className();
        $this->twig->addGlobal('page', $page);
    }
}
