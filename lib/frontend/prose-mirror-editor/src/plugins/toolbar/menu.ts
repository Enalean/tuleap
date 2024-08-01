/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { MenuElement, MenuItemSpec } from "prosemirror-menu";
import { icons, MenuItem } from "prosemirror-menu";
import type { EditorState, Command } from "prosemirror-state";
import type { Schema, MarkType } from "prosemirror-model";
import { toggleMark } from "prosemirror-commands";
import { gettext_provider } from "../../use-editor";

function cmdItem(cmd: Command, options: MenuItemSpec): MenuItem {
    const passed_options: MenuItemSpec = options;
    passed_options.label = options.label ? options.label : "";
    passed_options.run = cmd;

    if (!options.enable && !options.select) {
        passed_options[options.enable ? "enable" : "select"] = (state): boolean => cmd(state);
    }

    return new MenuItem(passed_options);
}

function markActive(state: EditorState, type: MarkType): boolean {
    const { from, $from, to, empty } = state.selection;
    if (empty) {
        return Boolean(type.isInSet(state.storedMarks || $from.marks()));
    }
    return state.doc.rangeHasMark(from, to, type);
}

function markItem(markType: MarkType, options: Partial<MenuItemSpec>): MenuItem {
    const passed_options: MenuItemSpec = {
        ...options,
        active(state) {
            return markActive(state, markType);
        },
        run: toggleMark(markType),
    };

    return cmdItem(toggleMark(markType), passed_options);
}

type MenuItemResult = {
    toggleStrong?: MenuItem;
    toggleEm?: MenuItem;
    fullMenu: MenuElement[][];
};

export function buildMenuItems(schema: Schema): MenuItemResult {
    return {
        fullMenu: [
            [
                markItem(schema.marks.strong, {
                    title: gettext_provider.gettext("Toggle strong style `Ctrl+b`"),
                    icon: icons.strong,
                }),
                markItem(schema.marks.em, {
                    title: gettext_provider.gettext("Toggle embedded style `Ctrl+i`"),
                    icon: icons.em,
                }),
            ],
        ],
    };
}
