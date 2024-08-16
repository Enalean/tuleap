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

import { keymap } from "prosemirror-keymap";
import { baseKeymap } from "prosemirror-commands";
import type { Plugin } from "prosemirror-state";
import { menuBar } from "prosemirror-menu";
import { buildMenuItems } from "./menu";
import { buildKeymap } from "./keymap";
import { custom_schema } from "../../custom_schema";
import { buildInputRules } from "./input-rules";
import type { GetText } from "@tuleap/gettext";

export { buildMenuItems, buildKeymap };

export function setupToolbar(gettext_provider: GetText): Plugin[] {
    const plugins = [
        keymap(buildKeymap(custom_schema)),
        keymap(baseKeymap),
        buildInputRules(custom_schema),
    ];

    plugins.push(
        menuBar({
            content: buildMenuItems(custom_schema, gettext_provider).fullMenu,
        }),
    );

    return plugins;
}
