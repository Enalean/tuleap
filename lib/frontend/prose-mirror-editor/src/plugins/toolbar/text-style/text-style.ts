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

import { type EditorState, Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { getHeadingDropdownClass } from "./text-style-dropdown-menu";
import type { GetText } from "@tuleap/gettext";

const PROSE_MIRROR_PARAGRAPH_NODE = "paragraph";
const PROSE_MIRROR_HEADING_NODE = "heading";

function getSelectedNodeType(state: EditorState): {
    node_type: string | null;
    node_level: string | null;
} {
    const { from, to } = state.selection;

    let node_type: string | null = null;
    let node_level: string | null = null;
    state.doc.nodesBetween(from, to, (node) => {
        node_type = node.type.name;
        node_level = node.attrs?.level;
    });
    return { node_type, node_level };
}

export function updateHeadingDropdownTitle(
    state: EditorState,
    dropdown_element: Element | null,
    gettext_provider: GetText,
    dependencies: {
        get_selected_node: typeof getSelectedNodeType;
    },
): void {
    if (dropdown_element) {
        const selected_node = dependencies.get_selected_node(state);
        if (selected_node.node_type === PROSE_MIRROR_PARAGRAPH_NODE) {
            dropdown_element.textContent = gettext_provider.gettext("paragraph");
        } else if (selected_node.node_type === PROSE_MIRROR_HEADING_NODE) {
            dropdown_element.textContent =
                gettext_provider.gettext(`title`) + ` ${selected_node.node_level}`;
        }
    }
}

export function initPluginTextStyle(editor_id: string, gettext_provider: GetText): Plugin {
    return new Plugin({
        view(): { update: (view: EditorView) => void } {
            return {
                update(view: EditorView): void {
                    const text_style_dropdown = document
                        .getElementsByClassName(getHeadingDropdownClass(editor_id))
                        .item(0);
                    updateHeadingDropdownTitle(view.state, text_style_dropdown, gettext_provider, {
                        get_selected_node: getSelectedNodeType,
                    });
                },
            };
        },
    });
}
