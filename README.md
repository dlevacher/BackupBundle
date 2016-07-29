# DlevacherBackupBundle

Provide a simple Symfony 2 Bundle to backup database via one command.

## Installing via Composer
```json
{
    "require": {
        "dlevacher/backup-bundle": "dev-master"
    }
}
```

## Using and Setting Up

### Kernel.php

```php
public function registerBundles() {
  $bundles = array(
    new Dlevacher\BackupBundle()
  );
}
```

To provide custom backup dir. Add a config options in your config.yml, like:

```yaml
dlevacher_backup:
    dir: "%kernel.root_dir%/backup"
```

Then to access this setup call:

```php
$this->get('dlevacher_backup.dir');
```