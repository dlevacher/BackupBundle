# ItsisBackupBundle

Provide a simple Symfony 2 Bundle to backup database via one command.

## Installing via Composer
```json
{
    "require": {
        "itsis/backup-bundle": "dev-master"
    }
}
```

## Using and Setting Up

### Kernel.php

```php
public function registerBundles() {
  $bundles = array(
    new Itsis\BackupBundle()
  );
}
```

To provide custom backup dir. Add a config options in your config.yml, like:

```yaml
jma_backup:
    dir: "%kernel.root_dir%/backup"
```

Then to access this setup call:

```php
$this->get('itsis_backup.dir');
```