<?php

declare(strict_types=1);

namespace Adeliom\EasyMediaBundle\Admin\Field;

use Adeliom\EasyMediaBundle\Form\EasyMediaType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

class EasyMediaField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param string|true|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)

            // this template is used in 'index' and 'detail' pages
            ->setTemplatePath('@EasyMedia/admin/field/easy-media.html.twig')

            // this is used in 'edit' and 'new' pages to edit the field contents
            // you can use your own form types too
            ->setFormType(EasyMediaType::class)
            ->addCssClass('field-easy-media')
            ->setDefaultColumns('col-md-8 col-xxl-7')

            // loads the CSS and JS assets associated to the given Webpack Encore entry
            // in any CRUD page (index/detail/edit/new). It's equivalent to calling
            // encore_entry_link_tags('...') and encore_entry_script_tags('...')
            // ->addWebpackEncoreEntry('admin-field-map')

        ;
    }
}
