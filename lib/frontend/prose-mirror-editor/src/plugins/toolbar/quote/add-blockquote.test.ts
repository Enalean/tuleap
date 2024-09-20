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
import type { EditorState, Transaction } from "prosemirror-state";
import { custom_schema } from "../../../custom_schema";
import { addBlockQuote } from "./add-blockquote";

describe("addBlockquote", () => {
    it("should return true", () => {
        const wrapMock = vi.fn().mockReturnValue({} as Transaction);
        const state = {
            schema: custom_schema,
            selection: {
                $from: {},
                $to: {},
            },
            tr: {
                wrap: wrapMock,
            },
        } as unknown as EditorState;
        addBlockQuote(state, vi.fn());
        expect(wrapMock).toHaveBeenCalledOnce();
    });
});
