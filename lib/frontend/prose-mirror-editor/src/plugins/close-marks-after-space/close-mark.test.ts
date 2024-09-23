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

import { closeMark } from "./close-mark";
import { describe, expect, it, vi } from "vitest";
import type { MarkType } from "prosemirror-model";
import { custom_schema } from "../../custom_schema";
import type { EditorView } from "prosemirror-view";

describe("closeMark", () => {
    describe("When the cursor is in the end of the mark", () => {
        it("should close the mark", () => {
            const mark: MarkType = custom_schema.marks.subscript;
            const toggleMarkMock = vi.fn().mockReturnValue(() => true);
            const view = {
                dispatch: vi.fn(),
                state: {
                    selection: {
                        $from: {
                            pos: 1,
                            end: vi.fn().mockReturnValue(1),
                            marks: vi
                                .fn()
                                .mockReturnValue([custom_schema.marks.subscript.create()]),
                        },
                    },
                    storedMarks: [custom_schema.marks.subscript.create()],
                },
            } as unknown as EditorView;

            closeMark(mark, view, toggleMarkMock);
            expect(toggleMarkMock).toHaveBeenCalledOnce();
        });
    });
});
