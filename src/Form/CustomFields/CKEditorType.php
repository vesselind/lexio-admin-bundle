<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Textarea that activates the CKEditor Stimulus controller.
 *
 * Requires a Stimulus controller named `ckeditor` to be registered in the app.
 * Optionally enables a link-search sidebar via the `links_search` option.
 */
class CKEditorType extends AbstractType
{
    private const MEDIA_RESIZE_KEYS = [
        'media_resize.label',
        'media_resize.current_width',
        'media_resize.original',
        'media_resize.preset_75',
        'media_resize.preset_50',
        'media_resize.preset_25',
        'media_resize.custom',
        'media_resize.width_prompt',
    ];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $uploadRouteName = 'admin.ckeditor.upload',
        private readonly ?TranslatorInterface $translator = null,
    )
    {
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        try {
            $defaultDataUploadUrl = $this->router->generate($this->uploadRouteName);
        } catch (\Exception) {
            throw new \RuntimeException(sprintf(
                'The route "%s" must be defined in the host application for CKEditorType to work.',
                $this->uploadRouteName,
            ));
        }

        $resolver->setDefaults([
            'links_search'      => true,
            'required'          => false,
            'translation_domain' => 'form',
            'attr'         => function (Options $options) use ($defaultDataUploadUrl): array {
                return [
                    'data-controller'                    => 'ckeditor',
                    'data-ckeditor-upload-url-value'    => $defaultDataUploadUrl,
                    // Retained for hosts that still read the pre-package attribute.
                    'data-upload-url'                    => $defaultDataUploadUrl,
                ];
            },
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['linksSearch'] = $options['links_search'];

        if (null === $this->translator) {
            return;
        }

        $locale = $this->translator->getLocale();
        $translations = [];
        foreach (array_unique(['en', 'bg', $locale]) as $language) {
            $dictionary = [];
            foreach (self::MEDIA_RESIZE_KEYS as $key) {
                $dictionary[$key] = $this->translator->trans($key, [], 'ckeditor', $language);
            }
            $translations[$language] = ['dictionary' => $dictionary];
        }

        $view->vars['attr']['data-ckeditor-locale-value'] ??= $locale;
        $view->vars['attr']['data-ckeditor-translations-value'] ??= json_encode($translations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}

