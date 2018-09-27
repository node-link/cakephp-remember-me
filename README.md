# RememberMe plugin for CakePHP

CakePHP plugin that performs Remember-me authentication with the new cookie algorithm of version 3.5 or later.

## Requirements

* CakePHP 3.5 or later
* PHP 5.6 or later

## Installation

### 1. Install plugin

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```bash
composer require node-link/cakephp-remember-me
```

### 2. Load plugin

Please load the plugin manually as follows:

```php
<?php
// In src/Application.php. Requires at least 3.6.0
use Cake\Http\BaseApplication;

class Application extends BaseApplication
{
    public function bootstrap()
    {
        parent::bootstrap();

        // Load the plugin
        $this->addPlugin('NodeLink/RememberMe');
    }
}
```

Prior to 3.6.0, you should use `Plugin::load()`:

```php
<?php
// In config/bootstrap.php

use Cake\Core\Plugin;

Plugin::load('NodeLink/RememberMe', ['bootstrap' => true]);
```

Or, use `bin/cake` to load the plugin as follows:

```bash
bin/cake plugin load -b NodeLink/RememberMe
```

### 3. Set up `AuthComponent`

In the `AppController.php` of your application, set up `AuthComponent`.

```php
<?php
namespace App\Controller;
use Cake\Controller\Controller;

class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Auth', [
            'authenticate' => [
                'Form' => [
                    'userModel' => 'Users',
                    'fields' => ['username' => 'username', 'password' => 'password'],
                ],
                'NodeLink/RememberMe.Cookie' => [
                    'userModel' => 'Users',  // Please set the same as 'Form'.
                    'fields' => ['token' => 'remember_token'],  // Specify the column where you want to save the token for Remember-me authentication.
                ],
            ],
        ]);

        // ...
    }
}
```

### 4. Updating database and models

Please update database and models as necessary.

```sql
ALTER TABLE `users` ADD `remember_token` VARCHAR(64) NULL DEFAULT NULL;
```

```php
<?php
namespace App\Model\Entity;
use Cake\ORM\Entity;

class User extends Entity
{
    protected $_hidden = [
        'password',
        'remember_token',  // Add
    ];
}
```

### 5. Edit login template

Add the following to your login template:

```php
<?= $this->Form->control('remember_me', ['type' => 'checkbox', 'label' => __('Remember me')]); ?>
Or
<?= $this->Form->checkbox('remember_me'); ?>
<?= $this->Form->label('remember_me', __('Remember me')); ?>
```

## Configuration

Edit `config/.env` or `config/app.php`.

The full default configuration is as follows:

```php
<?php
return [
    'Security' => [
        'cookieKey' => env('SECURITY_COOKIE_KEY', env('SECURITY_SALT', '__SALT__')),
    ],
    'RememberMe' => [
        'field' => 'remember_me',
        'cookie' => [
            'name' => 'remember_me',
            'expires' => '+1 year',
            'path' => '',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true,
        ],
    ],
];
```

It is recommended to set random string to `SECURITY_COOKIE_KEY` of `config/.env`, or `'Security.cookieKey'` of `config/app.php`.

## Reporting Issues

If you have a problem with the RememberMe plugin, please send a pull request or open an issue on [GitHub](https://github.com/node-link/cakephp-remember-me/issues).

Also, I would appreciate it if you contribute to updating `README.md` file.
