<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Contract\Mail\MailTemplateRepositoryInterface;
use Lexio\AdminBundle\Entity\MailTemplate;
use Lexio\AdminBundle\Filter\MailTemplateFilter;

/**
 * @extends ServiceEntityRepository<MailTemplate>
 *
 * @method MailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailTemplate|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method MailTemplate[]    findAll()
 * @method MailTemplate[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 */
final class MailTemplateRepository extends ServiceEntityRepository implements MailTemplateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailTemplate::class);
    }

    public function filtered(MailTemplateFilter $filter): QueryBuilder
    {
        $builder = $this->createQueryBuilder('mail_template');

        if ($filter->content) {
            $builder
                ->andWhere('mail_template.content LIKE :content')
                ->setParameter('content', '%' . $filter->content . '%');
        }

        if ($filter->subject) {
            $builder
                ->andWhere('mail_template.subject LIKE :subject')
                ->setParameter('subject', '%' . $filter->subject . '%');
        }

        if ($filter->name) {
            $builder
                ->andWhere('mail_template.name LIKE :name')
                ->setParameter('name', '%' . $filter->name . '%');
        }

        return $builder;

    }

    public function findByName(string $name): ?MailTemplate
    {
        return $this->findOneBy(['name' => $name]);
    }
}
