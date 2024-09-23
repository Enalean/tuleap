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

import { MenuItem } from "prosemirror-menu";
import type { Schema } from "prosemirror-model";
import type { GetText } from "@tuleap/gettext";
import { toggleMark } from "prosemirror-commands";
import { createIcon } from "../helper/create-icon";
import type { CheckIsMArkActive } from "../helper/IsMarkActiveChecker";

export function getSubscriptMenuItem(
    schema: Schema,
    gettext_provider: GetText,
    check_is_mark_active: CheckIsMArkActive,
): MenuItem {
    return new MenuItem({
        title: gettext_provider.gettext("Apply subscript style on the selected text `Ctrl+,`"),
        label: gettext_provider.gettext(`Subscript`),
        active: (state) => check_is_mark_active.isMarkActive(state, schema.marks.subscript),
        run: toggleMark(schema.marks.subscript),
        icon: createIcon("fa-solid fa-subscript"),
    });
}

export function getSuperscriptMenuItem(
    schema: Schema,
    gettext_provider: GetText,
    check_is_mark_active: CheckIsMArkActive,
): MenuItem {
    return new MenuItem({
        title: gettext_provider.gettext("Apply superscript style on the selected text `Ctrl+.`"),
        label: gettext_provider.gettext(`Superscript`),
        active: (state) => check_is_mark_active.isMarkActive(state, schema.marks.superscript),
        run: toggleMark(schema.marks.superscript),
        icon: createIcon("fa-solid fa-superscript"),
    });
}
