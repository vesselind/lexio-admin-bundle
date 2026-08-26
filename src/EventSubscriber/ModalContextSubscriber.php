<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;

/**
 * Intercepts responses in a modal context and returns the appropriate Turbo Stream response
 * to communicate newly created entity data back to the parent form.
 */
final readonly class ModalContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment            $twig,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $modalContext = $request->query->get('_modal_context');
        if ($modalContext !== 'association') {
            return;
        }

        $response = $event->getResponse();

        if (!$response->isRedirect() || $response->getStatusCode() !== 302) {
            return;
        }

        $entityClass = $request->query->get('_modal_entity_class');
        $choiceLabel = $request->query->get('_modal_choice_label', 'title');

        $entity = $request->attributes->get('_created_entity');

        if (!$entity || !$entityClass) {
            return;
        }

        if (!\is_a($entity, $entityClass)) {
            return;
        }

        try {
            $locale = $request->getLocale();

            if (\method_exists($entity, 'setTranslatableLocale')) {
                $entity->setTranslatableLocale($locale);
                $this->entityManager->refresh($entity);
            }

            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $value = $propertyAccessor->getValue($entity, $choiceLabel);
            $entityId = $entity->getId();

            $content = $this->twig->render('@LexioAdmin/admin/_modals/_association_modal_success.html.twig', [
                'entityId' => $entityId,
                'value' => $value,
                'class' => $entityClass,
            ]);

            $newResponse = new Response($content);
            $newResponse->headers->set('Content-Type', 'text/vnd.turbo-stream.html');

            $event->setResponse($newResponse);
        } catch (\Exception) {
            return;
        }
    }
}
