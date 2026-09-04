<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Mail;

use Lexio\AdminBundle\Contract\Mail\EmailAddressesProviderInterface;
use Lexio\AdminBundle\Contract\Mail\MailSenderInterface;
use Lexio\AdminBundle\Contract\Mail\MailTemplateRepositoryInterface;
use Lexio\AdminBundle\Entity\MailTemplate;
use Lexio\AdminBundle\Mail\MailSender;
use Lexio\AdminBundle\Mail\Mailable;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class MailSenderBoundaryTest extends TestCase
{
    public function test_sender_uses_the_public_repository_and_sender_contracts(): void
    {
        $repository = $this->createMock(MailTemplateRepositoryInterface::class);
        $repository->expects(self::once())->method('findByName')->with('bundle_test_mail')->willReturn(
            (new MailTemplate())->setSubject('Subject')->setContent('Body'),
        );

        $sender = new MailSender(
            $repository,
            $this->createStub(MailerInterface::class),
            new Environment(new ArrayLoader([])),
            $this->createStub(EmailAddressesProviderInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );

        self::assertInstanceOf(MailSenderInterface::class, $sender);

        $sender->send(new class extends Mailable {
            public function __construct()
            {
                $this->classSnakeName = 'bundle_test_mail';
            }

            public function subject(): string
            {
                return 'Subject';
            }
        }, 'to@example.test', 'from@example.test');
    }
}
