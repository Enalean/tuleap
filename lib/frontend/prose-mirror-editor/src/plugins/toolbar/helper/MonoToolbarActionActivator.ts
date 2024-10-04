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
export type ActivateToolbar = {
    activateToolbarItem(
        toolbar_view: ToolbarView,
        state: EditorState,
        check_is_mark_active: CheckIsMArkActive,
    ): void;
};
export const ToolbarActivator = (): ActivateToolbar => ({
    activateToolbarItem(
        toolbar_view: ToolbarView,
        state: EditorState,
        check_is_mark_active: CheckIsMArkActive,
    ): void {
        toolbar_view.activateBold(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.strong),
        );
        toolbar_view.activateEmbedded(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.em),
        );
        toolbar_view.activateCode(
            check_is_mark_active.isMarkActive(state, custom_schema.marks.code),
        );
        toolbar_view.activateQuote(isSelectionABlockQuote(state));
    },
});
