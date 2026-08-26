<?php

namespace Lexio\AdminBundle\Seeder;


use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Contract\Seeder\SeedersRegistryInterface;
use Lexio\AdminBundle\Entity\Seed as SeederEntity;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class SeedLoader
{
    public function __construct(
        private EntityManagerInterface   $manager,
        private MessageBusInterface      $messageBus,
        private SeedersRegistryInterface $seedersRegistry
    )
    {
    }
    public function load(SymfonyStyle $io): void
    {
        foreach ($this->seedersRegistry->getRegistry() as $seedClass) {
            $hasRun = $this->manager->getRepository(SeederEntity::class)->findOneBy(['name' => $seedClass]);

            if ($hasRun) {
                continue;
            }

            $this->messageBus->dispatch(new $seedClass());

            $seederEntity = new SeederEntity();
            $seederEntity->setName($seedClass);

            $this->manager->persist($seederEntity);
            $this->manager->flush();

            $io->success(sprintf('%s was loaded successfully.', $seedClass));
        }
    }
}