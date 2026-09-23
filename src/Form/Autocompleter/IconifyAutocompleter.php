<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\Autocompleter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Autocomplete\AutocompleteResults;
use Symfony\UX\Autocomplete\AutocompleterInterface;

final readonly class IconifyAutocompleter implements AutocompleterInterface
{
    private const string API_ENDPOINT = 'https://api.iconify.design/search';
    private const int API_LIMIT = 32;
    private const int MAX_QUERY_LENGTH = 100;
    private const int RESULTS_PER_PAGE = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function fetchResults(string $query, int $page): AutocompleteResults
    {
        $query = trim($query);

        if ($query === '') {
            return new AutocompleteResults([], false);
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::RESULTS_PER_PAGE;

        try {
            $response = $this->httpClient->request('GET', self::API_ENDPOINT, [
                'query' => [
                    'query' => mb_substr($query, 0, self::MAX_QUERY_LENGTH),
                    'limit' => self::API_LIMIT,
                    'start' => $offset,
                ],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) {
                return new AutocompleteResults([], false);
            }

            $payload = $response->toArray(false);
        } catch (\Throwable) {
            return new AutocompleteResults([], false);
        }

        if (!is_array($payload['icons'] ?? null)) {
            return new AutocompleteResults([], false);
        }

        $icons = [];
        foreach ($payload['icons'] as $icon) {
            if (is_string($icon) && $icon !== '') {
                $icons[] = $icon;
            }
        }

        $results = [];
        foreach (array_slice($icons, 0, self::RESULTS_PER_PAGE) as $icon) {
            $results[] = [
                'value' => $icon,
                'text' => $this->formatIconOption($icon),
            ];
        }

        return new AutocompleteResults(
            $results,
            count($icons) > self::RESULTS_PER_PAGE,
        );
    }

    private function formatIconOption(string $icon): string
    {
        if (!preg_match('/\\A([a-z0-9-]+):([a-z0-9-]+)\\z/i', $icon, $matches)) {
            return htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $iconUrl = sprintf(
            'https://api.iconify.design/%s/%s.svg?height=20',
            rawurlencode($matches[1]),
            rawurlencode($matches[2]),
        );

        return sprintf(
            '<span class="d-inline-flex align-items-center gap-2"><img src="%s" alt="" width="20" height="20" aria-hidden="true"><span>%s</span></span>',
            htmlspecialchars($iconUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    public function isGranted(Security $security): bool
    {
        return $security->isGranted('ROLE_EDITOR');
    }
}
