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

import { icons, MenuItem } from "prosemirror-menu";
import type { MenuElement, MenuItemSpec } from "prosemirror-menu";
import type { EditorState, Command } from "prosemirror-state";
import type { Schema, MarkType } from "prosemirror-model";
import { toggleMark } from "prosemirror-commands";
import type { GetText } from "@tuleap/gettext";
import { linkItem, unlinkItem } from "./links/link-menu-item-builder";
import { wrapListItem } from "./list/list-menu-item-builder";
import { getTextStyleDropdownMenu } from "./text-style";
import { imageItem } from "./image/image-menu-item-builder";
import { getSubscriptMenuItem, getSuperscriptMenuItem } from "./text-transformations";
import { getQuoteMenuItem } from "./quote";

export function cmdItem(cmd: Command, options: MenuItemSpec): MenuItem {
    const passed_options: MenuItemSpec = options;
    passed_options.label = options.label ? options.label : "";
    if (options.run) {
        passed_options.run = options.run;
    } else {
        passed_options.run = cmd;
    }

    if (!options.enable && !options.select) {
        passed_options[options.enable ? "enable" : "select"] = (state): boolean => cmd(state);
    }

    return new MenuItem(passed_options);
}

export function markActive(state: EditorState, type: MarkType): boolean {
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
    wrapBulletList?: MenuItem;
    wrapOrderedList?: MenuItem;
    toggleCode?: MenuItem;
    toggleLink?: MenuItem;
    fullMenu: MenuElement[][];
};

export function buildMenuItems(
    schema: Schema,
    gettext_provider: GetText,
    editor_id: string,
): MenuItemResult {
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
                getQuoteMenuItem(gettext_provider),
                getSubscriptMenuItem(schema, gettext_provider),
                getSuperscriptMenuItem(schema, gettext_provider),
                wrapListItem(
                    schema.nodes.bullet_list,
                    {
                        title: gettext_provider.gettext("Wrap in bullet list `Shift+Ctrl+8`"),
                        icon: icons.bulletList,
                    },
                    schema.nodes.ordered_list,
                    "fa-list",
                ),
                wrapListItem(
                    schema.nodes.ordered_list,
                    {
                        title: gettext_provider.gettext("Wrap in ordered list `Shift+Ctrl+9`"),
                        icon: icons.orderedList,
                    },
                    schema.nodes.bullet_list,
                    "fa-list-ol",
                ),
                markItem(schema.marks.code, {
                    title: gettext_provider.gettext("Toggle code Ctrl+`"),
                    icon: icons.code,
                }),
                getTextStyleDropdownMenu(schema, editor_id, gettext_provider),
                linkItem(schema.marks.link, editor_id, gettext_provider),
                unlinkItem(schema.marks.link, editor_id),

                imageItem(schema.nodes.image, editor_id, gettext_provider),
            ],
        ],
    };
}
