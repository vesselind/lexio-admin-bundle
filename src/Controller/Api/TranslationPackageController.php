<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Lexio\AdminBundle\Service\Translation\TranslationPackageManager;
use Lexio\AdminBundle\Service\Translation\TranslationPackageRequestAuthenticator;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Extend this controller in a host application and add the unlocalized API route prefix.
 */
abstract class TranslationPackageController
{
    #[Route('/download', name: 'api.translation_package.download', methods: ['GET'])]
    public function download(
        Request $request,
        TranslationPackageRequestAuthenticator $authenticator,
        TranslationPackageManager $packageManager,
    ): Response {
        if (!$this->isAuthenticated($request, '', $authenticator)) {
            return $this->problem(Response::HTTP_UNAUTHORIZED, 'Unauthorized', 'The translation package signature is missing, invalid, or expired.');
        }

        try {
            $archive = $packageManager->export();
        } catch (TranslationSynchronizationException) {
            return $this->problem(Response::HTTP_INTERNAL_SERVER_ERROR, 'Translation package unavailable', 'The translation package could not be prepared.');
        }

        return new Response($archive, Response::HTTP_OK, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'translations.zip'),
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    #[Route('/upload', name: 'api.translation_package.upload', methods: ['POST'])]
    public function upload(
        Request $request,
        TranslationPackageRequestAuthenticator $authenticator,
        TranslationPackageManager $packageManager,
    ): Response {
        $archive = $request->getContent();
        if (!$this->isAuthenticated($request, $archive, $authenticator)) {
            return $this->problem(Response::HTTP_UNAUTHORIZED, 'Unauthorized', 'The translation package signature is missing, invalid, or expired.');
        }

        try {
            $result = $packageManager->import($archive);
        } catch (TranslationSynchronizationException) {
            return $this->problem(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid translation package', 'The translation package could not be imported.');
        }

        return new JsonResponse($this->resultData($result));
    }

    private function isAuthenticated(
        Request $request,
        string $body,
        TranslationPackageRequestAuthenticator $authenticator,
    ): bool {
        return $authenticator->isValid(
            $request->getMethod(),
            $request->getPathInfo(),
            $body,
            $request->headers->get(TranslationPackageRequestAuthenticator::TIMESTAMP_HEADER),
            $request->headers->get(TranslationPackageRequestAuthenticator::SIGNATURE_HEADER),
        );
    }

    /** @return array<string, int> */
    private function resultData(TranslationPackageMergeResult $result): array
    {
        return [
            'files_created' => $result->filesCreated,
            'files_updated' => $result->filesUpdated,
            'keys_inserted' => $result->keysInserted,
            'keys_updated' => $result->keysUpdated,
            'keys_unchanged' => $result->keysUnchanged,
        ];
    }

    private function problem(int $status, string $title, string $detail): JsonResponse
    {
        return new JsonResponse([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
