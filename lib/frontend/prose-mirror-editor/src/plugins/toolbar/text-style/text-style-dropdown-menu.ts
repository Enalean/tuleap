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

import { Dropdown } from "prosemirror-menu";
import { getHeadingMenuItems, getPlainTextMenuItem } from "./get-text-style-menu-items";
import type { Schema } from "prosemirror-model";
import { getHeadingCommand, getPlainTextCommand } from "./transform-text";
import type { GetText } from "@tuleap/gettext";
import { NB_HEADING } from "../index";

export function getHeadingDropdownClass(editor_id: string): string {
    return `heading_dropdown_${editor_id}`;
}
export function getTextStyleDropdownMenu(
    schema: Schema,
    editor_id: string,
    gettext_provider: GetText,
): Dropdown {
    return new Dropdown(
        [
            getPlainTextMenuItem(schema, getPlainTextCommand, gettext_provider),
            ...getHeadingMenuItems(schema, NB_HEADING, getHeadingCommand, gettext_provider),
        ],
        {
            label: gettext_provider.gettext("Styles"),
            class: getHeadingDropdownClass(editor_id),
        },
    );
}
