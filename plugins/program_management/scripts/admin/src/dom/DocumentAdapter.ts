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

export class DocumentAdapter implements RetrieveElement {
    constructor(private readonly doc: Document) {}

    getInputById(id: string): HTMLInputElement {
        const element = this.doc.getElementById(id);
        if (!(element instanceof HTMLInputElement)) {
            throw new Error(`Could not find element with id #${id}`);
        }
        return element;
    }

    getSelectById(id: string): HTMLSelectElement {
        const element = this.doc.getElementById(id);
        if (!(element instanceof HTMLSelectElement)) {
            throw new Error(`Could not find element with id #${id}`);
        }
        return element;
    }

    getElementById(id: string): Element {
        const element = this.doc.getElementById(id);
        if (!element) {
            throw new Error(`Could not find element with id #${id}`);
        }
        return element;
    }

    getElementsByClassName(class_name: string): HTMLCollectionOf<Element> {
        return this.doc.getElementsByClassName(class_name);
    }
}
