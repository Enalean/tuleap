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

import { DocumentAdapter } from "./DocumentAdapter";

describe(`DocumentAdapter`, () => {
    let doc: Document, adapter: DocumentAdapter;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        adapter = new DocumentAdapter(doc);
    });

    describe(`getInputById`, () => {
        it(`throws when given ID cannot be found`, () => {
            expect(() => adapter.getInputById("unknown")).toThrow();
        });

        it(`returns input element with given ID`, () => {
            const input = doc.createElement("input");
            input.id = "some_id";
            doc.body.append(input);

            expect(adapter.getInputById("some_id")).toBe(input);
        });
    });

    describe(`getNodeBySelector`, () => {
        it(`throws when node with given selector cannot be found`, () => {
            expect(() => adapter.getNodeBySelector("[data-unknown]")).toThrow();
        });

        it(`returns node matching given selector`, () => {
            const element = doc.createElement("div");
            element.dataset.selector = "";
            doc.body.append(element);

            expect(adapter.getNodeBySelector("[data-selector]")).toBe(element);
        });
    });

    describe(`getAllNodesBySelector`, () => {
        it(`returns all nodes matching given selector`, () => {
            const first_element = doc.createElement("div");
            first_element.dataset.selector = "";
            const second_element = doc.createElement("div");
            second_element.dataset.selector = "";
            doc.body.append(first_element, second_element);

            const results = adapter.getAllNodesBySelector("[data-selector]");
            expect(results).toContain(first_element);
            expect(results).toContain(second_element);
        });
    });
});
