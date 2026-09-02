<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Twig;

use Lexio\AdminBundle\Twig\Runtime\AdminExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class AdminExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly string $frontHomePageRoute,
        /** @var array<string, mixed> */
        private readonly array $ui,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_field', [AdminExtensionRuntime::class, 'renderField'], ['is_safe' => ['html']]),
            new TwigFunction('render_admin_menu', [AdminExtensionRuntime::class, 'renderAdminMenu'], ['is_safe' => ['html']]),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'frontHomePageRoute' => $this->frontHomePageRoute,
            'lexio_admin_ui' => $this->ui,
        ];
    }
}

