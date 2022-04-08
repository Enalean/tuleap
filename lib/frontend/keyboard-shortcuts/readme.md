#@tuleap/keyboard-shortcuts

This lib provides the `addShortcutsGroup()` and `addGlobalShortcutsGroup()` functions which take in a `ShortcutsGroup` to:
- create shortcuts using `hotkeys-js` library,
- add it to the shortcuts help modal.

The lib also provide the `removeShortcutsGroup()` function which takes in a `ShortcutsGroup` to:
- unbind shortcuts,
- remove it from the shortcuts help modal.

The `Shortcut` `handle` property takes a function.
This function may use a `KeyboardEvent` parameter, and must return `void` or a `ShortcutHandleOptions` object.
After running the `handle` function, the default browser behaviour will be prevented by default.
If the `preventDefault` property of `ShortcutHandleOptions` is set to false, it will not be prevented.

## Usage
```typescript
import type { Shortcut, ShortcutsGroup, ShortcutHandleOptions } from "@tuleap/keyboard-shortcuts";
import { addShortcutsGroup, addGlobalShortcutsGroup, removeShortcutsGroup } from "@tuleap/keyboard-shortcuts";

function firstShortcutHandleFunction(): void {
    doStuff();
    return; // Default browser behaviour will be prevented.
}

const shortcut_first_example: Shortcut = {
    keyboard_inputs: "f",
    description: "Shortcut description",
    handle: () => { firstShortcutHandleFunction() },
}

function secondShortcutHandleFunction(event: KeyboardEvent): ShortcutHandleOptions {
    doStuff(event);
    return { preventDefault: false };
}

const shortcut_second_example: Shortcut = {
    keyboard_inputs: "up",
    displayed_inputs?: "↑", //Will be displayed in the shortcuts modal instead of the real keyboard input.
    description: "Shortcut description",
    handle: (event) => { secondShortcutHandleFunction(event) },
}

const shortcuts_group_example: ShortcutsGroup = {
    title: "Shortcuts group title",
    details?: "Group description, explanation",
    shortcuts: [ shortcut_first_example, shortcut_second_example ],
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
