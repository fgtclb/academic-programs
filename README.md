# TYPO3 extension `academic_programs` (READ-ONLY)

|                 | URL                                                       |
|-----------------|-----------------------------------------------------------|
| **Repository:** | https://github.com/fgtclb/academic-programs               |
| **TER:**        | https://extensions.typo3.org/extension/academic_programs/ |

## Description

> @todo

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version     | TYPO3     | PHP                                          |
|--------|-------------|-----------|----------------------------------------------|
| main   | ^3, 3.x-dev | v13 + v14 | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | ^1, 1.x-dev | v11 + v12 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3)      |

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_programs/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer req \
  'fgtclb/category-types':'^2' \
  'fgtclb/academic-programs':'^2'
```

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/category-types':'2.*.*@dev' \
  'fgtclb/academic-programs':'2.*.*@dev'
```

## Upgrade

Upgrading between major versions can include breaking changes, which have to be
addressed manually where no automatic upgrade path is available. They are
documented per version in [Documentation/Changelog](./Documentation/Changelog).

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).

## Supported Versions

| Version | Supported          | End of Support |
|---------|--------------------|----------------|
| 3.x     | :white_check_mark: | 2029-06-30     |
| 2.x     | :white_check_mark: | 2027-12-31     |
| < 2.0   | :x:                | support ended  |

The newest line listed above is under development on the default branch and has not been released yet.

## Security

Found a vulnerability? Please report it privately via our
[security report form](https://security.fgtclb.com) — **do not** open a public issue.
See [SECURITY.md](SECURITY.md) for the full vulnerability disclosure policy,
including what to expect and our safe harbor statement.

## Simplified EU Declaration of Conformity (Annex VI)

> Hereby, web-vision GmbH declares that the product with digital elements
> type FGTCLB: University Educational Program is in compliance with Regulation (EU) 2024/2847.
>
> The full text of the EU declaration of conformity is available at the
> following internet address:
> https://security.fgtclb.com/conformity/fgtclb/academic-programs/2.4.0/en/

The full declarations are also included in this repository:
[English](EU-Declaration-of-Conformity.md) ·
[Deutsch](EU-Konformitaetserklaerung.md).

## License

This extension is released under the [GPL-2.0-or-later](LICENSE) license.
