/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { ShortcutsGroup, Scope } from "../type";
import { GLOBAL_SCOPE, PLUGIN_SCOPE } from "../type";

import { createShortcutsGroupHead } from "./create-shortcuts-group-container/create-shortcuts-group-head";
import { createShortcutsGroupTable } from "./create-shortcuts-group-container/create-shortcuts-group-table";
import { getGlobalShortcutsSection, getSpecificShortcutsSection } from "./get-shortcuts-section";

export function addShortcutsGroupToShortcutsModal(
    doc: Document,
    shortcuts_group: ShortcutsGroup,
    scope: Scope = PLUGIN_SCOPE
): void {
    const help_template = doc.querySelector("[data-shortcuts-help-header-template]");
    if (!(help_template instanceof HTMLElement)) {
        return;
    }

    const shortcuts_group_container = createShortcutsGroupContainer(doc, shortcuts_group);
    const shortcuts_modal_section =
        scope === GLOBAL_SCOPE ? getGlobalShortcutsSection(doc) : getSpecificShortcutsSection(doc);
    shortcuts_modal_section.append(shortcuts_group_container);
}

export function createShortcutsGroupContainer(
    doc: Document,
    shortcuts_group: ShortcutsGroup
): HTMLElement {
    const shortcuts_group_head = createShortcutsGroupHead(doc, shortcuts_group);
    const shortcuts_group_table = createShortcutsGroupTable(doc, shortcuts_group);

    const shortcuts_group_container = doc.createElement("div");
    shortcuts_group_container.dataset.shortcutsGroup = shortcuts_group.title;
    shortcuts_group_container.append(shortcuts_group_head, shortcuts_group_table);

    return shortcuts_group_container;
}
