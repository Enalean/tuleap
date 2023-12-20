# Changelog

## 2.6.0

* ✨ Now promoted items can have subitems. This allows for example to display Backlog » Releases » Sprint.
* ✨ Now tools can have an info tooltip. A question mark icon will be displayed next to the tool name and the tooltip will display the information on hover.
* 🚸 Promoted items (and subitems) have a larger target click area. If users click on the highlighted row, they are redirected to the item (before they had to explicitly click on the label). This behavior is consistent with the one for tools.

## 2.5.0

* Collapse of the sidebar is no longer deprecated.
* When sidebar is collapsed, the services are no longer displayed.

## 2.4.0

* 🚸 In order to not clutter the sidebar when the number of linked projects is too big,
  a new configuration variable is added: `project.linked_projects.nb_max_projects_before_popover`.
  When the number of linked projects exceeds this limit, then they are no more displayed in the sidebar,
  only in the popover on click. Default is `5`.
* 🐛 Linked projects displayed in the popover are now clickable links
* 🐛 fix broken style for arrow on linked projects popover
* 🐛 fix broken links in README
* ⬆️ Bump vue: 3.2.37 -> 3.3.4

## 2.3.0

* Tools can have promoted items, they will be displayed as a sub list when the sidebar is expanded.
* Collapse of the sidebar is deprecated. A boolean `is_collapsible` is now part of the configuration to control the behavior.

## 2.2.4

* `process.env.NODE_ENV` is removed from the distributed files so the element can be used without a bundler

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
