<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Http;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A redirect response that can target a specific Hotwire Turbo frame.
 *
 * When the target frame is '_top' this behaves exactly like a standard RedirectResponse.
 * For any other frame name the response carries a `Turbo-Frame` header so that the
 * client-side Turbo JS routes the redirect into the named frame.
 */
final class TurboRedirectResponse extends RedirectResponse
{
    public function __construct(
        string $url,
        string $targetFrame = '_top',
    ) {
        parent::__construct($url, Response::HTTP_SEE_OTHER);

        if ($targetFrame !== '_top') {
            $this->headers->set('Turbo-Frame', $targetFrame);
        }
    }
}

