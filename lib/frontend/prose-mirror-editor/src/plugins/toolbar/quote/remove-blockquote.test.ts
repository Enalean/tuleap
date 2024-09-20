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
import { custom_schema } from "../../../custom_schema";
import { removeBlockQuote } from "./remove-blockquote";
import type { NodeRange } from "prosemirror-model";

describe("removeBlockquote", () => {
    it("should remove blockquote", () => {
        const liftMock = vi.fn();
        vi.mock("prosemirror-transform", () => {
            return {
                liftTarget: vi.fn().mockReturnValue(1),
            };
        });
        const state = {
            schema: custom_schema,
            selection: {
                $from: {
                    blockRange: vi.fn().mockReturnValue({} as NodeRange),
                },
                $to: {},
            },
            tr: {
                lift: liftMock,
            },
        } as unknown as EditorState;
        removeBlockQuote(state, vi.fn());
        expect(liftMock).toHaveBeenCalledOnce();
    });
});
