<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form;

use Lexio\AdminBundle\Form\IconifySearcherType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;

final class IconifySearcherTypeTest extends TypeTestCase
{
    public function test_it_configures_the_iconify_autocomplete_defaults(): void
    {
        $form = $this->factory->create(IconifySearcherType::class);

        self::assertTrue($form->getConfig()->getOption('autocomplete'));
        self::assertSame('/autocomplete?alias=iconify', $form->getConfig()->getOption('autocomplete_url'));
        self::assertSame('placeholder.icon', $form->getConfig()->getOption('attr')['placeholder']);
        self::assertSame(2, $form->getConfig()->getOption('min_characters'));
        self::assertTrue($form->getConfig()->getOption('options_as_html'));
        self::assertSame(['maxItems' => 1], $form->getConfig()->getOption('tom_select_options'));
        self::assertSame('false', $form->getConfig()->getOption('preload'));
    }

    public function test_it_accepts_a_remote_icon_value_when_submitted(): void
    {
        $form = $this->factory->create(IconifySearcherType::class);

        $form->submit('mdi:home');

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertSame('mdi:home', $form->getData());
    }

    /** @return list<PreloadedExtension> */
    protected function getExtensions(): array
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/autocomplete?alias=iconify');

        return [
            new PreloadedExtension(
                [new IconifySearcherType($urlGenerator)],
                [
                    TextType::class => [
                        new AutocompleteChoiceTypeExtension(new ChecksumCalculator('test-secret')),
                    ],
                ],
            ),
        ];
    }
}
