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

import { setupPinnedButton } from "./pinned-button";

describe("pinned-button", () => {
    describe("setupPinnedButton", () => {
        it("throws an error if submit section is not in the dom", () => {
            expect(() =>
                setupPinnedButton(document.implementation.createHTMLDocument(), window)
            ).toThrowError();
        });

        it("throws an error if submit button is not in the dom", () => {
            const doc = document.implementation.createHTMLDocument();
            const section = doc.createElement("section");
            section.id = "project-admin-background-submit-section";
            doc.body.appendChild(section);

            expect(() => setupPinnedButton(doc, window)).toThrowError();
        });

        it("Marks the section as pinned if button is not in the viewport", () => {
            const doc = document.implementation.createHTMLDocument();
            const section = doc.createElement("section");
            section.id = "project-admin-background-submit-section";
            doc.body.appendChild(section);
            const button = doc.createElement("button");
            button.id = "project-admin-background-submit-button";
            doc.body.appendChild(button);
            const radio = doc.createElement("input");
            radio.type = "radio";
            radio.classList.add("project-admin-background-radio");
            doc.body.appendChild(radio);

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

            setupPinnedButton(doc, window);

            const evt = doc.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            radio.dispatchEvent(evt);

            expect(section.classList.contains("pinned")).toBe(true);
        });

        it("Does not mark the section as pinned if button is in the viewport", () => {
            const doc = document.implementation.createHTMLDocument();
            const section = doc.createElement("section");
            section.id = "project-admin-background-submit-section";
            doc.body.appendChild(section);
            const button = doc.createElement("button");
            button.id = "project-admin-background-submit-button";
            doc.body.appendChild(button);
            const radio = doc.createElement("input");
            radio.type = "radio";
            radio.classList.add("project-admin-background-radio");
            doc.body.appendChild(radio);

            Object.defineProperty(window, "innerHeight", { get: () => 100 });
            jest.spyOn(Element.prototype, "getBoundingClientRect").mockReturnValue({
                top: 0,
                right: 0,
                bottom: 50,
                left: 0,
                height: 0,
                width: 0,
                x: 0,
                y: 0,
                toJSON: () => ({}),
            });

            setupPinnedButton(doc, window);

            const evt = doc.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            radio.dispatchEvent(evt);

            expect(section.classList.contains("pinned")).toBe(false);
        });
    });
});
