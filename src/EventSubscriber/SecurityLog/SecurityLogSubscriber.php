<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber\SecurityLog;

use Lexio\AdminBundle\Entity\SecurityLog;
use Lexio\AdminBundle\Enum\SecurityEvents;
use Lexio\AdminBundle\Event\Security\ForgotPassword;
use Lexio\AdminBundle\Event\Security\PasswordChanged;
use Lexio\AdminBundle\Event\Security\UserHasRegistered;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents as SymfonySecurityEvents;

final readonly class SecurityLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setAffectedUser($event->getTargetUser()->getUserIdentifier())
            ->setType(SecurityEvents::SWITCH_USER)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'))
            ->setActingUser($event->getToken()?->getUserIdentifier());

        $this->manager->persist($eventEntity);
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setAffectedUser($event->getAuthenticationToken()->getUserIdentifier())
            ->setType(SecurityEvents::LOGIN)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'));

        $this->manager->persist($eventEntity);

        $user = $this->security->getUser();
        if ($user !== null && \method_exists($user, 'setLastLoginAt')) {
            $user->setLastLoginAt(new \DateTimeImmutable());
        }

        $this->manager->flush();
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setAffectedUser($request->request->get('email', 'n/a'))
            ->setType(SecurityEvents::LOGIN_FAILURE)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'));

        $this->manager->persist($eventEntity);
        $this->manager->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            return;
        }

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setActingUser($currentUser->getUserIdentifier())
            ->setType(SecurityEvents::LOGOUT)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'))
            ->setAffectedUser($currentUser->getUserIdentifier());

        $this->manager->persist($eventEntity);
        $this->manager->flush();
    }

    public function onPasswordChange(PasswordChanged $event): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return;
        }

        $user = $this->security->getUser();

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setAffectedUser($event->affectedUser)
            ->setType($event->success ? SecurityEvents::PASSWORD_CHANGE : SecurityEvents::PASSWORD_CHANGE_FAILED)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'))
            ->setActingUser($user?->getUserIdentifier());

        $this->manager->persist($eventEntity);
        $this->manager->flush();
    }

    public function onForgotPassword(ForgotPassword $event): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return;
        }

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setActingUser($event->actingUser)
            ->setAffectedUser($event->affectedUser)
            ->setType(SecurityEvents::PASSWORD_RESET_REQUEST)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'));

        $this->manager->persist($eventEntity);
        $this->manager->flush();
    }

    public function onUserRegistered(UserHasRegistered $event): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request) {
            return;
        }

        $eventEntity = new SecurityLog()
            ->setIpAddress($request->getClientIp())
            ->setActingUser($event->actingUser)
            ->setAffectedUser($event->affectedUser)
            ->setType(SecurityEvents::USER_REGISTERED)
            ->setUserAgent($request->headers->get('User-Agent', 'n/a'));

        $this->manager->persist($eventEntity);
        $this->manager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SymfonySecurityEvents::SWITCH_USER => 'onSwitchUser',
            SymfonySecurityEvents::INTERACTIVE_LOGIN => 'onLogin',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
            PasswordChanged::class => 'onPasswordChange',
            ForgotPassword::class => 'onForgotPassword',
            UserHasRegistered::class => 'onUserRegistered',
        ];
    }
}
