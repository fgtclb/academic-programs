# Shortcut

## Usage

Using a shortcut page to display the contents of an educational
page is possible.
If the shortcut page should be able to overwrite certain fields
of the original educational program page, the `shortcut_overwrite`
checkbox needs to be enabled first. To do so, enable the field
using TSConfig. Enabling it for all subpages of a page can be
done by adding the following to the parent page's TSConfig via
the Backend.
```
[{Insert current page id here} in tree.rootLineIds]
  TCEFORM.pages {
    shortcut_overwrite.disabled = 0
  }
[END]
```
