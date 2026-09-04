<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class BulkContext
{
    /** @var list<int> */
    private array $ids = [];

    public function __construct(
        private readonly RequestStack              $requestStack,
        private readonly EntityManagerInterface    $manager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
        $this->deserialize();
    }

    private function deserialize(): void
    {
        $query = $this->requestStack->getCurrentRequest()?->query->all() ?? [];
        $ids = $query['ids'] ?? [];

        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $id) {
            if (is_int($id) && $id > 0) {
                $this->ids[] = $id;

                continue;
            }

            if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
                $this->ids[] = (int) $id;
            }
        }
    }

    /**
     * @return list<int>
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @param class-string $entityFqcn
     *
     * @return list<object>
     */
    public function getEntities(string $entityFqcn): array
    {
        if ($this->ids === []) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        $token = (string) $request?->headers->get('X-CSRF-Token');

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('bulk_action', $token))) {
            throw new AccessDeniedHttpException('Invalid bulk action CSRF token.');
        }

        return $this->manager->getRepository($entityFqcn)->findBy(['id' => $this->ids]);
    }
}

