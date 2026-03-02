[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# attribute-reader

A clean API for working with PHP attributes

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/attribute-reader
```

## Usage

```php
use Cline\AttributeReader\Attributes;

#[Route('/users')]
class UserController {}

$route = Attributes::get(UserController::class, Route::class);

Attributes::has(UserController::class, Route::class); // true
```

Detailed docs:

- [DOCS.md](DOCS.md)

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://git.cline.sh/faustbrian/attribute-reader/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/attribute-reader.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/attribute-reader.svg

[link-tests]: https://git.cline.sh/faustbrian/attribute-reader/actions
[link-packagist]: https://packagist.org/packages/cline/attribute-reader
[link-downloads]: https://packagist.org/packages/cline/attribute-reader
[link-security]: https://git.cline.sh/faustbrian/attribute-reader/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors
