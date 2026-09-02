<?php

namespace Lexio\AdminBundle\Page;

use Lexio\AdminBundle\Form\CustomFields\CKEditorType;
use Lexio\AdminBundle\Form\CustomFields\InputImageSelectorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

enum ContentItemTypes: string
{
    case IMAGE_PATH = 'image_path';
    case LINK_URL = 'link_url';
    case RICH_TEXT = 'rich_text';
    case TEXTAREA = 'textarea';
    case TEXT_INPUT = 'text_input';


    /**
     * @return class-string<\Symfony\Component\Form\FormTypeInterface>
     */
    public function getFormTypeClass(): string
    {
        return match ($this) {
            self::IMAGE_PATH => InputImageSelectorType::class,
            self::LINK_URL, self::TEXT_INPUT => TextType::class,
            self::RICH_TEXT => CKEditorType::class,
            self::TEXTAREA => TextareaType::class
        };
    }

    public function translatable(): bool
    {
        return match ($this) {
            self::IMAGE_PATH => false,
            default => true,
        };
    }
}
