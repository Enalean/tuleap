/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { closeExistingModal } from "./close-existing-modal";
import { addToShortcutsModalTable, addHelpModalHeader } from "./add-to-help-modal";
import { Shortcut, ShortcutsGroup } from "./type";

export { Shortcut, ShortcutsGroup };

export function addShortcutsGroup(doc: Document, shortcut_group: ShortcutsGroup): void {
    addHelpModalHeader(doc, shortcut_group.title, shortcut_group.cells_id);
    shortcut_group.shortcuts.forEach((shortcut) => {
        hotkeys(shortcut.keyboard_inputs, (event) => {
            event.preventDefault();
            closeExistingModal(doc);
            shortcut.handle(event);
        });
        addToShortcutsModalTable(doc, shortcut_group.cells_id, shortcut);
    });
}
