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

import type { MenuItem, MenuItemSpec } from "prosemirror-menu";
import { toggleMark } from "prosemirror-commands";
import type { MarkType } from "prosemirror-model";
import type { CheckIsMArkActive } from "./IsMarkActiveChecker";
import type { BuildMenuItemWithCommand } from "./BuildMenuItemWithCommand";
export type BuildMenuItem = {
    buildMenuItem(
        markType: MarkType,
        options: Partial<MenuItemSpec>,
        check_is_mark_active: CheckIsMArkActive,
        menu_item_with_command_builder: BuildMenuItemWithCommand,
    ): MenuItem;
};
export const MenuItemBuilder = (): BuildMenuItem => ({
    buildMenuItem(
        mark_type: MarkType,
        options: Partial<MenuItemSpec>,
        check_is_mark_active: CheckIsMArkActive,
        menu_item_with_command_builder: BuildMenuItemWithCommand,
    ): MenuItem {
        const passed_options: MenuItemSpec = {
            ...options,
            active(state) {
                return check_is_mark_active.isMarkActive(state, mark_type);
            },
            run: toggleMark(mark_type),
        };

        return menu_item_with_command_builder.buildMenuItemWihCommand(
            toggleMark(mark_type),
            passed_options,
        );
    },
});
