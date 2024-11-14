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

import type { EditorState, Transaction } from "prosemirror-state";
import { Plugin, PluginKey, TextSelection } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";

// This value represents the number of positions between the end of the title content and the beginning of the description content.

// +1 for the position between nodes </artidoc-section-title> and <artidoc-section-description>
// +1 for the position between nodes <artidoc-section-description> and <p>
// +1 for the position between <p> and its contents
//
//      0      1     2   3
// title </ast> <asd> <p> description
const POSITIONS_BETWEEN_TITLE_AND_DESCRIPTION = 3;

const getEndOfTitlePosition = (state: EditorState): number => {
    let artidoc_section_title_size: number = 0;
    state.doc.descendants((node) => {
        if (node.type === state.schema.nodes.artidoc_section_title) {
            artidoc_section_title_size = node.nodeSize;
            return false;
        }
    });
    return artidoc_section_title_size;
};

const isCursorAtStartOfDescription = (
    current_position: number,
    end_of_title_position: number,
): boolean => {
    const start_of_description = end_of_title_position + POSITIONS_BETWEEN_TITLE_AND_DESCRIPTION;
    return current_position === start_of_description;
};

const moveCursorToSectionDescription = (
    event: KeyboardEvent,
    state: EditorState,
    dispatch: (tr: Transaction) => void,
    toolbar_bus: ToolbarBus,
): void => {
    const tr = state.tr;
    tr.setSelection(
        TextSelection.create(
            state.doc,
            getEndOfTitlePosition(state) + POSITIONS_BETWEEN_TITLE_AND_DESCRIPTION,
        ),
    );

    toolbar_bus.enableToolbar();
    event.preventDefault();

    dispatch(tr);
};

const moveCursorToSectionTitle = (
    event: KeyboardEvent,
    state: EditorState,
    dispatch: (tr: Transaction) => void,
    toolbar_bus: ToolbarBus,
    end_of_title_position: number,
): void => {
    const tr = state.tr;
    tr.setSelection(TextSelection.create(state.doc, end_of_title_position));

    toolbar_bus.disableToolbar();
    event.preventDefault();

    dispatch(tr);
};

function handleEnterEvent(
    view: EditorView,
    event: KeyboardEvent,
    toolbar_bus: ToolbarBus,
): boolean {
    const { state, dispatch } = view;
    const { $from } = state.selection;
    const current_node = $from.parent;

    if (current_node.type !== state.schema.nodes.artidoc_section_title) {
        return false; // To allow new lines at the beginning of the description after hit enter
    }

    moveCursorToSectionDescription(event, state, dispatch, toolbar_bus);

    return true;
}

function handleBackspaceEvent(
    view: EditorView,
    event: KeyboardEvent,
    toolbar_bus: ToolbarBus,
): boolean {
    const { state, dispatch } = view;
    const { $from } = state.selection;
    const current_node = $from.parent;

    const end_of_title_position = getEndOfTitlePosition(state);

    if (current_node.type === state.schema.nodes.artidoc_section_title) {
        return true; // To allow deleting the title after hit backspace
    }

    if (!isCursorAtStartOfDescription($from.pos, end_of_title_position)) {
        return true; // To allow deleting the description after hit backspace
    }

    moveCursorToSectionTitle(event, state, dispatch, toolbar_bus, end_of_title_position);

    return true;
}

export const JumpToSectionNodePlugin = (toolbar_bus: ToolbarBus): Plugin =>
    new Plugin({
        key: new PluginKey("jump-to-section-node"),
        props: {
            handleDOMEvents: {
                keydown: (view, event): boolean => {
                    const { state } = view;
                    const { empty } = state.selection;

                    if (!empty || event.shiftKey || event.ctrlKey) {
                        return false;
                    }

                    if (event.key === "Enter") {
                        return handleEnterEvent(view, event, toolbar_bus);
                    }

                    if (event.key === "Backspace") {
                        return handleBackspaceEvent(view, event, toolbar_bus);
                    }
                    return false;
                },
            },
        },
    });

export const setupMonoEditorPlugins = (toolbar_bus: ToolbarBus): Plugin[] => [
    JumpToSectionNodePlugin(toolbar_bus),
];
