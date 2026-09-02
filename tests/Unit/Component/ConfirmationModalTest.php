<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Component;

use Lexio\AdminBundle\Component\ConfirmationModal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ConfirmationModalTest extends TestCase
{
    public function test_confirmation_preserves_numeric_subject_ids_for_delete_validation(): void
    {
        $request = Request::create('/admin');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $component = new ConfirmationModal($requestStack);
        $component->mount(42, '/admin/items/42/delete');

        $response = $component->confirm();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/items/42/delete', $response->getTargetUrl());
        self::assertSame(42, $request->getSession()->get(ConfirmationModal::CONFIRMED_SESSION_KEY));
    }
}
