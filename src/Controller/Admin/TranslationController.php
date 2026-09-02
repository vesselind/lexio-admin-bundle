<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\Contract\Translation\TranslationCatalogInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationCatalogException;
use Lexio\AdminBundle\Contract\Translation\TranslationEntry;
use Lexio\AdminBundle\Contract\Translation\TranslationFile;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Service\Translation\TranslationCacheClearer;
use Lexio\AdminBundle\Service\Translation\TranslationCacheException;
use Lexio\AdminBundle\Service\Translation\TranslationEntryFilter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Extend this controller in a host application and add its admin route prefix and authorization rule.
 */
abstract class TranslationController extends BaseController
{
    private const int ITEMS_PER_PAGE = 20;

    #[Route('', name: 'admin.translation.index', methods: ['GET'])]
    public function index(
        Request $request,
        TranslationCatalogInterface $catalog,
        TranslationEntryFilter $entryFilter,
        PaginatorInterface $paginator,
        TranslationPackageSynchronizerInterface $synchronizer,
    ): Response
    {
        $this->assertEnabled($catalog);

        try {
            /** @var list<TranslationFile> $files */
            $files = array_values(iterator_to_array($catalog->listFiles()));
        } catch (TranslationCatalogException) {
            throw $this->createNotFoundException();
        }

        $selectedFile = $this->selectFile($files, $request->query->getString('file'));
        /** @var list<TranslationEntry> $entries */
        $entries = [];

        if (null !== $selectedFile) {
            try {
                /** @var list<TranslationEntry> $entries */
                $entries = array_values(iterator_to_array($catalog->getEntries($selectedFile->domain, $selectedFile->locale)));
            } catch (TranslationCatalogException) {
                throw $this->createNotFoundException();
            }
        }

        $searchKey = $request->query->getString('search_key');
        $searchValue = $request->query->getString('search_value');
        $filteredEntries = $entryFilter->filter($entries, $searchKey, $searchValue);
        $entries = $paginator->paginate(
            $filteredEntries,
            max(1, $request->query->getInt('page', 1)),
            self::ITEMS_PER_PAGE,
        );

        return $this->render('@LexioAdmin/admin/translation/index.html.twig', [
            'files' => $files,
            'selectedFile' => $selectedFile,
            'entries' => $entries,
            'searchKey' => $searchKey,
            'searchValue' => $searchValue,
            'canSynchronizeTranslations' => $synchronizer->canUseAdminActions(),
        ]);
    }

    #[Route('/update', name: 'admin.translation.update', methods: ['POST'])]
    public function update(
        Request $request,
        TranslationCatalogInterface $catalog,
        ValidatorInterface $validator,
    ): Response {
        $this->assertEnabled($catalog);

        if (!$this->isCsrfTokenValid('translation.update', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $input = new UpdateTranslationInput(
            domain: $request->request->getString('domain'),
            locale: $request->request->getString('locale'),
            key: $request->request->getString('key'),
            value: $request->request->getString('value'),
        );

        if (0 < $validator->validate($input)->count()) {
            $this->addFlash(
                Flash::ERROR->value,
                $this->translator()->trans('translation.update_failed', [], $this->translationDomain()),
            );

            return $this->redirectToRoute('admin.translation.index', $this->indexParameters($request, $input->domain, $input->locale));
        }

        try {
            $catalog->update($input->domain, $input->locale, $input->key, $input->value);
            $this->addFlash(
                Flash::SUCCESS->value,
                $this->translator()->trans('translation.updated', [], $this->translationDomain()),
            );
        } catch (TranslationCatalogException) {
            $this->addFlash(
                Flash::ERROR->value,
                $this->translator()->trans('translation.update_failed', [], $this->translationDomain()),
            );
        }

        return $this->redirectToRoute('admin.translation.index', $this->indexParameters($request, $input->domain, $input->locale));
    }

    #[Route('/send', name: 'admin.translation.send', methods: ['POST'])]
    public function send(Request $request, TranslationPackageSynchronizerInterface $synchronizer): Response
    {
        return $this->synchronize($request, $synchronizer, 'upload');
    }

    #[Route('/receive', name: 'admin.translation.receive', methods: ['POST'])]
    public function receive(Request $request, TranslationPackageSynchronizerInterface $synchronizer): Response
    {
        return $this->synchronize($request, $synchronizer, 'download');
    }

    #[Route('/clear-cache', name: 'admin.translation.clear_cache', methods: ['POST'])]
    public function clearCache(
        Request $request,
        TranslationCatalogInterface $catalog,
        TranslationCacheClearer $cacheClearer,
    ): Response {
        $this->assertEnabled($catalog);

        if (!$this->isCsrfTokenValid('translation.cache.clear', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $cacheClearer->clear();
            $this->addFlash(
                Flash::SUCCESS->value,
                $this->translator()->trans('translation.cache.cleared', [], $this->translationDomain()),
            );
        } catch (TranslationCacheException) {
            $this->addFlash(
                Flash::ERROR->value,
                $this->translator()->trans('translation.cache.clear_failed', [], $this->translationDomain()),
            );
        }

        return $this->redirectToRoute(
            'admin.translation.index',
            $this->listingParameters($request, $request->request->getString('file')),
        );
    }

    /** @param list<TranslationFile> $files */
    private function selectFile(array $files, string $filename): ?TranslationFile
    {
        if ([] === $files) {
            return null;
        }

        if ('' === $filename) {
            return $files[0];
        }

        foreach ($files as $file) {
            if ($file->filename === $filename) {
                return $file;
            }
        }

        throw $this->createNotFoundException();
    }

    private function assertEnabled(TranslationCatalogInterface $catalog): void
    {
        if (!$catalog->isEnabled()) {
            throw $this->createNotFoundException();
        }
    }

    /** @return array<string, int|string> */
    private function indexParameters(Request $request, string $domain, string $locale): array
    {
        return $this->listingParameters($request, $domain . '.' . $locale . '.yaml');
    }

    private function synchronize(
        Request $request,
        TranslationPackageSynchronizerInterface $synchronizer,
        string $operation,
    ): Response {
        if (!$synchronizer->canUseAdminActions()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('translation.sync.' . $operation, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $result = 'upload' === $operation ? $synchronizer->upload() : $synchronizer->download();
            $this->addFlash(
                Flash::SUCCESS->value,
                $this->translator()->trans('translation.sync.' . $operation . '.success', $this->resultParameters($result), $this->translationDomain()),
            );
        } catch (TranslationSynchronizationException) {
            $this->addFlash(
                Flash::ERROR->value,
                $this->translator()->trans('translation.sync.' . $operation . '.failed', [], $this->translationDomain()),
            );
        }

        return $this->redirectToRoute(
            'admin.translation.index',
            $this->listingParameters($request, $request->request->getString('file')),
        );
    }

    /** @return array<string, int> */
    private function resultParameters(TranslationPackageMergeResult $result): array
    {
        return [
            '%files_created%' => $result->filesCreated,
            '%files_updated%' => $result->filesUpdated,
            '%keys_inserted%' => $result->keysInserted,
            '%keys_updated%' => $result->keysUpdated,
            '%keys_unchanged%' => $result->keysUnchanged,
        ];
    }

    /** @return array<string, int|string> */
    private function listingParameters(Request $request, string $filename): array
    {
        $parameters = [];
        if ('' !== $filename) {
            $parameters['file'] = $filename;
        }

        foreach (['search_key', 'search_value'] as $parameter) {
            $value = trim($request->request->getString($parameter));
            if ('' !== $value) {
                $parameters[$parameter] = $value;
            }
        }

        $page = max(1, $request->request->getInt('page', 1));
        if (1 !== $page) {
            $parameters['page'] = $page;
        }

        return $parameters;
    }
}
