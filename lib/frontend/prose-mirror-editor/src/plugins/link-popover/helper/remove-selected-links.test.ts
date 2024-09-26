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

import { describe, expect, it, vi } from "vitest";
import type { EditorState } from "prosemirror-state";
import { removeSelectedLinks } from "./remove-selected-links";
import * as selectionModule from "./get-selection-that-wraps-all-selected-links";
import { custom_schema } from "../../../custom_schema";

describe("remove links", () => {
    describe("removeSelectedLinks", () => {
        it("should remove link marks", () => {
            vi.spyOn(selectionModule, "getSelectionThatWrapsAllSelectedLinks").mockReturnValue({
                start: 1,
                end: 10,
            });
            const dispatch_mock = vi.fn();
            const remove_mark_mock = vi.fn();
            const state = {
                schema: custom_schema,
                selection: {
                    from: 1,
                    to: 10,
                },
                tr: {
                    removeMark: remove_mark_mock,
                },
            } as unknown as EditorState;

            removeSelectedLinks(state, dispatch_mock);

            expect(remove_mark_mock).toHaveBeenCalledOnce();
            expect(remove_mark_mock).toHaveBeenCalledWith(1, 10, state.schema.marks.link);
            expect(dispatch_mock).toHaveBeenCalledOnce();
        });
    });
});
