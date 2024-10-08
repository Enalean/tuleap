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

import type { MenuElement } from "prosemirror-menu";
import type { Schema } from "prosemirror-model";
import type { GetText } from "@tuleap/gettext";
import { getTextStyleDropdownMenu } from "./text-style";

type MenuItemResult = {
    fullMenu: MenuElement[][];
};

export function buildMenuItems(
    schema: Schema,
    gettext_provider: GetText,
    editor_id: string,
): MenuItemResult {
    return {
        fullMenu: [[getTextStyleDropdownMenu(schema, editor_id, gettext_provider)]],
    };
}
