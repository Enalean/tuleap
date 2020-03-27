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

import { scrollToItemIfNeeded } from "./scroll-to-item";

describe("scroll to item helper", () => {
    let card: HTMLElement, fullscreen_element: HTMLElement;

    beforeEach(() => {
        const local_document = document.implementation.createHTMLDocument();
        card = local_document.createElement("div");
        fullscreen_element = local_document.createElement("div");

        jest.spyOn(window, "scrollTo").mockImplementation();

        fullscreen_element.scrollTo = (): void => {
            // else fullscreen_element.scrollTo is not found
        };

        jest.spyOn(fullscreen_element, "scrollTo").mockImplementation();
    });

    it("scrolls to the item if it is outside of the viewport", () => {
        setCardTopPosition(card, 50);
        scrollToItemIfNeeded(card, null);

        expect(window.scrollTo).toHaveBeenCalled();
    });

    it("does nothing if the item is visible", () => {
        setCardTopPosition(card, 200);
        scrollToItemIfNeeded(card, null);

        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    describe("In fullscreen", () => {
        it("scrolls to the item inside the element in fullscreen if it is outside of the viewport", () => {
            setCardTopPosition(card, 20);
            scrollToItemIfNeeded(card, fullscreen_element);

            expect(fullscreen_element.scrollTo).toHaveBeenCalled();
        });

        it("does nothing if the item is visible", () => {
            setCardTopPosition(card, 200);
            scrollToItemIfNeeded(card, fullscreen_element);

            expect(fullscreen_element.scrollTo).not.toHaveBeenCalled();
        });
    });
});

function setCardTopPosition(card: HTMLElement, top: number): void {
    jest.spyOn(card, "getBoundingClientRect").mockImplementation(() => {
        return {
            top,
        } as DOMRect;
    });
}
