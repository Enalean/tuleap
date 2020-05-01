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

import insertWidget from "./insertWidget";

describe(`insertEmbed`, () => {
    let doc: Document, widget: HTMLElement;
    beforeEach(() => {
        doc = createLocalDocument();
        widget = doc.createElement("iframe");
    });

    it("Includes embed after the paragraph", () => {
        doc.body.innerHTML = '<p>See mockup <a href="https://example.com"></a>.</p>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed after the div", () => {
        doc.body.innerHTML = '<div>See mockup <a href="https://example.com"></a>.</div>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed after the h1", () => {
        doc.body.innerHTML = '<h1>See mockup <a href="https://example.com"></a>.</h1><p>Plop</p>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed after the paragraph even if it is deep in the hierarchy", () => {
        doc.body.innerHTML =
            '<div>See mockup <b><i><span><a href="https://example.com"></a></span></i></b>.</div>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);
        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed inside the li", () => {
        doc.body.innerHTML = '<ul><li>See mockup <a href="https://example.com"></a>.</li></ul>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed inside the td", () => {
        doc.body.innerHTML =
            '<table><tr><td>See mockup <a href="https://example.com"></a>.</td></tr></table>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed inside the section", () => {
        doc.body.innerHTML = '<section>See mockup <a href="https://example.com"></a>.</section>';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });

    it("Includes embed inside the body", () => {
        doc.body.innerHTML = 'See mockup <a href="https://example.com"></a>.';

        const embeddable_link = getEmbeddableLink(doc.body);
        insertWidget(embeddable_link, widget);

        expect(doc.body).toMatchSnapshot();
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function getEmbeddableLink(element: HTMLElement, selector = "a"): Element {
    const embeddable_link = element.querySelector(selector);
    if (!embeddable_link) {
        throw Error("Unable to find embeddable link");
    }

    return embeddable_link;
}
