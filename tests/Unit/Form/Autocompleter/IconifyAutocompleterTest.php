<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form\Autocompleter;

use Lexio\AdminBundle\Form\Autocompleter\IconifyAutocompleter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IconifyAutocompleterTest extends TestCase
{
    public function test_it_returns_results_for_the_requested_query_and_page(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame(
                'https://api.iconify.design/search?query=home&limit=32&start=10',
                $url,
            );
            self::assertSame('home', $options['query']['query']);
            self::assertSame(32, $options['query']['limit']);
            self::assertSame(10, $options['query']['start']);

            return new MockResponse(json_encode([
                'icons' => [
                    'mdi:home-10',
                    'mdi:home-11',
                    'mdi:home-12',
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $results = (new IconifyAutocompleter($httpClient))->fetchResults(' home ', 2);

        self::assertSame([
            [
                'value' => 'mdi:home-10',
                'text' => '<span class="d-inline-flex align-items-center gap-2"><img src="https://api.iconify.design/mdi/home-10.svg?height=20" alt="" width="20" height="20" aria-hidden="true"><span>mdi:home-10</span></span>',
            ],
            [
                'value' => 'mdi:home-11',
                'text' => '<span class="d-inline-flex align-items-center gap-2"><img src="https://api.iconify.design/mdi/home-11.svg?height=20" alt="" width="20" height="20" aria-hidden="true"><span>mdi:home-11</span></span>',
            ],
            [
                'value' => 'mdi:home-12',
                'text' => '<span class="d-inline-flex align-items-center gap-2"><img src="https://api.iconify.design/mdi/home-12.svg?height=20" alt="" width="20" height="20" aria-hidden="true"><span>mdi:home-12</span></span>',
            ],
        ], $results->results);
        self::assertFalse($results->hasNextPage);
    }

    public function test_it_escapes_an_unexpected_icon_name(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(json_encode([
            'icons' => ['invalid"<icon>'],
        ], JSON_THROW_ON_ERROR)));

        $results = (new IconifyAutocompleter($httpClient))->fetchResults('icon', 1);

        self::assertSame(
            'invalid&quot;&lt;icon&gt;',
            $results->results[0]['text'],
        );
    }

    public function test_it_does_not_call_iconify_for_an_empty_query(): void
    {
        $httpClient = new MockHttpClient();

        $results = (new IconifyAutocompleter($httpClient))->fetchResults(' ', 1);

        self::assertSame([], $results->results);
        self::assertFalse($results->hasNextPage);
        self::assertSame(0, $httpClient->getRequestsCount());
    }

    public function test_it_returns_no_results_when_iconify_fails(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 503]));

        $results = (new IconifyAutocompleter($httpClient))->fetchResults('home', 1);

        self::assertSame([], $results->results);
        self::assertFalse($results->hasNextPage);
    }
}
