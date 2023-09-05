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

import type { RetrieveContainedNode } from "./RetrieveContainedNode";
import type { RetrieveElement } from "./RetrieveElement";

export class ElementAdapter implements RetrieveContainedNode {
    private constructor(private readonly element: Element) {}

    static fromId(retriever: RetrieveElement, id: string): ElementAdapter {
        return new ElementAdapter(retriever.getElementById(id));
    }

    getNodeBySelector(selector: string): Node {
        const node = this.element.querySelector(selector);
        if (!node) {
            throw new Error(
                `Could not find node by selector ${selector} in container with id ${this.element.id}`,
            );
        }
        return node;
    }

    getAllNodesBySelector(selector: string): Node[] {
        return Array.from(this.element.querySelectorAll(selector));
    }
}
