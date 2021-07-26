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

import type { RetrieveElement } from "./RetrieveElement";

export class RetrieveElementStub implements RetrieveElement {
    private constructor(private readonly elements: HTMLElement[]) {}

    getInputById(): HTMLInputElement {
        if (this.elements.length > 0) {
            const element = this.elements.shift();
            if (!(element instanceof HTMLInputElement)) {
                throw new Error(
                    "Expected the stub to be prepared with an HTMLInputElement but it was not"
                );
            }
            return element;
        }
        throw new Error("No elements left to return in the stub");
    }

    static withElements(...elements: HTMLElement[]): RetrieveElementStub {
        return new RetrieveElementStub(elements);
    }
}
