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
use Symfony\UX\LiveComponent\Attribute\LiveProp;

final class ConfirmationModalTest extends TestCase
{
    public function test_confirmation_preserves_string_subject_ids_for_delete_validation(): void
    {
        $request = Request::create('/admin');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $component = new ConfirmationModal($requestStack);
        $component->mount('42', '/admin/items/42/delete', null, '42_delete');

        $response = $component->confirm();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/items/42/delete', $response->getTargetUrl());
        self::assertSame('42', $request->getSession()->get(ConfirmationModal::CONFIRMED_SESSION_KEY));
        self::assertSame('42_delete', $component->modalId);
    }

    public function test_modal_id_defaults_to_the_subject_id(): void
    {
        $component = new ConfirmationModal(new RequestStack());
        $component->mount('42');

        self::assertSame('42', $component->modalId);
    }

    public function test_confirmation_configuration_is_hydrated_by_live_component_requests(): void
    {
        foreach (['subjectId', 'modalId', 'confirmUrl', 'dispatchEventName'] as $propertyName) {
            $property = new \ReflectionProperty(ConfirmationModal::class, $propertyName);

            self::assertNotEmpty(
                $property->getAttributes(LiveProp::class),
                sprintf('The %s property must remain a LiveProp.', $propertyName),
            );
        }

        self::assertSame('string', (new \ReflectionProperty(ConfirmationModal::class, 'subjectId'))->getType()?->getName());
        self::assertSame('string', (new \ReflectionProperty(ConfirmationModal::class, 'modalId'))->getType()?->getName());
    }
}
