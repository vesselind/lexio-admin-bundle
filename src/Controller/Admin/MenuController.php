<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Breadcrumbs\AdminBreadcrumbs;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Entity\FooterMenu;
use Lexio\AdminBundle\Entity\HeaderMenu;
use Lexio\AdminBundle\Form\FooterMenuType;
use Lexio\AdminBundle\Form\HeaderMenuType;
use Lexio\AdminBundle\Repository\FooterMenuRepository;
use Lexio\AdminBundle\Repository\HeaderMenuRepository;
use Lexio\AdminBundle\Repository\SortableMenuRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides reusable administration for the bundle's header and footer menus.
 *
 * Host applications extend this controller to add the route prefix used by
 * their own application route loader.
 */
abstract class MenuController extends BaseController
{
    /** @param list<string> $locales */
    public function __construct(
        private readonly SortableMenuRepository $menuRepository,
        private readonly HeaderMenuRepository $headerMenuRepository,
        private readonly FooterMenuRepository $footerMenuRepository,
        private readonly FormFactoryInterface $formFactory,
        private readonly TranslatorInterface $translator,
        private readonly AdminBreadcrumbs $breadcrumbs,
        private readonly array $locales,
    ) {
    }

    #[Route('/header', name: 'admin.menu.header')]
    public function headerMenu(): Response
    {
        $headerMenu = $this->headerMenuRepository->getParents() ?? [];

        $this->prepareBreadcrumbs('admin.menu.header', 'admin.menu.header');

        return $this->render('@LexioAdmin/admin/menu/header.html.twig', [
            'title' => $this->translate('admin.menu.header'),
            'headerMenu' => $headerMenu,
        ]);
    }

    #[Route('/header/create', name: 'admin.menu.header_create')]
    public function headerMenuCreate(Request $request): Response
    {
        $headerMenu = new HeaderMenu();
        $form = $this->createForm(HeaderMenuType::class, $headerMenu, [
            'translation_domain' => $this->translationDomain(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->menuRepository->save($headerMenu, true);

            return $this->redirectToRoute('admin.menu.header');
        }

        $this->prepareBreadcrumbs('admin.menu.header', 'admin.menu.header_create');

        return $this->render('@LexioAdmin/admin/menu/header_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/header/update_order', name: 'admin.menu.header_update_order', methods: ['POST'])]
    public function orderHeader(Request $request): Response
    {
        $item = null;

        if ($currentItemId = $request->query->get('currentItemId')) {
            /** @var HeaderMenu|null $item */
            $item = $this->headerMenuRepository->find($currentItemId);
        }

        if ($item) {
            $item->setPositionIndex((int) $request->query->get('newIndex'));
            $this->menuRepository->flush();
        } else {
            return $this->json(['message' => $this->translate('sorting_error')], 500);
        }

        return $this->json(['message' => $this->translate('sorting_success')]);
    }

    #[Route('/header/{id}/update', name: 'admin.menu.header_update')]
    public function headerMenuUpdate(HeaderMenu $headerMenu, Request $request): Response
    {
        $forms = [];

        foreach ($this->locales as $locale) {
            $headerMenu->setTranslatableLocale($locale);
            $this->menuRepository->refresh($headerMenu);

            $form = $this->formFactory->createNamed($locale, HeaderMenuType::class, $headerMenu, [
                'translation_domain' => $this->translationDomain(),
            ]);
            $forms[$locale] = $form->createView();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $headerMenu->setTranslatableLocale($locale);
                $this->menuRepository->flush();

                $this->addFlash('success', $this->translate('message.item_updated'));

                return $this->redirectToRoute('admin.menu.header');
            }
        }

        $this->prepareBreadcrumbs('admin.menu.header', 'admin.menu.header_update', [
            'id' => $headerMenu->getId(),
        ]);

        return $this->render('@LexioAdmin/admin/menu/header_update.html.twig', [
            'forms' => $forms,
        ]);
    }

    #[Route('/header/{id}/delete', name: 'admin.menu.header_delete', methods: ['POST'])]
    public function deleteHeader(HeaderMenu $headerMenu): Response
    {
        $this->menuRepository->remove($headerMenu, true);
        $this->addFlash('success', $this->translate('message.item_deleted'));

        return $this->redirectToRoute('admin.menu.header');
    }

    #[Route('/footer', name: 'admin.menu.footer')]
    public function footerMenu(): Response
    {
        $footerMenu = $this->footerMenuRepository->getParents() ?? [];

        $this->prepareBreadcrumbs('admin.menu.footer', 'admin.menu.footer');

        return $this->render('@LexioAdmin/admin/menu/footer.html.twig', [
            'title' => $this->translate('admin.menu.footer'),
            'footerMenu' => $footerMenu,
        ]);
    }

    #[Route('/footer/create', name: 'admin.menu.footer_create')]
    public function footerMenuCreate(Request $request): Response
    {
        $footerMenu = new FooterMenu();
        $form = $this->createForm(FooterMenuType::class, $footerMenu, [
            'translation_domain' => $this->translationDomain(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->menuRepository->save($footerMenu, true);

            return $this->redirectToRoute('admin.menu.footer');
        }

        $this->prepareBreadcrumbs('admin.menu.footer', 'admin.menu.footer_create');

        return $this->render('@LexioAdmin/admin/menu/footer_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/footer/update_order', name: 'admin.menu.footer_update_order', methods: ['POST'])]
    public function orderFooter(Request $request): Response
    {
        $item = null;

        if ($currentItemId = $request->query->get('currentItemId')) {
            /** @var FooterMenu|null $item */
            $item = $this->footerMenuRepository->find($currentItemId);
        }

        if ($item) {
            $item->setPositionIndex((int) $request->query->get('newIndex'));
            $this->menuRepository->flush();
        } else {
            return $this->json(['message' => $this->translate('sorting_error')], 500);
        }

        return $this->json(['message' => $this->translate('sorting_success')]);
    }

    #[Route('/footer/{id}/update', name: 'admin.menu.footer_update')]
    public function footerMenuUpdate(FooterMenu $footerMenu, Request $request): Response
    {
        $forms = [];

        foreach ($this->locales as $locale) {
            $footerMenu->setTranslatableLocale($locale);
            $this->menuRepository->refresh($footerMenu);

            $form = $this->formFactory->createNamed($locale, FooterMenuType::class, $footerMenu, [
                'translation_domain' => $this->translationDomain(),
            ]);
            $forms[$locale] = $form->createView();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $footerMenu->setTranslatableLocale($locale);
                $this->menuRepository->flush();

                $this->addFlash('success', $this->translate('message.item_updated'));

                return $this->redirectToRoute('admin.menu.footer');
            }
        }

        $this->prepareBreadcrumbs('admin.menu.footer', 'admin.menu.footer_update', [
            'id' => $footerMenu->getId(),
        ]);

        return $this->render('@LexioAdmin/admin/menu/footer_update.html.twig', [
            'forms' => $forms,
        ]);
    }

    #[Route('/footer/{id}/delete', name: 'admin.menu.footer_delete', methods: ['POST'])]
    public function deleteFooter(FooterMenu $footerMenu): Response
    {
        $this->menuRepository->remove($footerMenu, true);
        $this->addFlash('success', $this->translate('message.item_deleted'));

        return $this->redirectToRoute('admin.menu.footer');
    }

    /** @param array<string, mixed> $routeParameters */
    private function prepareBreadcrumbs(string $parentKey, ?string $currentKey = null, array $routeParameters = []): void
    {
        $this->breadcrumbs->forHome();
        $this->breadcrumbs->getInstance()->addItem(
            $this->translate($parentKey),
            $this->generateUrl($this->routeForKey($parentKey)),
        );

        if (null !== $currentKey && $parentKey !== $currentKey) {
            $this->breadcrumbs->getInstance()->addItem(
                $this->translate($currentKey),
                $this->generateUrl($this->routeForKey($currentKey), $routeParameters),
            );
        }
    }

    private function routeForKey(string $key): string
    {
        return match ($key) {
            'admin.menu.header' => 'admin.menu.header',
            'admin.menu.header_create' => 'admin.menu.header_create',
            'admin.menu.header_update' => 'admin.menu.header_update',
            'admin.menu.footer' => 'admin.menu.footer',
            'admin.menu.footer_create' => 'admin.menu.footer_create',
            'admin.menu.footer_update' => 'admin.menu.footer_update',
            default => throw new \InvalidArgumentException(sprintf('Unsupported menu breadcrumb key "%s".', $key)),
        };
    }

    private function translate(string $key): string
    {
        return $this->translator->trans($key, [], $this->translationDomain());
    }
}
