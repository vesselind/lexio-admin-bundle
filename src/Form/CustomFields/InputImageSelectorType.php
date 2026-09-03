<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A text-backed image selector that opens an image-gallery modal.
 *
 * Requires:
 *   - A Stimulus controller named `input-image-selector`
 *   - A Stimulus controller named `open-base-modal`
 *   - Route `admin.image.modal_gallery`
 */
class InputImageSelectorType extends AbstractType
{
    private const array CONTROLLER_ATTRIBUTE_NAMES = [
        'data-controller',
        'data-open-base-modal-modal-title-value',
        'data-open-base-modal-visit-url-value',
        'data-open-base-modal-modal-size-value',
        'data-open-base-modal-close-on-success-value',
        'data-action',
    ];

    private const string GALLERY_TITLE_ATTRIBUTE = 'data-open-base-modal-modal-title-value';
    private const string GALLERY_URL_ATTRIBUTE = 'data-open-base-modal-visit-url-value';

    public function __construct(
        private readonly RouterInterface      $router,
        private readonly TranslatorInterface $translator,
        private readonly string              $imageGalleryRouteName = 'admin.image.modal_gallery',
        private readonly string              $translationDomain = 'LexioAdminBundle',
    ) {
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $controllerAttributes = $this->getControllerAttributes();

        $resolver->setDefaults([
            'attr' => $controllerAttributes,
        ]);

        // Keep the Stimulus contract when a consumer adds ordinary form attributes.
        $resolver->setNormalizer(
            'attr',
            static fn (Options $options, array $attributes): array => array_replace($controllerAttributes, $attributes),
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attributes = $view->vars['attr'];

        $view->vars['imageGalleryUrl'] = (string) ($attributes[self::GALLERY_URL_ATTRIBUTE] ?? '');
        $view->vars['imageGalleryModalTitle'] = (string) ($attributes[self::GALLERY_TITLE_ATTRIBUTE] ?? '');

        // The form value stays on the form control, while the selector's
        // Stimulus attributes are rendered on the visual component root.
        $inputAttributes = array_diff_key(
            $attributes,
            array_fill_keys(self::CONTROLLER_ATTRIBUTE_NAMES, true),
        );

        foreach (self::CONTROLLER_ATTRIBUTE_NAMES as $attributeName) {
            // FormRenderer merges nested widget attributes with the current
            // widget scope. Explicit false values prevent the controller
            // attributes from leaking back onto the hidden form control.
            $inputAttributes[$attributeName] = false;
        }

        $view->vars['imageSelectorInputAttr'] = $inputAttributes;
    }

    public function getBlockPrefix(): string
    {
        return 'input_image_selector';
    }

    /** @return array<string, string> */
    private function getControllerAttributes(): array
    {
        return [
            'data-controller' => 'input-image-selector open-base-modal',
            self::GALLERY_TITLE_ATTRIBUTE => $this->translator->trans('modal.gallery', [], $this->translationDomain),
            self::GALLERY_URL_ATTRIBUTE => $this->router->generate($this->imageGalleryRouteName),
            'data-open-base-modal-modal-size-value' => 'modal-xl',
            'data-open-base-modal-close-on-success-value' => 'false',
            'data-action' => 'image-gallery:image-selected@window->input-image-selector#selectImage',
        ];
    }
}

