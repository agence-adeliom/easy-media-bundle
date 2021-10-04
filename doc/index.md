# Setup database

## Using doctrine migrations

```bash
php bin/console doctrine:migration:diff
php bin/console doctrine:migration:migrate
```

## Without

```bash
php bin/console doctrine:schema:update --force
```

# Manage medias in your Easyadmin dashboard

Go to your dashboard controller, example : `src/Controller/Admin/DashboardController.php`

```php
<?php

namespace App\Controller\Admin;

...
class DashboardController extends AbstractDashboardController
{
    ...
    public function configureMenuItems(): iterable
    {
        ...
        yield MenuItem::linkToRoute('Medias', 'fa fa-picture-o', 'media.index');

        ...
```

# Integrate with FOS CKEditor

# Integrate with LiipImagineBundle

# Use the field

# Frontend usage
