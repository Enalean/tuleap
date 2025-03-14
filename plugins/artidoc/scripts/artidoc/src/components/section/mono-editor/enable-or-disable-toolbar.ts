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

import type { PluginView } from "prosemirror-state";
import { Plugin, PluginKey } from "prosemirror-state";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import type { EditorView } from "prosemirror-view";
import type { ResolvedPos, NodeType } from "prosemirror-model";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";

const isCurrentPositionInsideAnAncestorNodeWithType = (
    position: ResolvedPos,
    ancestor_type: NodeType,
): boolean => {
    for (let depth = position.depth; depth > 0; depth--) {
        if (position.node(depth).type === ancestor_type) {
            return true;
        }
    }
    return false;
};

export const EnableOrDisableToolbarPlugin = (
    toolbar_bus: ToolbarBus,
    headings_button_state: HeadingsButtonState,
    section: ReactiveStoredArtidocSection,
): Plugin => {
    const enableOrDisableToolbarButtons = (view: EditorView): void => {
        const { selection, schema } = view.state;
        const is_cursor_in_description = isCurrentPositionInsideAnAncestorNodeWithType(
            selection.$from,
            schema.nodes.artidoc_section_description,
        );

        if (is_cursor_in_description) {
            toolbar_bus.enableToolbar();
            headings_button_state.deactivateButton();
            return;
        }

        toolbar_bus.disableToolbar();
        headings_button_state.activateButtonForSection(section);
    };

    return new Plugin({
        key: new PluginKey("enable-or-disable-toolbar"),
        props: {
            handleDOMEvents: {
                focus: (view): void => {
                    enableOrDisableToolbarButtons(view);
                },
            },
        },
        view(): PluginView {
            return {
                update: (view): void => {
                    if (!view.hasFocus()) {
                        toolbar_bus.disableToolbar();
                        headings_button_state.deactivateButton();
                        return;
                    }

                    enableOrDisableToolbarButtons(view);
                },
            };
        },
    });
};
