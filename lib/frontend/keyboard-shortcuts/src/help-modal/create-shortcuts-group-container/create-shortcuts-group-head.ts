/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { ShortcutsGroup } from "../../type";

export function createShortcutsGroupHead(
    doc: Document,
    shortcuts_group: ShortcutsGroup,
): HTMLElement {
    const shortcuts_group_head = doc.createElement("div");
    shortcuts_group_head.classList.add("help-modal-shortcuts-group-head");

    const group_title = doc.createElement("h2");
    group_title.classList.add("tlp-modal-subtitle");
    group_title.append(shortcuts_group.title);
    shortcuts_group_head.append(group_title);

    if (shortcuts_group.details) {
        const group_details = doc.createElement("p");
        group_details.classList.add("help-modal-shortcuts-group-details");
        group_details.append(shortcuts_group.details);
        shortcuts_group_head.append(group_details);
    }

    return shortcuts_group_head;
}
