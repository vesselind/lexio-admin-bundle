<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller;

use Lexio\AdminBundle\Sitemap\SitemapProviderRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class SitemapController
{
    public function __construct(
        private SitemapProviderRegistry $providers,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
        private bool $enabled,
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap.index', defaults: ['_format' => 'xml'], methods: ['GET'])]
    public function index(): Response
    {
        $this->assertEnabled();
        $sitemaps = [];

        foreach (array_keys($this->providers->all()) as $name) {
            $sitemaps[] = $this->urlGenerator->generate(
                'sitemap.collection',
                ['name' => $name],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        }

        return $this->xmlResponse('@LexioAdmin/sitemap/index.xml.twig', [
            'sitemaps' => $sitemaps,
        ]);
    }

    #[Route(
        '/sitemap_{name}.xml',
        name: 'sitemap.collection',
        requirements: ['name' => '[a-z0-9_-]+'],
        defaults: ['_format' => 'xml'],
        methods: ['GET'],
    )]
    public function collection(string $name): Response
    {
        $this->assertEnabled();
        $provider = $this->providers->get($name);

        if (null === $provider) {
            throw new NotFoundHttpException();
        }

        return $this->xmlResponse('@LexioAdmin/sitemap/collection.xml.twig', [
            'urls' => $provider->getUrls(),
        ]);
    }

    /** @param array<string, mixed> $context */
    private function xmlResponse(string $template, array $context): Response
    {
        return new Response(
            $this->twig->render($template, $context),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    private function assertEnabled(): void
    {
        if (!$this->enabled) {
            throw new NotFoundHttpException();
        }
    }
}
