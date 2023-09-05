/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { highlightFilterElements } from "./highlight-filter-template";
import type { UpdateFunctionWithMethods } from "hybrids";

const TEST_PHRASE = "Prefix Highlight Suffix";

describe("highlight-filter-template", () => {
    it("should highlight a matching phrase", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, "highlight")))
            .toMatchInlineSnapshot(`
            <div>
              
              
              Prefix 
              
              <span
                class="highlight"
              >
                Highlight
              </span>
              
               Suffix
            </div>
        `);
    });

    it("should highlight nothing if no match found", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, "no match")))
            .toMatchInlineSnapshot(`
            <div>
              
              
              Prefix Highlight Suffix
            </div>
        `);
    });

    it("should highlight nothing for the undefined filter", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, undefined)))
            .toMatchInlineSnapshot(`
            <div>
              Prefix Highlight Suffix
            </div>
        `);
    });

    it("should work correctly for number filters", () => {
        expect(getElementFromTemplate(highlightFilterElements("3210123", 0)))
            .toMatchInlineSnapshot(`
            <div>
              
              
              321
              
              <span
                class="highlight"
              >
                0
              </span>
              
              123
            </div>
        `);
    });

    it("should work when the text context is undefined", () => {
        expect(getElementFromTemplate(highlightFilterElements(undefined, "nothing")))
            .toMatchInlineSnapshot(`
            <div>
              
            </div>
        `);
    });

    it("should work correctly for number text", () => {
        expect(getElementFromTemplate(highlightFilterElements(3210123, "0")))
            .toMatchInlineSnapshot(`
            <div>
              
              
              321
              
              <span
                class="highlight"
              >
                0
              </span>
              
              123
            </div>
        `);
    });

    it("should highlight nothing if empty filter string passed", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, "")))
            .toMatchInlineSnapshot(`
            <div>
              Prefix Highlight Suffix
            </div>
        `);
    });

    it("should highlight more that one element", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, "gh")))
            .toMatchInlineSnapshot(`
            <div>
              
              
              Prefix Hi
              
              <span
                class="highlight"
              >
                gh
              </span>
              
              li
              
              <span
                class="highlight"
              >
                gh
              </span>
              
              t Suffix
            </div>
        `);
    });

    it("highlights each matching search terms", () => {
        expect(getElementFromTemplate(highlightFilterElements(TEST_PHRASE, "suffix highlight")))
            .toMatchInlineSnapshot(`
            <div>
              
              
              Prefix 
              
              <span
                class="highlight"
              >
                Highlight
              </span>
              
               
              
              <span
                class="highlight"
              >
                Suffix
              </span>
            </div>
        `);
    });

    it("should escape regexp search terms", () => {
        expect(
            getElementFromTemplate(
                highlightFilterElements("Prefix (Highlight) Suffix", "(Highlight)"),
            ),
        ).toMatchInlineSnapshot(`
            <div>
              
              
              Prefix 
              
              <span
                class="highlight"
              >
                (Highlight)
              </span>
              
               Suffix
            </div>
        `);
    });
});

function getElementFromTemplate(template: UpdateFunctionWithMethods<unknown>): HTMLElement {
    const element = document.createElement("div");
    template(document.body, element);

    return element;
}
