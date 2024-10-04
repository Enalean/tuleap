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

import { icons } from "prosemirror-menu";
import type { MenuElement, MenuItem } from "prosemirror-menu";
import type { Schema } from "prosemirror-model";
import type { GetText } from "@tuleap/gettext";
import { linkItem, unlinkItem } from "./links/link-menu-item-builder";
import { wrapListItem } from "./list/list-menu-item-builder";
import { getTextStyleDropdownMenu } from "./text-style";
import { imageItem } from "./image/image-menu-item-builder";
import type { CheckIsMArkActive } from "./helper/IsMarkActiveChecker";
import { type BuildMenuItemWithCommand } from "./helper/BuildMenuItemWithCommand";

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
    check_is_mark_active: CheckIsMArkActive,
    MenuItemWithCommandBuilder: BuildMenuItemWithCommand,
): MenuItemResult {
    return {
        fullMenu: [
            [
                wrapListItem(
                    schema.nodes.bullet_list,
                    {
                        title: gettext_provider.gettext("Wrap in bullet list `Shift+Ctrl+8`"),
                        icon: icons.bulletList,
                    },
                    schema.nodes.ordered_list,
                    "fa-list",
                    MenuItemWithCommandBuilder,
                ),
                wrapListItem(
                    schema.nodes.ordered_list,
                    {
                        title: gettext_provider.gettext("Wrap in ordered list `Shift+Ctrl+9`"),
                        icon: icons.orderedList,
                    },
                    schema.nodes.bullet_list,
                    "fa-list-ol",
                    MenuItemWithCommandBuilder,
                ),
                getTextStyleDropdownMenu(schema, editor_id, gettext_provider),
                linkItem(schema.marks.link, editor_id, gettext_provider, check_is_mark_active),
                unlinkItem(schema.marks.link, editor_id, check_is_mark_active),

                imageItem(schema.nodes.image, editor_id, gettext_provider),
            ],
        ],
    };
}
