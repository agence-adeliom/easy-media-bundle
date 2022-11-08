<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Form;

use Adeliom\EasyMediaBundle\Service\EasyMediaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EasyMediaType extends AbstractType
{
    public function __construct(
        /**
         * @readonly
         */
        private EasyMediaManager $manager
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'restrictions_path' => null,
            'restrictions_uploadTypes' => null,
            'restrictions_uploadSize' => null,
            'hideExt' => null,
            'hidePath' => null,
            'editor' => true,
            'upload' => true,
            'bulk_selection' => true,
            'move' => true,
            'rename' => true,
            'metas' => true,
            'delete' => true,
        ]);

        $resolver->setAllowedTypes('restrictions_path', ['null', 'string']);
        $resolver->setAllowedTypes('restrictions_uploadTypes', ['null', 'array']);
        $resolver->setAllowedTypes('restrictions_uploadSize', ['null', 'float']);
        $resolver->setAllowedTypes('hideExt', ['null', 'array']);
        $resolver->setAllowedTypes('hidePath', ['null', 'array']);
        $resolver->setAllowedTypes('editor', 'bool');
        $resolver->setAllowedTypes('upload', 'bool');
        $resolver->setAllowedTypes('bulk_selection', 'bool');
        $resolver->setAllowedTypes('move', 'bool');
        $resolver->setAllowedTypes('rename', 'bool');
        $resolver->setAllowedTypes('metas', 'bool');
        $resolver->setAllowedTypes('delete', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($media) {
                if (empty($media)) {
                    return '';
                }

                $class = $this->manager->getHelper()->getMediaClassName();
                if (!($media instanceof $class)) {
                    $media = $this->manager->getMedia($media);
                }

                if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
                    return '';
                }

                return $media->getId();
            },
            function ($mediaId) {
                if (!$mediaId) {
                    return null;
                }

                $media = $this->manager->getMedia($mediaId);

                if (!$media instanceof \Adeliom\EasyMediaBundle\Entity\Media) {
                    throw new TransformationFailedException(sprintf('An media with id "%s" does not exist!', $mediaId));
                }

                return $media;
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['restrict'] = [
            'path' => $options['restrictions_path'],
            'uploadTypes' => $options['restrictions_uploadTypes'],
            'uploadSize' => $options['restrictions_uploadSize'],
        ];

        $view->vars['hideExt'] = $options['hideExt'];
        $view->vars['hidePath'] = $options['hidePath'];
        $view->vars['editor'] = $options['editor'];
        $view->vars['upload'] = $options['upload'];
        $view->vars['move'] = $options['move'];
        $view->vars['rename'] = $options['rename'];
        $view->vars['metas'] = $options['metas'];
        $view->vars['delete'] = $options['delete'];
        $view->vars['bulk_selection'] = $options['bulk_selection'];
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'easy_media';
    }
}
