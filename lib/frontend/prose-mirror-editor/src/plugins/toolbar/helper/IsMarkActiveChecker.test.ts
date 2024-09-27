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

import { describe, expect, it, vi } from "vitest";
import { schema } from "prosemirror-schema-basic";
import type { Mark, MarkType, ResolvedPos } from "prosemirror-model";
import { IsMarkActiveChecker } from "./IsMarkActiveChecker";
import type { EditorState } from "prosemirror-state";

describe("MarkActivator", () => {
    it("returns isInSet value when there is no selection", () => {
        const mark_type = {
            isInSet: vi.fn().mockReturnValue(true),
        } as unknown as MarkType;
        const state = {
            selection: {
                from: 1,
                $from: {} as ResolvedPos,
                to: 2,
                empty: true,
            },
            storedMarks: [{} as Mark],
        } as unknown as EditorState;

        expect(IsMarkActiveChecker().isMarkActive(state, mark_type)).toBe(true);
    });

    it("returns rangeHasMarkValue when there is a selection", () => {
        const mark_type = schema.marks.strong;
        const state = {
            selection: {
                from: 1,
                $from: {} as ResolvedPos,
                to: 2,
                empty: false,
            },
            storedMarks: [mark_type],
            doc: {
                rangeHasMark: vi.fn().mockReturnValue(true),
            },
        } as unknown as EditorState;

        expect(IsMarkActiveChecker().isMarkActive(state, mark_type)).toBe(true);
    });
});
