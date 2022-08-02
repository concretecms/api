## Installation

This will take a long time the first time as it clones the entire concrete5/concrete5 repository.
```
composer install
```

## .env

Copy `.env.dist` to `.env` and change `BASE_URL`.

## Generating docs

First parse all versions and files:

```bash
php vendor/bin/doctum.php parse --quiet config.php
```

Then render the documentation:

```bash 
php vendor/bin/doctum.php render --quiet config.php
```
