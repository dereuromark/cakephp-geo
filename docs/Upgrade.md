# Upgrade Guide

## Coming from 2.x

We use a custom finder in 3.x, so any setDistanceAsVirtualField() call can now be simplified to
```php
$this->findDistance($query, $options);
```

## Changed behavior config

- `'before' => 'save'` is now `'on' => 'beforeSave'`.
