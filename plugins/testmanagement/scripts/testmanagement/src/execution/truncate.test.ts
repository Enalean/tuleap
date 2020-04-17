/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { truncateHTML } from "./truncate";

const placeholder_image_text = "A screenshot has been attached";

describe("truncateHTML", () => {
    it(`Given the content is smaller than expected length,
        Then it does not add an ellipsis`, () => {
        expect(truncateHTML("<p>Hello World</p>", 888, placeholder_image_text)).toBe(
            "<p>Hello World</p>"
        );
    });

    it(`Given the content is bigger than expected length,
        Then it adds an ellipsis`, () => {
        expect(truncateHTML("<p>Hello World</p>", 8, placeholder_image_text)).toBe(
            "<p>Hello Wo…</p>"
        );
    });

    it(`Given the content is bigger than expected length,
        And it contains additional content,
        Then it adds an ellipsis and remove the remaining content`, () => {
        expect(
            truncateHTML(
                "<p>Hello World <strong>!</strong></p><p>to be also removed</p>",
                8,
                placeholder_image_text
            )
        ).toBe("<p>Hello Wo…</p>");
    });

    it(`Given the text to cut is nested in sub tags
        Then it adds an ellipsis inside those tags
        So that hyperlinks are still accessible`, () => {
        expect(truncateHTML('<p>Hello <a href="/">World</a></p>', 8, placeholder_image_text)).toBe(
            '<p>Hello <a href="/">Wo…</a></p>'
        );
    });

    it(`Given the text is plain text
        Then it adds an ellipsis without adding tags`, () => {
        expect(truncateHTML("Hello World", 8, placeholder_image_text)).toBe("Hello Wo…");
    });

    it(`Given the text contains images
        Then it removes them`, () => {
        expect(truncateHTML("Hello <img> World", 100, placeholder_image_text)).toBe("Hello  World");
    });

    it(`Given the text contains only images
        Then it replaces it with the placeholder image text: **`, () => {
        expect(truncateHTML("<img>", 100, placeholder_image_text)).toBe(
            "<p><i>A screenshot has been attached</i></p>"
        );
        expect(truncateHTML("<p><img></p>", 100, placeholder_image_text)).toBe(
            "<p><i>A screenshot has been attached</i></p>"
        );
        expect(truncateHTML("<p><img><img></p>", 100, placeholder_image_text)).toBe(
            "<p><i>A screenshot has been attached</i></p>"
        );
    });

    it(`Given there are line breaks
        Then it removes them`, () => {
        expect(truncateHTML("Hello <br><br><br> World", 100, placeholder_image_text)).toBe(
            "Hello  World"
        );
    });

    it(`Given there are empty blocks
        Then they are removed`, () => {
        expect(truncateHTML("Hello <p></p> World", 100, placeholder_image_text)).toBe(
            "Hello  World"
        );
    });

    it(`Given there are more than one p tag
        Then it takes only the first one`, () => {
        expect(truncateHTML("<p>Hello</p><p>World</p>", 100, placeholder_image_text)).toBe(
            "<p>Hello</p>"
        );
    });
});
