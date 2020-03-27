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

import { isElementInViewport } from "./is-element-in-viewport";

describe("isElementInViewport", () => {
    it("Returns false if element is not in viewport", () => {
        Object.defineProperty(window, "innerHeight", { get: () => 100 });
        jest.spyOn(Element.prototype, "getBoundingClientRect").mockReturnValue({
            top: 0,
            right: 0,
            bottom: 150,
            left: 0,
            height: 0,
            width: 0,
            x: 0,
            y: 0,
            toJSON: () => ({}),
        });

        expect(isElementInViewport(document.createElement("div"))).toBe(false);
    });

    it("Returns true if element is in viewport", () => {
        Object.defineProperty(window, "innerHeight", { get: () => 100 });
        jest.spyOn(Element.prototype, "getBoundingClientRect").mockReturnValue({
            top: 0,
            right: 0,
            bottom: 0,
            left: 0,
            height: 0,
            width: 0,
            x: 0,
            y: 0,
            toJSON: () => ({}),
        });

        expect(isElementInViewport(document.createElement("div"))).toBe(true);
    });
});
