/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Command } from "prosemirror-state";
import type { ToolbarBus } from "../helper/toolbar-bus";

export type BuildEmojiMenuCommand = {
    build(): Command;
};

export const OpenEmojiMenuCommandBuilder = (toolbar_bus: ToolbarBus): BuildEmojiMenuCommand => ({
    build: (): Command => (): boolean => {
        toolbar_bus.toggleMenu("emoji");
        return true;
    },
});
