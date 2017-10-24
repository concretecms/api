## Installation

This will take a long time the first time as it clones the entire concrete5/concrete5 repository.
```
composer install
```

## Generating docs

First parse all versions and files:

```bash
php ./vendor/bin/sami.php parse sami_config.php
```

```bash 
php ./vendor/bin/sami.php render sami_config.php
```
