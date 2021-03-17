#@tuleap/keyboard-shortcuts

This lib provides the `addShortcutsGroup()` and `addGlobalShortcutsGroup()` functions which take in a `ShortcutsGroup` to:
- create shortcuts using `hotkeys-js` library,
- add it to the shortcuts help modal.

The lib also provide the `removeShortcutsGroup()` function which takes in a `ShortcutsGroup` to:
- unbind shortcuts,
- remove it from the shortcuts help modal.

## Usage
```typescript
import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import { addShortcutsGroup, addGlobalShortcutsGroup, removeShortcutsGroup } from "@tuleap/keyboard-shortcuts";

const shortcut_example: Shortcut = {
    keyboard_inputs: "up",
    displayed_inputs?: "↑", //Will be displayed in the shortcuts modal instead of the real keyboard input.
    description: "Shortcut description",
    handle: () => { shortcutHandleFunction() },
}

const shortcuts_group_example: ShortcutsGroup = {
    title: "Shortcuts group title",
    details?: "Group description, explanation",
    shortcuts: [ shortcut_example ],
};

addShortcutsGroup(
    document,
    shortcuts_group_example,
);

addGlobalShortcutsGroup(
    document,
    shortcuts_group_example,
);

removeShortcutsGroup(
    document,
    shortcuts_group_example,
);
```

## Dependencies to Tuleap
The lib needs and uses:
- the shortcut help modal, querying both `#help-modal-shortcuts` and `[data-shortcuts-modal-body]`,
- classes set in Tuleap (`tlp-modal-*`, `tlp-table*`, `help-modal-shortcuts-*`),
- a template set in `tuleap/src/template/common/shortcuts.mustache`,
- modal events `tlp-modal-shown` and `tlp-modal-hidden`.
