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
import { custom_schema } from "../../custom_schema";
import type { EditorView } from "prosemirror-view";
import { closeAllCurrentMarks } from "./close-all-marks";
import * as closeMarkModule from "./close-mark";
import { toggleMark } from "prosemirror-commands";

describe("closeAllCurrentMarks", () => {
    describe("When the current mark is in the list of marks to close", () => {
        it("should close the subscript mark", () => {
            const closeMarkMock = vi.spyOn(closeMarkModule, "closeMark").mockReturnValue(false);
            const subscript_mark = custom_schema.marks.subscript.create();
            const view = {
                state: {
                    selection: {
                        $from: {
                            marks: vi.fn().mockReturnValue([subscript_mark]),
                        },
                    },
                },
            } as unknown as EditorView;

            closeAllCurrentMarks(view);
            expect(closeMarkMock).toHaveBeenCalledOnce();
            expect(closeMarkMock.mock.calls[0]).toEqual([subscript_mark.type, view, toggleMark]);
        });
        it("should close the superscript mark", () => {
            const closeMarkMock = vi.spyOn(closeMarkModule, "closeMark").mockReturnValue(false);
            const superscript_mark = custom_schema.marks.superscript.create();
            const view = {
                state: {
                    selection: {
                        $from: {
                            marks: vi.fn().mockReturnValue([superscript_mark]),
                        },
                    },
                },
            } as unknown as EditorView;

            closeAllCurrentMarks(view);
            expect(closeMarkMock).toHaveBeenCalledOnce();
            expect(closeMarkMock.mock.calls[0]).toEqual([superscript_mark.type, view, toggleMark]);
        });
    });
    describe("When the current mark is not in the list of marks to close", () => {
        it("should not close the mark", () => {
            const closeMarkMock = vi.spyOn(closeMarkModule, "closeMark").mockReturnValue(false);
            const view = {
                state: {
                    selection: {
                        $from: {
                            marks: vi.fn().mockReturnValue([custom_schema.marks.link.create()]),
                        },
                    },
                },
            } as unknown as EditorView;

            closeAllCurrentMarks(view);
            expect(closeMarkMock).not.toHaveBeenCalledOnce();
        });
    });
});
