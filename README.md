## Configure Code Quality Toolbox

Our Code Quality Toolbox contains a number of tools to improve code style and overall code quality.

### First time set up (when setting up a project)

1. Add these lines in the `scripts.post-autoload-dump` setting in the `composer.json` file:

    ```
    "@php -r \"copy('vendor/webwhales/code-quality-tools/pint.json', 'pint.json');\"",
    "@php -r \"copy('vendor/webwhales/code-quality-tools/rector.dist.php', 'rector.php');\"",
    ```

    Resulting in the following lines (the rest of the `post-autoload-dump` commands may be different):

    ```
    "scripts": {
        ...
        "post-autoload-dump": [
            "@php -r \"copy('vendor/webwhales/code-quality-tools/pint.json', 'pint.json');\"",
            "@php -r \"copy('vendor/webwhales/code-quality-tools/rector.dist.php', 'rector.php');\"",
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
       ...
    }
    ```

2. Install the code quality tools using the following commands:

    ```shell
    composer config repositories.code-quality-tools github https://github.com/WebWhales/Code-Quality-Tools
    composer config --no-plugins allow-plugins.phpstan/extension-installer true
    composer remove --dev laravel/pint --no-scripts -q
    composer require --dev webwhales/code-quality-tools:dev-master
    composer config scripts.check.0 "composer pint"
    composer config scripts.check.1 "composer phpstan"
    composer config scripts.ide.0 "@php artisan ide-helper:generate"
    composer config scripts.ide.1 "@php artisan ide-helper:meta"
    composer config scripts.ide.2 "@php artisan ide-helper:models -M"
    composer config scripts.phpstan.0 "phpstan --memory-limit=-1"
    composer config scripts.pint.0 "pint"
    composer config scripts.pint-dirty.0 "pint --dirty"
    composer config scripts.rector.0 "rector"
    composer config scripts.rector-dry-run.0 "rector --dry-run"
    rm -f phpstan.neon
    ```

### Laravel Pint (code style fixer)

Laravel Pint is used to automatically reformat the PHP code style. You can run Laravel Pint using the
following command from your Docker web container:

```shell
composer pint
```

You can also configure a file watcher in your IDE to automatically run the formatter on save.

1. Open the PhpStorm settings and go to Tools > File Watchers
2. Click the + button and select "<custom>"
3. Fill in the following settings:
    * Name: `Laravel Pint`
    * File type: `PHP`
    * Scope: `Project Files`
    * Program: `wsl`
    * Arguments: `docker-compose exec -T web composer pint $/FileRelativePath$`
    * Output paths to refresh: `$/FileRelativePath$`
    * Working directory: `$ProjectFileDir$`
    * Advanced Options > Auto-save edited files to trigger the watcher: `not checked`
    * Advanced Options > Trigger the watcher on external changes: `not checked`
    * Advanced Options > Trigger the watcher regardless of syntax errors: `not checked`
    * Advanced Options > Show console: `On error`

### Larastan (static analysis)

Larastan is a static analysis tool built on top of PHPStan to help analyze code and prevent bugs. You can
run Larastan using the following command from your Docker web container:

```shell
composer phpstan
```

### Rector (code refactoring)

Rector is a tool that can automatically refactor your code to improve its quality and maintainability. You can run Rector using the following command from your Docker web container:

```shell
composer rector
```

To perform a dry run (without making any changes), you can use the following command:

```shell
composer rector-dry-run
```

#### local customization options

You can customize the behavior of Rector by creating a `rector.custom.php` or `rector.local.php` file in the root of 
your project. The `rector.custom.php` is supposed to be committed to the repository, while the `rector.local.php` is 
for local changes that should not be committed. These files should return a callable that takes a 
`Rector\Configuration\RectorConfigBuilder` as an argument. For example:

```php
use Rector\Configuration\RectorConfigBuilder;

return fn(RectorConfigBuilder $builder) => $builder->withParallel(1, 2);
```
