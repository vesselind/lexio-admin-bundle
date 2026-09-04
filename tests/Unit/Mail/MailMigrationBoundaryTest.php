<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Mail;

use Doctrine\ORM\Mapping as ORM;
use Lexio\AdminBundle\Entity\EmailDelivery;
use Lexio\AdminBundle\Filter\EmailDeliveryFilter;
use Lexio\AdminBundle\Filter\MailTemplateFilter;
use Lexio\AdminBundle\Repository\EmailDeliveryRepository;
use Lexio\AdminBundle\Repository\MailTemplateRepository;
use PHPUnit\Framework\TestCase;

final class MailMigrationBoundaryTest extends TestCase
{
    public function test_migrated_mail_entities_and_repositories_do_not_reference_host_classes(): void
    {
        $deliveryEntity = (new \ReflectionClass(EmailDelivery::class))
            ->getAttributes(ORM\Entity::class)[0]
            ->newInstance();

        self::assertSame(EmailDeliveryRepository::class, $deliveryEntity->repositoryClass);
        self::assertSame(
            MailTemplateFilter::class,
            (new \ReflectionMethod(MailTemplateRepository::class, 'filtered'))->getParameters()[0]->getType()?->getName(),
        );
        self::assertSame(
            EmailDeliveryFilter::class,
            (new \ReflectionMethod(EmailDeliveryRepository::class, 'filtered'))->getParameters()[0]->getType()?->getName(),
        );
    }
}
