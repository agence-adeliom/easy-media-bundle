
![Adeliom](https://adeliom.com/public/uploads/2017/09/Adeliom_logo.png)
[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=agence-adeliom_easy-media-bundle)](https://sonarcloud.io/dashboard?id=agence-adeliom_easy-media-bundle)

# Easy Media Bundle

A VueJS media-manager for Easyadmin.

## Features

- image editor
- multi
    + upload
    + move/copy
    + delete
- upload by either
    + using the upload panel
    + drag & drop anywhere
    + click & hold on an empty area **"items container"**
    + from a url **"images only"**
- preview files before uploading
- toggle between `random/original` names for uploaded files
- bulk selection
- bookmark visited directories for quicker navigation
- change item/s visibility
- update the page url on navigation
- show audio files info **"artist, album, year, etc.."**
- dynamically hide files / folders
- restrict access to path
- download selected "including bulk selection"
- directly copy selected file link
- use the manager
    + from modal
    + with any wysiwyg editor
- auto scroll to selected item using **"left, up, right, down, home, end"**
- lock/unlock item/s.
- filter by
    + folder
    + image
    + audio
    + video
    + text/pdf
    + application/archive
    + locked items
    + selected items
- sort by
    + name
    + size
    + last modified
- items count for
    + all
    + selected
    + search found
- file name sanitization for
    + upload
    + rename
    + new folder
- disable/enable buttons depend on the usage to avoid noise & keep the user focused
- shortcuts / gestures
    + if no more **rows** available, pressing `down` will go to the last item in the list **"same as native file manager"**.
    + when viewing a `audio/video` file in the preview card, pressing `space` will **play/pause** the item instead of closing the modal.
    + dbl click/tap
        + any file of type `audio/video` when sidebar is hidden, will open it in the preview card **"same as images"**.
        + any file of type `application/archive` will download it.
    + all the **left/right** gestures have their counterparts available as well.
    + pressing `esc` while using the ***image editor*** wont close the modal but you can ***dbl click/tap*** the `modal background` to do so. **"to avoid accidentally canceling your changes"**.

>\- the info sidebar is only available on big screens **"> 1023px"**.<br>
>\- to stop interfering with other `keydown` events you can toggle the manager listener through<br>
>`EventHub.fire('disable-global-keys', true/false)`.

<br>

| navigation           | button                                              | keyboard         | click / tap                  | touch                           |
| -------------------- | --------------------------------------------------- | ---------------- | ---------------------------- | ------------------------------- |
|                      | toggle upload panel *(toolbar)*                     | u                |                              |                                 |
|                      | refresh *(toolbar)*                                 | r                | hold *"clear cache"*         | pinch in *(items container)*    |
|                      | move/show movable list *(toolbar)*       | m / p            |                              |                                 |
|                      | image editor *(toolbar)*                            | e                |                              |                                 |
|                      | delete *(toolbar)*                                  | d / del          |                              |                                 |
|                      | lock/unlock *(toolbar)*                             | l                | hold *"anything but images"* |                                 |
|                      | change visibility *(toolbar)*                       | v                |                              |                                 |
|                      | toggle bulk selection *(toolbar)*                   | b                |                              |                                 |
|                      | (reset) bulk select all *(toolbar)*                 | a                |                              |                                 |
|                      | add to movable list *(shopping cart)*               | c / x            | *                            |                                 |
|                      | move/show movable list *(shopping cart)* |                  | **                           |                                 |
|                      | clear movable list *(shopping cart)*                |                  | hold                         |                                 |
|                      | toggle sidebar *(path bar)*                         | t                | *                            | swipe left/right *(sidebar)*    |
|                      | confirm *(modal)*                                   | enter            |                              |                                 |
|                      | toggle preview image/pdf/text *(item)*              | space            | **                           |                                 |
|                      | play/pause media *(item)*                           | space            | **                           |                                 |
|                      | hide (modal / upload-panel)                         | esc              |                              |                                 |
|                      | reset (search / bulk selection / filter / sorting)  | esc              |                              |                                 |
|                      | reset upload showPreview          | esc              |                              |                                 |
|                      | confirm upload showPreview          | enter            |                              |                                 |
|                      | &nbsp;                                              |                  |                              |                                 |
|                      | add to movable list *(item)*                        |                  |                              | swipe up                        |
|                      | delete *(item)*                                     |                  |                              | swipe down                      |
|                      | rename *(item)*                                     |                  |                              | swipe left                      |
|                      | image editor *(item)*                               |                  | hold                         |                                 |
|                      | current ++ selected *(item)*                        | shift + click    |                              |                                 |
|                      | current + selected *(item)*                         | alt/meta + click |                              |                                 |
|                      | create new folder                                   |                  | ** *(items container)*       |                                 |
|                      | &nbsp;                                              |                  |                              |                                 |
| go to next *"item"*  |                                                     | right            | *                            | swipe left  *(preview)*         |
| go to prev *"item"*  |                                                     | left             | *                            | swipe right *(preview)*         |
| go to first *"item"* |                                                     | home             |                              |                                 |
| go to last *"item"*  |                                                     | end              |                              |                                 |
| go to next *"row"*   |                                                     | down             |                              | swipe up *(preview)*            |
| go to prev *"row"*   |                                                     | up               |                              | swipe down *(preview)*          |
| open folder          |                                                     | enter            | **                           |                                 |
| go to prev *"dir"*   | folderName *(path bar)*                             | backspace        | *                            | swipe right *(items container)* |

<br>

## Events

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

## Installation

Install with composer

```bash
composer require agence-adeliom/easy-media-bundle
```

## Documentation

[Check it here](doc/index.md)

## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)


## Thanks to

[ctf0/Laravel-Media-Manager](https://github.com/ctf0/Laravel-Media-Manager)

