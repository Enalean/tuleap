/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { getHTMLInputElementFromId, getHTMLSelectElementFromId } from "./HTML_element_extractor";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("HTML_element_extractor", () => {
    describe("getHTMLSelectElementFromId", () => {
        it("should throw error when element does not exist", () => {
            expect(() => getHTMLSelectElementFromId(createDocument(), "bad-id")).toThrow(
                "bad-id element does not exist",
            );
        });
        it("should throw error when element is not select element", () => {
            const doc = createDocument();
            const div = document.createElement("div");
            div.id = "id-selector";
            doc.body.appendChild(div);

            expect(() => getHTMLSelectElementFromId(doc, "id-selector")).toThrow(
                "id-selector element does not exist",
            );
        });
        it("whould return select element when it exists", () => {
            const doc = createDocument();
            const select = document.createElement("select");
            select.id = "id-selector";
            doc.body.appendChild(select);

            expect(getHTMLSelectElementFromId(doc, "id-selector")).toEqual(select);
        });
    });
    describe("getHTMLInputElementFromId", () => {
        it("should throw error when element does not exist", () => {
            expect(() => getHTMLInputElementFromId(createDocument(), "bad-id")).toThrow(
                "No bad-id input element",
            );
        });
        it("should throw error when element is not select element", () => {
            const doc = createDocument();
            const div = document.createElement("div");
            div.id = "id-input";
            doc.body.appendChild(div);

            expect(() => getHTMLInputElementFromId(doc, "id-input")).toThrow(
                "No id-input input element",
            );
        });
        it("whould return select element when it exists", () => {
            const doc = createDocument();
            const select = document.createElement("input");
            select.id = "id-input";
            doc.body.appendChild(select);

            expect(getHTMLInputElementFromId(doc, "id-input")).toEqual(select);
        });
    });
});
