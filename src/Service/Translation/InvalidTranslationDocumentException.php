<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

final class InvalidTranslationDocumentException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?int $documentLine = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getDocumentLine(): ?int
    {
        return $this->documentLine;
    }
}
