<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore;

use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class AdminUrlGenerator
{
    public ?string $entityFqcn      = null;
    public ?string $entitySnakeName = null;
    public ?object $entityInstance  = null;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string          $defaultLocale,
    ) {
    }

    /**
     * @param class-string $entityFqcn
     * @return $this
     */
    public function setEntityFqcn(string $entityFqcn): static
    {
        if (!class_exists($entityFqcn)) {
            throw new \InvalidArgumentException(
                sprintf('[AdminUrlGenerator] Entity class "%s" does not exist.', $entityFqcn)
            );
        }

        $this->entityFqcn      = $entityFqcn;
        $this->entitySnakeName = AdminUtils::classNameToSnake($entityFqcn);

        return $this;
    }

    public function setEntityInstance(?object $entityInstance): static
    {
        $this->entityInstance = $entityInstance;

        return $this;
    }

    public function getHomeLink(): string
    {
        return $this->router->generate('admin.dashboard');
    }

    public function indexLink(): string
    {
        Assert::notNull(
            $this->entitySnakeName,
            '[AdminUrlGenerator] Entity FQCN must be set before calling indexLink().'
        );

        return $this->router->generate(sprintf('admin.%s.index', $this->entitySnakeName));
    }

    public function createLink(): string
    {
        return $this->router->generate(sprintf('admin.%s.create', $this->entitySnakeName));
    }

    public function updateRoute(): string
    {
        return sprintf('admin.%s.update', $this->entitySnakeName);
    }

    public function updateRouteParams(?string $locale = null): array
    {
        $this->assertEntityInstanceNotNull('updateRouteParams');

        return ['id' => $this->entityInstance->getId(), 'language' => $locale ?? $this->defaultLocale];
    }

    public function updateLink(?string $locale = null): string
    {
        return $this->router->generate($this->updateRoute(), $this->updateRouteParams($locale));
    }

    public function deleteRouteParams(): array
    {
        $this->assertEntityInstanceNotNull('deleteRouteParams');

        return ['id' => $this->entityInstance->getId()];
    }

    public function deleteRoute(): string
    {
        return sprintf('admin.%s.delete', $this->entitySnakeName);
    }

    public function deleteLink(): string
    {
        return $this->router->generate($this->deleteRoute(), $this->deleteRouteParams());
    }

    private function assertEntityInstanceNotNull(string $caller): void
    {
        if ($this->entityInstance === null) {
            throw new \RuntimeException(
                sprintf('[AdminUrlGenerator] Entity instance must be set before calling %s().', $caller)
            );
        }
    }
}

