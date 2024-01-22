/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import hotkeys from "hotkeys-js";

import { addShortcutsGroupToShortcutsModal } from "./help-modal/add-to-modal";
import { removeShortcutsGroupFromShortcutsModal } from "./help-modal/remove-from-modal";
import { isWildCardAndNotQuestionMark } from "./wildcards/handle-wildcards";
import { ignoreInputsEvenThoseInCustomElementsShadowDOM } from "./filter/hotkeys-filter";
import { HOTKEYS_SCOPE_NO_MODAL } from "./modals-events/handle-modals-events";
import { GLOBAL_SCOPE, PLUGIN_SCOPE } from "./type";
import type { Shortcut, ShortcutsGroup } from "./type";

export function addShortcutsGroup(doc: Document, shortcuts_group: ShortcutsGroup): void {
    shortcuts_group.shortcuts.forEach(createShortcut);
    addShortcutsGroupToShortcutsModal(doc, shortcuts_group, PLUGIN_SCOPE);
}

export function addGlobalShortcutsGroup(doc: Document, shortcuts_group: ShortcutsGroup): void {
    hotkeys.filter = ignoreInputsEvenThoseInCustomElementsShadowDOM;
    shortcuts_group.shortcuts.forEach(createShortcut);
    addShortcutsGroupToShortcutsModal(doc, shortcuts_group, GLOBAL_SCOPE);
}

function createShortcut(shortcut: Shortcut): void {
    const shortcut_handle = shortcut.handle;
    if (shortcut_handle === null) {
        return;
    }
    hotkeys(shortcut.keyboard_inputs, HOTKEYS_SCOPE_NO_MODAL, (event) => {
        if (isWildCardAndNotQuestionMark(shortcut, event)) {
            return;
        }

        const shortcut_handle_options = shortcut_handle(event);
        if (shortcut_handle_options?.preventDefault === false) {
            return;
        }
        event.preventDefault();
    });
}

export function removeShortcutsGroup(doc: Document, shortcuts_group: ShortcutsGroup): void {
    shortcuts_group.shortcuts.forEach((shortcut) => {
        hotkeys.unbind(shortcut.keyboard_inputs);
    });
    removeShortcutsGroupFromShortcutsModal(doc, shortcuts_group);
}
