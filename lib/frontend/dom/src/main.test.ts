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

import { beforeEach, describe, it, expect } from "vitest";
import { getAttributeOrThrow, selectOrThrow } from "./main";

describe(`DOM`, () => {
    describe(`getAttributeOrThrow`, () => {
        const DATA_ATTRIBUTE_VALUE = "lingberry";

        function createElement(key: string): HTMLDivElement {
            const doc = document.implementation.createHTMLDocument();
            const element = doc.createElement("div");

            element.setAttribute(key, DATA_ATTRIBUTE_VALUE);

            return element;
        }

        function* generateKeys(): Generator<[string]> {
            yield ["data-name"];
            yield ["my-attribute"];
        }

        it.each([...generateKeys()])(
            `returns the string value of the item's attribute for the given key`,
            (key) => {
                const element = createElement(key);
                expect(getAttributeOrThrow(element, key)).toBe(DATA_ATTRIBUTE_VALUE);
            },
        );

        it.each([...generateKeys()])(
            `throws when the item has no matching attribute for the given key`,
            (key) => {
                const element = createElement("unknown-key");
                const error = `Missing attribute ${key}`;

                expect(() => getAttributeOrThrow(element, key)).toThrowError(error);
            },
        );
    });

    describe(`selectOrThrow()`, () => {
        const HTML_ID = "unusualness";
        const DATA_ATTRIBUTE_NAME = "frush";

        let base: HTMLDivElement, element_to_select: HTMLDivElement;
        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();
            base = doc.createElement("div");
            element_to_select = doc.createElement("div");
            element_to_select.id = HTML_ID;
            element_to_select.dataset[DATA_ATTRIBUTE_NAME] = "";
            base.append(element_to_select);
        });

        it(`finds the element in base by its HTML ID`, () => {
            expect(selectOrThrow(base, "#" + HTML_ID)).toBe(element_to_select);
        });

        it(`asserts the found element is an instance of given element constructor`, () => {
            const selected_element = selectOrThrow(base, "#" + HTML_ID, HTMLDivElement);
            expect(selected_element).toBe(element_to_select);
            expect(selected_element instanceof HTMLDivElement).toBe(true);
        });

        it(`finds the element in base by a data attribute selector`, () => {
            expect(selectOrThrow(base, `[data-${DATA_ATTRIBUTE_NAME}]`)).toBe(element_to_select);
        });

        it(`throws when the selectors don't match anything`, () => {
            const unknown_selector = "#doesNotMatch";
            expect(() => selectOrThrow(base, unknown_selector)).toThrowError(
                `Could not find element with selector '${unknown_selector}'`,
            );
        });

        it(`throws when the element does not match the given element constructor`, () => {
            expect(() => selectOrThrow(base, "#" + HTML_ID, HTMLInputElement)).toThrow();
        });
    });
});
