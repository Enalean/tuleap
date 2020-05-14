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

import insertEmbedsEverywhere from "./insertEmbedsEverywhere";
import insertEmbed from "./insertEmbed";
jest.mock("./insertEmbed");

describe("insertEmbedsEverywhere", () => {
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
        jest.clearAllMocks();
    });

    it(`Given there is more than one matching element,
        Then it insert embeds for each link.`, () => {
        const a1 = doc.createElement("a");
        a1.id = "a1";

        const p1 = doc.createElement("p");
        p1.appendChild(a1);

        const a2 = doc.createElement("a");
        a2.id = "a2";

        const p2 = doc.createElement("p");
        p2.appendChild(a2);

        doc.body.appendChild(p1);
        doc.body.appendChild(p2);

        insertEmbedsEverywhere(doc.body, "p");

        expect(insertEmbed).toHaveBeenNthCalledWith(1, a1);
        expect(insertEmbed).toHaveBeenNthCalledWith(2, a2);
    });

    it(`Given there is a matching element,
        When it contains many anchor elements,
        Then it insert embeds for each link,
        And it does so in reverse order to match the reading order.`, () => {
        const a1 = doc.createElement("a");
        a1.id = "a1";

        const a2 = doc.createElement("a");
        a2.id = "a2";

        const p = doc.createElement("p");
        p.appendChild(a1);
        p.appendChild(a2);

        doc.body.appendChild(p);

        insertEmbedsEverywhere(doc.body, "p");

        expect(insertEmbed).toHaveBeenNthCalledWith(1, a2);
        expect(insertEmbed).toHaveBeenNthCalledWith(2, a1);
    });

    it(`Given there is no matching element,
        Then it does not insert embeds.`, () => {
        const a1 = doc.createElement("a");
        a1.id = "a1";

        const div = doc.createElement("div");
        div.appendChild(a1);

        doc.body.appendChild(div);

        insertEmbedsEverywhere(doc.body, "p");

        expect(insertEmbed).not.toHaveBeenCalled();
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}
