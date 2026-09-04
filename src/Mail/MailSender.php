<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Mail;

use Lexio\AdminBundle\Contract\Mail\EmailAddressesProviderInterface;
use Lexio\AdminBundle\Contract\Mail\MailableInterface;
use Lexio\AdminBundle\Contract\Mail\MailSenderInterface;
use Lexio\AdminBundle\Contract\Mail\MailTemplateRepositoryInterface;
use Lexio\AdminBundle\Entity\MailTemplate;
use Lexio\AdminBundle\Event\Mail\EmailFailed;
use Lexio\AdminBundle\Event\Mail\EmailSent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Webmozart\Assert\Assert;

final readonly class MailSender implements MailSenderInterface
{
    public function __construct(
        private MailTemplateRepositoryInterface $mailTemplateRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private EmailAddressesProviderInterface $emailAddressesProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function send(MailableInterface $mailable, ?string $toEmail = null, ?string $fromEmail = null): void
    {
        $templateName = $mailable->getSnakeClassName();

        $mailTemplate = $this->resolveMailTemplate($mailable);
        if (!$mailTemplate->isEnabled()) {
            return;
        }

        $fromEmail ??= $this->emailAddressesProvider->getDefaultSenderEmail();
        $toEmail ??= $this->emailAddressesProvider->getDefaultReceiverEmail();

        try {
            Assert::notNull($mailTemplate->getContent(), \sprintf('Mail template with name %s has no content', $templateName));

            $twigTemplate = $this->twig->createTemplate($mailTemplate->getContent());
            $body = $this->twig->render($twigTemplate, $mailable->toArray());

            $this->mailer->send(
                new TemplatedEmail()
                    ->from($fromEmail)
                    ->to($toEmail)
                    ->subject($mailable->subjectOverride() ?? $mailTemplate->getSubject())
                    ->html($body)
            );
        } catch (\Throwable $exception) {
            $this->eventDispatcher->dispatch(new EmailFailed(
                mailTemplate: $mailTemplate,
                recipientEmail: $toEmail,
                senderEmail: $fromEmail,
                exception: $exception
            ));

            throw $exception;
        }

        $this->eventDispatcher->dispatch(new EmailSent(
            mailTemplate: $mailTemplate,
            recipientEmail: $toEmail,
            senderEmail: $fromEmail,
            sentAt: new \DateTimeImmutable(),
            body: $body
        ));
    }

    public function resolveMailTemplate(MailableInterface $mailable): MailTemplate
    {
        $templateName = $mailable->getSnakeClassName();
        Assert::notNull($templateName);

        $mailTemplate = $this->mailTemplateRepository->findByName($templateName);
        if ($mailTemplate === null) {
            throw new \RuntimeException(\sprintf('Mail template with name %s not found', $templateName));
        }

        return $mailTemplate;
    }
}
