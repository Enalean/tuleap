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
import type { EditorNode } from "../../../types/internal-types";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import { EditorNodeAtPositionFinder } from "./EditorNodeAtPositionFinder";

describe("EditorNodeAtPositionFinder", () => {
    it("Given a position, then it should return the corresponding EditorNode", () => {
        const position = 102;
        const node = createLocalDocument().createElement("a") as unknown as EditorNode;
        const nodeAt = vi.fn().mockReturnValue(node);

        const view = { doc: { nodeAt } } as unknown as EditorState;
        const finder = EditorNodeAtPositionFinder(view);
        const found_node = finder.findNodeAtPosition(position);

        expect(nodeAt).toHaveBeenCalledOnce();
        expect(nodeAt).toHaveBeenCalledWith(position);
        expect(found_node).toBe(node);
    });
});
