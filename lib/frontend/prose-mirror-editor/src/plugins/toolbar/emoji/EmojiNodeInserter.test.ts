/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { buildCustomSchema } from "../../../custom_schema";
import type { EditorState } from "prosemirror-state";
import { EmojiNodeInserter } from "./EmojiNodeInserter";

describe("EmojiNodeInserter", () => {
    it("Given emoji properties, it should dispatch a transaction replacing the selection with the emoji", () => {
        const transaction = {
            replaceSelectionWith: vi.fn(),
        };
        const state = {
            tr: transaction,
            schema: buildCustomSchema(),
        } as unknown as EditorState;

        const dispatch = vi.fn();
        const emoji = { emoji: "🐱" };

        EmojiNodeInserter(state, dispatch).insertEmoji(emoji);

        expect(transaction.replaceSelectionWith).toHaveBeenCalledOnce();
        const emoji_node_attributes = transaction.replaceSelectionWith.mock.calls[0][0];
        expect(emoji_node_attributes.text).toBe(emoji.emoji);

        expect(dispatch).toHaveBeenCalledOnce();
    });
});
