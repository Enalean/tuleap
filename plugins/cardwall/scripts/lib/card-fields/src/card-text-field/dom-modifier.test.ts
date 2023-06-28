/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { Classifier } from "../highlight/Classifier";
import { getHighlightedDOM, getHighlightedNodes } from "./dom-modifier";

describe(`dom-modifier`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`getHighlightedNodes()`, () => {
        it(`will return an array containing Span elements for each Highlight text
            and Text nodes for each Background text`, () => {
            const classifier = Classifier("sear");

            const result = getHighlightedNodes(doc, classifier, "Researcher");
            expect(result).toHaveLength(3);
            const [prefix, highlight, suffix] = result;
            expect(prefix).toBeInstanceOf(Text);
            expect(highlight).toBeInstanceOf(HTMLSpanElement);
            expect(suffix).toBeInstanceOf(Text);
        });
    });

    describe(`getHighlightedDOM()`, () => {
        let content: DocumentFragment;
        beforeEach(() => {
            content = doc.createDocumentFragment();
        });

        const runHighlight = (): DocumentFragment => {
            const classifier = Classifier("sear");
            return getHighlightedDOM(doc, content, classifier);
        };

        it(`will clone the given DocumentFragment and replace matching Text nodes
            by a combination of Span elements for Highlights and Text nodes for each
            Background text`, () => {
            content.append("Sear", "Researcher");

            const result = runHighlight();
            expect(result.childNodes).toHaveLength(4);
            expect(result).not.toBe(content);
        });

        it(`given an empty DocumentFragment, it will return an empty DocumentFragment`, () => {
            const result = runHighlight();
            expect(result.childNodes).toHaveLength(0);
            expect(result).not.toBe(content);
        });

        it(`will also iterate over children Element nodes (Anchor elements)
            and when those nodes have Text children, it will also replace them by
            a combination of Span elements and Text nodes`, () => {
            const first_anchor = doc.createElement("a");
            first_anchor.append("Seared");
            const second_anchor = doc.createElement("a");
            second_anchor.append("Researcher");
            content.append(first_anchor, second_anchor);

            const result = runHighlight();
            expect(result).not.toBe(content);
            const anchors = result.querySelectorAll("a");
            expect(anchors).toHaveLength(2);
            const [first, second] = anchors;
            expect(first.childNodes).toHaveLength(2);
            expect(second.childNodes).toHaveLength(3);
        });
    });
});
