
![Adeliom](https://adeliom.com/public/uploads/2017/09/Adeliom_logo.png)
[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=agence-adeliom_easy-media-bundle)](https://sonarcloud.io/dashboard?id=agence-adeliom_easy-media-bundle)

# Easy Media Bundle

A VueJS media-manager for Easyadmin.

## Features

- Image editor
- Multi
    + Upload
    + Move/Copy
    + Delete
- Upload by either
    + Using the upload panel
    + Drag&Drop anywhere
    + Click&Hold on an empty area **"items container"**
    + From a url **"images only"**
- Preview files before uploading
- Toggle between `random/original` names for uploaded files
- Bulk selection
- Bookmark visited directories for quicker navigation
- Change item/s visibility
- Update the page url on navigation
- Show audio files info **"artist, album, year, etc.."**
- Dynamically hide files / folders
- Restrict access to path
- Download selected "including bulk selection"
- Directly copy selected file link
- Use the manager
    + from modal
    + with any wysiwyg editor
- Auto scroll to selected item using **"left, up, right, down, home, end"**
- Lock/Unlock item/s.
- Filter by
    + Folder
    + Image
    + Audio
    + Video
    + text/pdf
    + application/archive
    + Locked items
    + Selected items
- Sort by
    + Name
    + Size
    + Last modified
- Items count for
    + All
    + Selected
    + Search found
- File name sanitization for
    + Upload
    + Rename
    + New folder
- Disable/Enable buttons depend on the usage to avoid noise & keep the user focused
- [Shortcuts / Gestures](doc/shortcuts.md)
    + If no more **rows** available, pressing `down` will go to the last item in the list **"same as native file manager"**.
    + When viewing a `audio/video` file in the preview card, pressing `space` will **play/pause** the item instead of closing the modal.
    + Double click/tap
        + any file of type `audio/video` when sidebar is hidden, will open it in the preview card **"same as images"**.
        + any file of type `application/archive` will download it.
    + All the **left/right** gestures have their counterparts available as well.
    + Pressing `esc` while using the ***image editor*** wont close the modal but you can ***dbl click/tap*** the `modal background` to do so. **"to avoid accidentally canceling your changes"**.

> The info sidebar is only available on big screens **"> 1023px"**.<br>
> To stop interfering with other `keydown` events you can toggle the manager listener through `EventHub.fire('disable-global-keys', true/false)`.

## Installation

Install with composer

```bash
composer require agence-adeliom/easy-media-bundle
```

### Setup database

#### Using doctrine migrations

```bash
php bin/console doctrine:migration:diff
php bin/console doctrine:migration:migrate
```

#### Without

```bash
php bin/console doctrine:schema:update --force
```

## Documentation

### Manage medias in your Easyadmin dashboard

Go to your dashboard controller, example : `src/Controller/Admin/DashboardController.php`

```php
<?php

namespace App\Controller\Admin;

...
class DashboardController extends AbstractDashboardController
{
    ...
    
    // Add the custom form theme
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyMedia/form/easy-media.html.twig')
            ;
    }
    
    public function configureMenuItems(): iterable
    {
        ...
        yield MenuItem::linkToRoute('Medias', 'fa fa-picture-o', 'media.index');

        ...
```

### Integrate with FOS CKEditor

```yaml
#config/packages/fos_ck_editor.yaml
fos_ck_editor:
    configs:
        main_config:
            ...
            filebrowserBrowseRoute: media.browse
            filebrowserImageBrowseRoute: media.browse
            filebrowserImageBrowseRouteParameters:
                provider: 'image'
                restrict:
                    uploadTypes:
                        - 'image/*'
                    uploadSize: 5
```

### Integrate with LiipImagineBundle

```yaml
#config/packages/liip_imagine.yaml
liip_imagine:
  loaders:
    default:
      filesystem:
        data_root: '%kernel.project_dir%/public'
```

```php
{{ object.media|resolve_media|imagine_filter('filter_name') }}
```

### Field's usage

#### Usage

```php
use Adeliom\EasyMediaBundle\Admin\Field\EasyMediaField;
...
yield EasyMediaField::new('property', "label")
    // Apply restrictions by mime-types
    ->setFormTypeOption("restrictions_uploadTypes", ["image/*"])
    // Apply restrictions to upload size in MB
    ->setFormTypeOption("restrictions_uploadSize", 5)
    // Apply restrictions to path
    ->setFormTypeOption("restrictions_path", "users/" . $userID)
    // Hide fiels with extensions (null or array)
    ->setFormTypeOption("hideExt", ["svg"])
    // Hide folders (null or array)
    ->setFormTypeOption("hidePath", ['others', 'users/testing'])
    // Enable/Disable actions
    ->setFormTypeOption("editor", true)
    ->setFormTypeOption("upload", true)
    ->setFormTypeOption("bulk_selection", true)
    ->setFormTypeOption("move", true)
    ->setFormTypeOption("rename", true)
    ->setFormTypeOption("metas", true)
    ->setFormTypeOption("delete", true)
    ;
```

### Twig usage

```php
# Get media URL
{{ object.media|resolve_media }}

# Get media metadatas
{{ object.media|media_meta }}

# Get complete media informations
{{ object.media|media_infos }}

# Get test file type
# type_to_test: can be a mime_type or 
# image for any image type
# pdf for pdf files
# compressed for archives files
{{ file_is_type(object.media, type_to_test) }}

# Get mimetype icon (font-awesome)
{{ mime_icon("text/plain") }}
```

### Use the Doctrine type (optional)

It automatically converts the stored path into a File object

```yaml
# config/packages/doctrine.yaml
doctrine:
  dbal:
    ...
    types:
      easy_media_type: Adeliom\EasyMediaBundle\Types\EasyMediaType
```

In your entity

```php
class Article
{
    /**
     * @ORM\Column(type="easy_media_type", nullable=true)
     * @Assert\NotBlank()
     */
    private $file;
    
    ...
```

### Configurations

```yaml
# config/packages/easy_media.yaml
easy_media:
    storage:              '%kernel.project_dir%/public/upload'
    base_url:             /upload/
    
    # ignore any file starts with "."
    ignore_files:         '/^\..*/'
    
    # remove any file special chars except
    # dot .
    # dash -
    # underscore _
    # single quote ''
    # white space
    # parentheses ()
    # comma ,
    allowed_fileNames_chars: '\._\-\''\s\(\),'
    
    # remove any folder special chars except
    # dash -
    # underscore _
    # white space
    #
    # to add & nest folders in one go add '\/'
    # avoid using '#' as browser interpret it as an anchor
    allowed_folderNames_chars: _\-\s
    
    # disallow uploading files with the following mimetypes (https://www.iana.org/assignments/media-types/media-types.xhtml)
    unallowed_mimes:
        # Defaults:
        - php
        - java
    
    # disallow uploading files with the following extensions (https://en.wikipedia.org/wiki/List_of_filename_extensions)
    unallowed_ext:
        # Defaults:
        - php
        - jav
        - py

    extended_mimes:
        # any extra mime-types that doesnt have "image" in it
        image:                # Required
            # Default:
            - binary/octet-stream
        # any extra mime-types that doesnt have "compressed" in it
        archive:              # Required
            # Defaults:
            - application/x-tar
            - application/zip
    
    # display file last modification time as
    last_modified_format: Y-m-d
    
    # hide file extension in files list
    hide_files_ext:       true
    
    # in-order to get the folder items count & size
    # we need to recursively get all the files inside the folders
    # which could make the request take longer
    get_folder_info:      true
    
    # preview files base64 uploading
    preview_files_before_upload: true
    
    # loaded chunk amount "pagination"
    pagination_amount:    50

```


### Events

| type            | event-name                                         | description                                                                |
| --------------- | -------------------------------------------------- | -------------------------------------------------------------------------- |
| [JS][js]        |                                                    |                                                                            |
|                 | modal-show                                         | when modal is shown                                                        |
|                 | modal-hide                                         | when modal is hidden                                                       |
|                 | file_selected *(when inside modal)*       | get selected file url                                                      |
|                 | multi_file_selected *(when inside modal)* | get bulk selected files urls                                               |
|                 | folder_selected *(when inside modal)*     | get selected folder path                                                   |
| [Symfony][symfony] |                                                    |                                                                            |
|                 | em.file.uploaded($file_path, $mime_type, $options)   | get uploaded file storage path, mime type |
|                 | em.file.saved($file_path, $mime_type)       | get saved (edited/link) image full storage path, mime type                 |
|                 | em.file.deleted($file_path, $is_folder)              | get deleted file/folder storage path, if removed item is a folder          |
|                 | em.file.renamed($old_path, $new_path)                | get renamed file/folder "old & new" storage path                           |
|                 | em.file.moved($old_path, $new_path)                  | get moved file/folder "old & new" storage path                             |

[js]: https://github.com/gocanto/vuemit
[symfony]: https://symfony.com/doc/current/event_dispatcher.html

## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)


## Thanks to

[ctf0/Laravel-Media-Manager](https://github.com/ctf0/Laravel-Media-Manager)

