/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { NodeType } from "prosemirror-model";
import type { MenuItem, MenuItemSpec } from "prosemirror-menu";
import { isSelectionAList, isSelectionAListWithType } from "./is-list-checker";
import { lift } from "prosemirror-commands";
import { wrapInList } from "prosemirror-schema-list";
import { v4 as uuidv4 } from "uuid";
import type { BuildMenuItemWithCommand } from "../helper/BuildMenuItemWithCommand";

export function wrapListItem(
    node_type: NodeType,
    options: Partial<MenuItemSpec>,
    can_not_be_converted_to: NodeType,
    fa_icon: string,
    menu_item_with_command_builder: BuildMenuItemWithCommand,
): MenuItem {
    const icon_id = `${uuidv4()}-icon-list`;
    const passed_options: MenuItemSpec = {
        ...options,
        select(): boolean {
            return true;
        },
        render(): HTMLElement {
            const icon = document.createElement("i");
            icon.classList.add("fa-solid", fa_icon, "ProseMirror-icon");
            icon.id = icon_id;
            return icon;
        },
        active(state) {
            const icon = document.getElementById(icon_id);
            if (!icon) {
                return false;
            }
            if (isSelectionAListWithType(state, can_not_be_converted_to)) {
                icon.setAttribute("disabled", "");
                icon.classList.add("prose-mirror-icon-disabled");
            } else {
                icon.removeAttribute("disabled");
                icon.classList.remove("prose-mirror-icon-disabled");
            }

            return isSelectionAListWithType(state, node_type);
        },
        run(state, dispatch) {
            if (isSelectionAList(state, node_type)) {
                return lift(state, dispatch);
            }

            const wrapFunction = wrapInList(node_type);
            return wrapFunction(state, dispatch);
        },
    };
    return menu_item_with_command_builder.buildMenuItemWihCommand(
        wrapInList(node_type, {}),
        passed_options,
    );
}
