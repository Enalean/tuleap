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

import { describe, expect, it } from "vitest";
import { TextSelection } from "prosemirror-state";
import { JumpToSectionNodePlugin } from "./jump-to-section-node";
import {
    initStateWithPlugins,
    initViewWithState,
    SOMEWHERE_IN_THE_DESCRIPTION_POSITION,
    SOMEWHERE_IN_THE_TITLE_POSITION,
    START_OF_DESCRIPTION_POSITION,
    END_OF_TITLE_POSITION,
} from "./test-mono-editor-helper";

describe("jump-to-section-node", () => {
    const state = initStateWithPlugins([JumpToSectionNodePlugin()]);
    const view = initViewWithState(state);

    const backspace_event = new KeyboardEvent("keydown", { key: "Backspace" });
    const enter_event = new KeyboardEvent("keydown", { key: "Enter" });

    const setCursorPosition = (position: number): void => {
        view.dispatch(view.state.tr.setSelection(TextSelection.create(view.state.doc, position)));
    };

    it(
        "should moves the cursor to the beginning of the description, " +
            "when Enter is pressed anywhere in the title",
        () => {
            setCursorPosition(SOMEWHERE_IN_THE_TITLE_POSITION);
            view.dom.dispatchEvent(enter_event);
            expect(view.state.selection.from).toBe(START_OF_DESCRIPTION_POSITION);
        },
    );

    it(
        "should moves the cursor to the end of the title, " +
            "when Backspace is pressed at the beginning of the description",
        () => {
            setCursorPosition(START_OF_DESCRIPTION_POSITION);
            view.dom.dispatchEvent(backspace_event);
            expect(view.state.selection.from).toBe(END_OF_TITLE_POSITION);
        },
    );

    it(
        "should not moves the cursor to the end of the title, " +
            "when Backspace is pressed elsewhere than at the beginning of the description",
        () => {
            setCursorPosition(SOMEWHERE_IN_THE_DESCRIPTION_POSITION);
            view.dom.dispatchEvent(backspace_event);
            expect(view.state.selection.from).not.toBe(END_OF_TITLE_POSITION);
        },
    );
});
