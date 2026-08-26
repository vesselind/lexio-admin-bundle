<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\AutoTranslator;
use DeepL\DeepLException;
use DeepL\Translator;
use Lexio\AdminBundle\Contract\AutoTranslator\AutoTranslatorInterface;

class DeeplAutoTranslator implements AutoTranslatorInterface
{
    public function __construct(private readonly ?string $deeplKey = null)
    {
    }

    /**
     * @throws DeepLException|\RuntimeException
     */
    public function getClient(): Translator
    {
        $key = $this->getDeeplKey();

        if (!$key) {
            throw new \RuntimeException(
                'DeepL translation key is not set. Please configure it in the settings.'
            );
        }

        return new Translator($key);
    }

    public function translate(string $text, string $fromLocale, string $toLocale): string
    {
        return $this->getClient()->translateText($text, $fromLocale, $toLocale)->text;
    }

    public function isActivated(): bool
    {
        try {
            $this->getClient();
        } catch (\RuntimeException|DeepLException) {
            return false;
        }

        return true;
    }

    private function getDeeplKey(): ?string
    {
        return $this->deeplKey;
    }
}

