/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { insertFigma } from "./figma";

describe("figma", () => {
    describe("insertFigma", () => {
        let doc: Document;
        beforeEach(() => {
            doc = createLocalDocument();
        });

        it("insert an iframe", () => {
            const a = doc.createElement("a");
            a.href = "https://figma-url.example.com";
            a.innerText = "figma";
            doc.body.appendChild(a);

            insertFigma(a);

            expect(doc.body).toMatchSnapshot();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
