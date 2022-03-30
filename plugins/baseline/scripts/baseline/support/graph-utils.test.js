/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import GraphUtils from "./graph-utils";
import ArrayUtils from "./array-utils";

describe("GraphUtils:", () => {
    describe("findAll", () => {
        let first_level_node;
        let child_node_a;
        let child_node_b;

        beforeEach(() => {
            child_node_a = { id: 2, links: [] };
            child_node_b = { id: 3, links: [] };
            first_level_node = [{ id: 1, links: [child_node_a, child_node_b] }];
        });

        it("returns elements which match given predicate", () => {
            const nodes = GraphUtils.findAllNodes(first_level_node, "links", (obj) => obj.id <= 2);
            const nodes_ids = ArrayUtils.mapAttribute(nodes, "id");
            expect(nodes_ids).toEqual([1, 2]);
        });

        it("returns empty array when no element match with given predicate", () => {
            const nodes = GraphUtils.findAllNodes(first_level_node, "links", (obj) => obj.id <= 0);
            const nodes_ids = ArrayUtils.mapAttribute(nodes, "id");
            expect(nodes_ids).toEqual([]);
        });

        it("returns empty array when array is empty", () => {
            const nodes = GraphUtils.findAllNodes([], "links", (obj) => obj.id <= 10);
            const nodes_ids = ArrayUtils.mapAttribute(nodes, "id");
            expect(nodes_ids).toEqual([]);
        });
    });
});
