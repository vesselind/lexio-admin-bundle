<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A text input that opens an image-gallery modal for picking an image URL.
 *
 * Requires:
 *   - A Stimulus controller named `input-image-selector`
 *   - A Stimulus controller named `open-base-modal`
 *   - Route `admin.image.modal_gallery`
 */
class InputImageSelectorType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface     $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => function (Options $options): array {
                return [
                    'data-controller'                              => 'input-image-selector open-base-modal',
                    'data-open-base-modal-modal-title-value'       => $this->translator->trans('modal.gallery', [], 'admin'),
                    'data-open-base-modal-visit-url-value'         => $this->router->generate('admin.image.modal_gallery'),
                    'data-open-base-modal-modal-size-value'        => 'modal-xl',
                    'data-open-base-modal-close-on-success-value'  => 'false',
                    'data-action'                                  => 'image-gallery:image-selected@window->input-image-selector#selectImage',
                ];
            },
        ]);
    }
}

