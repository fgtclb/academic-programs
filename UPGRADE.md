# Upgrade 2.0

## Switch to `EXT:category_types` 2.0

Category type based handling has been streamlined and centralized within the `EXT:category_types` extension
and the known implementation based on deprecated TYPO3 Enumeration has been replaced with a modern PHP API
provided by the `EXT:category_type` extension.

Extension specific category types are now grouped and are now defined by newly introduced yaml file format,
following a concrete convention to look and auto-register these files.

`EXT:academic_programs` now ships a default set of program related category types, which can be found
in [Configuration/CategoryTypes.yaml](./Configuration/CategoryTypes.yaml).

`EXT:academic_programs` related category types can be extended by any other TYPO3 extension providing a
`Configuration/CategoryTypes.yaml` file containing category-types using the group-identifier `programs`.

`Configuration/CategoryTypes.yaml` format uses following syntax:

```yaml
types:
  - identifier: <unique_identifier>
    title: '<type-translation-lable-using-full-LLL-syntax'
    group: programs
    icon: '<icon-file-using-EXT-syntax-needs-to-be-an-as-svg>'
```

> [!IMPORTANT]
> TYPO3 Enumeration based classes has been removed from the extension codebase
> and is considerable breaking, allowed to be done for a major version upgrade.
> Please adopt accordingly to the new handling.
