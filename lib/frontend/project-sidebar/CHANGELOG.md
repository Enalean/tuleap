# Changelog

## 2.2.3

* Changes regarding the Tuleap icons have been re-introduced
* Release pipeline has been fixed (hopefully)

## 2.2.2

Revert updated Tuleap icons due to build/release pipeline issue.

## 2.2.1

No changes since 2.2.0. This version exists because the 2.0.0 version was tagged as 2.2.0.

## 2.2.0

* Update bundled library `@vueuse/core` to 8.7.3 to avoid errors when using Chrome
* Path of UMD script has been changed (`project-sidebar.umd.js` → `project-sidedar.umd.cjs`)
* Updated Tuleap icons

## 2.1.0

* Update to FontAwesome 6

## 2.0.1

* Shortcuts are not triggered anymore when the user is editing (this is an addition to the fix made in 2.0.0)

## 2.0.0

* The element has an explicit height and width and is considered a block element by default.
* Only display the project flags icon when some project flags are present.
* Style the tag when the custom element is not yet defined to limit <abbr title="flash of unstyled content">FOUC</abbr>.
Note this requires to explicitly load a stylesheet.
* Shortcuts are not triggered anymore when the user is editing content.

## 1.1.0

Make possible to remove the collapse button using the `no-collapse-button` attribute on the element.

## 1.0.2

Mention the element also needs a stylesheet with the Tuleap CSS variables.

## 1.0.1

Fix links in [README.md](./README.md) and in [package.json](./package.json), so they can be used on npmjs.com.

## 1.0.0

Initial release
