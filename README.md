# Laravel Versionable (Polymorphic User Fork)

**Fork of [overtrue/laravel-versionable](https://github.com/overtrue/laravel-versionable) with polymorphic user support**

This fork extends the original package to support polymorphic user relationships, allowing you to track versions created by different user model types (e.g., Admin, Associate, EndUser, etc.) in applications with multiple authentication guards.

It's a minimalist way to make your model support version history, and it's very simple to revert to the specified version.

## What's Different in This Fork?

This fork replaces the single-user foreign key relationship with a **polymorphic relationship**, enabling:

- Support for multiple user model types (Admin, Associate, EndUser, etc.)
- Automatic tracking of which user type created each version
- Compatibility with applications using multiple authentication guards
- No configuration needed - uses `auth()->user()` to automatically detect the authenticated user

### Key Changes:

1. **Migration**: Uses `morphs('user')` instead of `unsignedBigInteger('user_id')`
2. **Version Model**: Uses `morphTo()` for the user relationship instead of `belongsTo()`
3. **Configuration**: Removed `user_model` and `user_foreign_key` config options (no longer needed)
4. **Versionable Trait**: New `getVersionUser()` method returns the authenticated user model

## Requirement

1. PHP >= 8.1.0
2. laravel/framework >= 9.0

## Features

- Keep the specified number of versions.
- Whitelist and blacklist for versionable attributes.
- Easily revert to the specified version.
- Record only changed attributes.
- Easy to customize.

## Installing

Add the package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/visualbuilder/versionable"
        }
    ],
    "require": {
        "visualbuilder/laravel-versionable": "*"
    }
}
```

Then run:

```shell
composer require visualbuilder/laravel-versionable
```

First, publish the config file and migrations:

```bash
php artisan vendor:publish --provider="Visualbuilder\Versionable\ServiceProvider"
```

Then run this command to create a database migration:

```bash
php artisan migrate
```

## Usage

Add `Visualbuilder\Versionable\Versionable` trait to the model and set versionable attributes:

```php
use Visualbuilder\Versionable\Versionable;

class Post extends Model
{
    use Versionable;

    /**
     * Versionable attributes
     *
     * @var array
     */
    protected $versionable = ['title', 'content'];

    // Or use a blacklist
    //protected $dontVersionable = ['created_at', 'updated_at'];

    <...>
}
```

Versions will be created on the vensionable model saved.

```php
$post = Post::create(['title' => 'version1', 'content' => 'version1 content']);
$post->update(['title' => 'version2']);
```

### Get versions

```php
$post->versions; // all versions
$post->latestVersion; // latest version
// or
$post->lastVersion;

$post->versions->first(); // first version
// or
$post->firstVersion;

$post->versionAt('2022-10-06 12:00:00'); // get version from a specific time
// or
$post->versionAt(\Carbon\Carbon::create(2022, 10, 6, 12));
```

### Revert

Revert a model instance to the specified version:

```php
$post->getVersion(3)->revert();

// or

$post->revertToVersion(3);
```

#### Revert without saving

```php
$version = $post->versions()->first();

$post = $version->revertWithoutSaving();
```

### Remove versions

```php
// soft delete
$post->removeVersion($versionId = 1);
$post->removeVersions($versionIds = [1, 2, 3]);
$post->removeAllVersions();

// force delete
$post->forceRemoveVersion($versionId = 1);
$post->forceRemoveVersions($versionIds = [1, 2, 3]);
$post->forceRemoveAllVersions();
```

### Restore deleted version by id

```php
$post->restoreTrashedVersion($id);
```

### Temporarily disable versioning

```php
// create
Post::withoutVersion(function () use (&$post) {
    Post::create(['title' => 'version1', 'content' => 'version1 content']);
});

// update
Post::withoutVersion(function () use ($post) {
    $post->update(['title' => 'updated']);
});
```

### Custom Version Store strategy

You can set the following different version policies through property `protected $versionStrategy`:

- `Visualbuilder\Versionable\VersionStrategy::DIFF` - Version content will only contain changed attributes (default
  strategy).
- `Visualbuilder\Versionable\VersionStrategy::SNAPSHOT` - Version content will contain all versionable attribute
  values.

### Show diff between the two versions

```php
$diff = $post->getVersion(1)->diff($post->getVersion(2));
```

`$diff` is a object `Visualbuilder\Versionable\Diff`, it based
on [jfcherng/php-diff](https://github.com/jfcherng/php-diff).

You can render the diff to [many formats](https://github.com/jfcherng/php-diff#introduction), and all formats result
will be like follows:

```php
[
    $attribute1 => $diffOfAttribute1,
    $attribute2 => $diffOfAttribute2,
    ...
    $attributeN => $diffOfAttributeN,
]
```

#### toArray()

```php
$diff->toArray();
//
[
    "name" => [
        "old" => "John",
        "new" => "Doe",
    ],
    "age" => [
        "old" => 25,
        "new" => 26,
    ],
]
```

### Other formats

```php
toArray(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toText(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toJsonText(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toContextText(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toHtml(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toInlineHtml(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toJsonHtml(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
toSideBySideHtml(array $differOptions = [], array $renderOptions = [], bool $stripTags = false): array
```

> **Note**
>
> `$differOptions` and `$renderOptions` are optional, you can set them following the README
> of [jfcherng/php-diff](https://github.com/jfcherng/php-diff#example).
> `$stripTags` allows you to remove HTML tags from the Diff, helpful when you don't want to show tags.

### Using custom version model

You can define `$versionModel` in a model, that used this trait to change the model(table) for versions

> **Note**
>
> Model MUST extend class `\Visualbuilder\Versionable\Version`;

```php
<?php

class PostVersion extends \Visualbuilder\Versionable\Version
{
    //
}
```

Update the model attribute `$versionModel`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Visualbuilder\Versionable\Versionable;

class Post extends Model
{
    use Versionable;

    public string $versionModel = PostVersion::class;
}
```

## Integrations

- [mansoorkhan96/filament-versionable](https://github.com/mansoorkhan96/filament-versionable) Effortlessly manage revisions of your Eloquent models in Filament.

## Credits

This package is a fork of [overtrue/laravel-versionable](https://github.com/overtrue/laravel-versionable).

All credit for the original implementation goes to [@overtrue](https://github.com/overtrue). This fork only adds polymorphic user support.

## License

MIT
