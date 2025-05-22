/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { buildItemPath } from "./build-parent-paths";
import type { Item, ItemReferencingWikiPageRepresentation } from "../../type";

describe("buildItemPath", () => {
    it("Build item parent path", () => {
        const item: ItemReferencingWikiPageRepresentation = {
            item_id: 10,
            item_name: "my item",
        };

        const parents = [
            {
                title: "folder A",
            } as Item,
            {
                title: "folder B",
            } as Item,
            {
                title: "folder C",
            } as Item,
            {
                title: "folder D",
            } as Item,
        ];

        const item_path = buildItemPath(item, parents);
        expect(item_path).toStrictEqual({
            path: "/folder A/folder B/folder C/folder D/my item",
            id: 10,
        });
    });
});
