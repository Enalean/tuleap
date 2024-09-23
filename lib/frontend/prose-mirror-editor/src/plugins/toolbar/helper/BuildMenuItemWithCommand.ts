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

import type { MenuItemSpec } from "prosemirror-menu";
import { MenuItem } from "prosemirror-menu";
import type { Command } from "prosemirror-state";

export type BuildMenuItemWithCommand = {
    buildMenuItemWihCommand(cmd: Command, options: MenuItemSpec): MenuItem;
};

export const MenuItemWithCommandBuilder = (): BuildMenuItemWithCommand => ({
    buildMenuItemWihCommand(cmd: Command, options: MenuItemSpec): MenuItem {
        const passed_options: MenuItemSpec = options;
        passed_options.label = options.label ? options.label : "";
        passed_options.run = options.run ? options.run : cmd;

        if (!options.enable && !options.select) {
            passed_options[options.enable ? "enable" : "select"] = (state): boolean => cmd(state);
        }

        return new MenuItem(passed_options);
    },
});
