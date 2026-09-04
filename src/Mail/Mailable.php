<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Mail;


use Lexio\AdminBundle\Contract\Mail\MailableInterface;
use Lexio\AdminBundle\Service\Utils;

abstract class Mailable implements MailableInterface
{
    protected ?string $classSnakeName = null;
    public function getSnakeClassName(): ?string
    {
        if ($this->classSnakeName === null) {
            $this->classSnakeName = Utils::classNameToSnake(static::class);
        }

        return $this->classSnakeName;

    }
    public function templatePath(): string
    {
        return 'mail/' . $this->getSnakeClassName() . '.html.twig';
    }

    abstract public function subject(): string;

    public function subjectOverride(): ?string
    {
        return null;
    }


    public function toArray(): array
    {
        //get current class properties
        return get_object_vars($this);
    }
}
