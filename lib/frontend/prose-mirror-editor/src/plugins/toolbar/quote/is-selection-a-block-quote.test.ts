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
import { isSelectionABlockQuote } from "./is-selection-a-block-quote";
import * as hasPreviousNodeTypeModule from "./has-previous-node-type";
import { buildCustomSchema } from "../../../custom_schema";

const custom_schema = buildCustomSchema();

describe("isSelectionABlockQuote", () => {
    describe("When the selection is a block quote", () => {
        it("should return true", () => {
            vi.spyOn(hasPreviousNodeTypeModule, "hasPreviousNodeType").mockReturnValue(true);
            const state = {
                schema: custom_schema,
            } as unknown as EditorState;
            expect(isSelectionABlockQuote(state)).toBe(true);
        });
    });
    describe("When the selection is not a block quote", () => {
        it("should return false", () => {
            vi.spyOn(hasPreviousNodeTypeModule, "hasPreviousNodeType").mockReturnValue(false);
            const state = {
                schema: custom_schema,
            } as unknown as EditorState;
            expect(isSelectionABlockQuote(state)).toBe(false);
        });
    });
});
