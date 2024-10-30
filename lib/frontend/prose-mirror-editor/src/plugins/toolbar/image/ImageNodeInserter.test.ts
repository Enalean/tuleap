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

import { describe, it, expect, vi } from "vitest";
import type { EditorState } from "prosemirror-state";
import { ImageNodeInserter } from "./ImageNodeInserter";
import { buildCustomSchema } from "../../../custom_schema";

describe("ImageNodeInserter", () => {
    it("Given image properties, then it should dispatch a Transaction replacing the current selection with a new image node", () => {
        const transaction = {
            replaceSelectionWith: vi.fn(),
        };
        const state = {
            tr: transaction,
            schema: buildCustomSchema(),
        } as unknown as EditorState;

        const dispatch = vi.fn();
        const image = {
            src: "https://example.com",
            title: "A beautiful image making the text around unreadable",
        };

        ImageNodeInserter(state, dispatch).insertImage(image);

        expect(transaction.replaceSelectionWith).toHaveBeenCalledOnce();
        const image_node_attributes = transaction.replaceSelectionWith.mock.calls[0][0].attrs;

        expect(image_node_attributes.src).toBe(image.src);
        expect(image_node_attributes.title).toBe(image.title);
        expect(image_node_attributes.alt).toBe(image.title);

        expect(dispatch).toHaveBeenCalledOnce();
    });
});
