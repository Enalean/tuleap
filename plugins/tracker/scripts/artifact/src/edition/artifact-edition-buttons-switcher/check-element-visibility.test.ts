/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { checkElementVisibility } from "./check-element-visibility";

describe("checkElementVisibility", () => {
    it("should return true for visible elements", () => {
        const element = document.createElement("div");
        document.body.appendChild(element);
        element.style.visibility = "visible";
        element.style.display = "block";
        Object.defineProperty(element, "offsetWidth", { value: 100 });
        Object.defineProperty(element, "offsetHeight", { value: 100 });

        expect(checkElementVisibility(element)).toBe(true);
    });

    it("should return false for elements with visibility hidden", () => {
        const element = document.createElement("div");
        document.body.appendChild(element);
        element.style.visibility = "hidden";
        element.style.display = "block";
        Object.defineProperty(element, "offsetWidth", { value: 100 });
        Object.defineProperty(element, "offsetHeight", { value: 100 });

        expect(checkElementVisibility(element)).toBe(false);
    });

    it("should return false for elements with display none", () => {
        const element = document.createElement("div");
        document.body.appendChild(element);
        element.style.visibility = "visible";
        element.style.display = "none";
        Object.defineProperty(element, "offsetWidth", { value: 100 });
        Object.defineProperty(element, "offsetHeight", { value: 100 });

        expect(checkElementVisibility(element)).toBe(false);

        document.body.removeChild(element);
    });

    it("should return false for elements with zero width and height", () => {
        const element = document.createElement("div");
        document.body.appendChild(element);
        element.style.visibility = "visible";
        element.style.display = "block";
        Object.defineProperty(element, "offsetWidth", { value: 0 });
        Object.defineProperty(element, "offsetHeight", { value: 0 });

        expect(checkElementVisibility(element)).toBe(false);

        document.body.removeChild(element);
    });
});
