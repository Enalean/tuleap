/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { EditorState } from "prosemirror-state";
import type { ToolbarView } from "./toolbar-bus";
import { custom_schema } from "../../../custom_schema";
import type { CheckIsMArkActive } from "./IsMarkActiveChecker";
import { isSelectionABlockQuote } from "../quote/is-selection-a-block-quote";
import type { BuildLinkState } from "../links/LinkStateBuilder";
import type { BuildImageState } from "../image/ImageStateBuilder";
import type { BuildListState } from "../list/ListStateBuilder";

export type ActivateToolbar = {
    activateToolbarItem(toolbar_view: ToolbarView, state: EditorState): void;
};

export const ToolbarActivator = (
    check_is_mark_active: CheckIsMArkActive,
    build_link_state: BuildLinkState,
    build_image_state: BuildImageState,
    build_list_state: BuildListState,
): ActivateToolbar => ({
    activateToolbarItem(toolbar_view: ToolbarView, state: EditorState): void {
        toolbar_view.activateBold(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.strong),
        );
        toolbar_view.activateItalic(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.em),
        );
        toolbar_view.activateCode(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.code),
        );
        toolbar_view.activateQuote(isSelectionABlockQuote(state));
        toolbar_view.activateSubscript(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.subscript),
        );
        toolbar_view.activateSuperscript(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.superscript),
        );
        toolbar_view.activateLink(build_link_state.build(state));
        toolbar_view.activateUnlink(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.link),
        );
        toolbar_view.activateImage(build_image_state.build(state.selection));

        toolbar_view.activateOrderedList(
            build_list_state.build(
                custom_schema.nodes.ordered_list,
                custom_schema.nodes.bullet_list,
            ),
        );

        toolbar_view.activateBulletList(
            build_list_state.build(
                custom_schema.nodes.bullet_list,
                custom_schema.nodes.ordered_list,
            ),
        );
    },
});
