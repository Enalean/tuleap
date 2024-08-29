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

import type { Schema } from "prosemirror-model";
import { blockTypeItem, type MenuItem } from "prosemirror-menu";
import type { EditorState, Transaction } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import type { GetText } from "@tuleap/gettext";

export type MenuItemSpecRunCommand = (
    state: EditorState,
    dispatch: (tr: Transaction) => void,
    view: EditorView,
    event: Event,
) => void;

export function getHeadingMenuItems(
    schema: Schema,
    number_of_heading: number,
    run_command: (level: number) => MenuItemSpecRunCommand,
    gettext_provider: GetText,
): MenuItem[] {
    return Array.from({ length: number_of_heading }, (_, index) => {
        const level = index + 1;
        return blockTypeItem(schema.nodes.heading, {
            title: gettext_provider.gettext(`Change to heading`) + ` ${level}`,
            label: gettext_provider.gettext(`Title`) + ` ${level}`,
            run: run_command(level),
            attrs: { level },
        });
    });
}

export function getPlainTextMenuItem(
    schema: Schema,
    run_command: () => MenuItemSpecRunCommand,
    gettext_provider: GetText,
): MenuItem {
    return blockTypeItem(schema.nodes.paragraph, {
        title: gettext_provider.gettext("Change to plain text"),
        label: gettext_provider.gettext(`Normal text`),
        run: run_command(),
    });
}
