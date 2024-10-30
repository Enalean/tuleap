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
import { buildCustomSchema } from "../../../custom_schema";
import * as removeBlockquoteModule from "./remove-blockquote";
import { toggleBlockQuote } from "./quote-command";
import * as hasPreviousNodeTypeModule from "./has-previous-node-type";
import * as addBlockquoteModule from "./add-blockquote";

const custom_schema = buildCustomSchema();

describe("QuoteCommand", () => {
    describe("toggleBlockQuote", () => {
        describe("When the current selection is not in a blockquote", () => {
            it("should add blockquote", () => {
                const addBlockquoteMock = vi
                    .spyOn(addBlockquoteModule, "addBlockQuote")
                    .mockReturnValue();
                vi.spyOn(hasPreviousNodeTypeModule, "hasPreviousNodeType").mockReturnValue(false);

                const state = {
                    schema: custom_schema,
                } as unknown as EditorState;

                toggleBlockQuote(state, vi.fn());

                expect(addBlockquoteMock).toHaveBeenCalledOnce();
            });
        });
        describe("When the current selection is in a blockquote", () => {
            it("should remove blockquote", () => {
                const removeBlockquoteMock = vi
                    .spyOn(removeBlockquoteModule, "removeBlockQuote")
                    .mockReturnValue();
                vi.spyOn(hasPreviousNodeTypeModule, "hasPreviousNodeType").mockReturnValue(true);

                const state = {
                    schema: custom_schema,
                } as unknown as EditorState;

                toggleBlockQuote(state, vi.fn());

                expect(removeBlockquoteMock).toHaveBeenCalledOnce();
            });
        });
    });
});
