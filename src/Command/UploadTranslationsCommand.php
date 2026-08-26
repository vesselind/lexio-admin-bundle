<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command;

use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UploadTranslationsCommand extends Command
{
    public function __construct(
        private readonly TranslationPackageSynchronizerInterface $synchronizer,
    ) {
        parent::__construct('lexio:translations:upload');
        $this->setDescription('Send the local translation package to the configured deployed application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Upload translations');

        try {
            $result = $this->synchronizer->upload();
        } catch (TranslationSynchronizationException) {
            $io->error('The translations could not be sent.');

            return self::FAILURE;
        }

        $io->success($this->summary($result));

        return self::SUCCESS;
    }

    private function summary(TranslationPackageMergeResult $result): string
    {
        return sprintf(
            'Translations sent. Files created: %d, files updated: %d, keys inserted: %d, keys updated: %d, unchanged keys: %d.',
            $result->filesCreated,
            $result->filesUpdated,
            $result->keysInserted,
            $result->keysUpdated,
            $result->keysUnchanged,
        );
    }
}
