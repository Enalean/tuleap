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
import type { EditorNode } from "../../../types/internal-types";
import { ParentNodeRetriever } from "./ParentNodeRetriever";

describe("ParentNodeRetriever", () => {
    it("Retrieves the parent node", () => {
        const position = 3;
        const parent = {
            textContent: "parent text",
        } as unknown as EditorNode;
        const tree: EditorNode = {
            resolve: vi.fn().mockReturnValue({
                parent,
            }),
        } as unknown as EditorNode;

        expect(ParentNodeRetriever().retrieveParentNode(tree, position)).toBe(parent);
    });
});
