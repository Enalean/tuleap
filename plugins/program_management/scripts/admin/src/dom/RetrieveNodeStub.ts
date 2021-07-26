/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { RetrieveNode } from "./RetrieveNode";

export class RetrieveNodeStub implements RetrieveNode {
    private constructor(private readonly nodes: Node[]) {}

    getNodeBySelector(): Node {
        const node = this.nodes.shift();
        if (!node) {
            throw new Error("no nodes left to return in the stub");
        }
        return node;
    }

    getAllNodesBySelector(): Node[] {
        return this.nodes;
    }

    static withNodes(...nodes: Node[]): RetrieveNodeStub {
        return new RetrieveNodeStub(nodes);
    }
}
