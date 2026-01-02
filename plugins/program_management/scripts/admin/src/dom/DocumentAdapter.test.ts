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

import { beforeEach, describe, expect, it } from "vitest";
import { DocumentAdapter } from "./DocumentAdapter";
import type { RetrieveElement } from "./RetrieveElement";

describe(`DocumentAdapter`, () => {
    let doc: Document, adapter: RetrieveElement;
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

    describe(`getSelectById`, () => {
        it(`throws when given ID cannot be found`, () => {
            expect(() => adapter.getSelectById("unknown")).toThrow();
        });

        it(`returns select element with given ID`, () => {
            const select = doc.createElement("select");
            select.id = "some_id";
            doc.body.append(select);

            expect(adapter.getSelectById("some_id")).toBe(select);
        });
    });

    describe(`getElementById`, () => {
        it(`throws when given ID cannot be found`, () => {
            expect(() => adapter.getElementById("unknown")).toThrow();
        });

        it(`returns element with given ID`, () => {
            const svg = doc.createElement("svg");
            svg.id = "illustration";
            doc.body.append(svg);

            expect(adapter.getElementById("illustration")).toBe(svg);
        });
    });
});
