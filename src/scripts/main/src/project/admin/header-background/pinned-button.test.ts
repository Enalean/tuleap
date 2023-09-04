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
                setupPinnedButton(document.implementation.createHTMLDocument()),
            ).toThrowError();
        });

        it("throws an error if submit button is not in the dom", () => {
            const doc = document.implementation.createHTMLDocument();
            const section = doc.createElement("section");
            section.id = "project-admin-background-submit-section";
            doc.body.appendChild(section);

            expect(() => setupPinnedButton(doc)).toThrowError();
        });

        it("Observes the position of the submit button and does not mark the section as pinned if no background is selected", () => {
            const { doc, section, button } = createDocumentExpectedFormStructure();

            const observe = jest.fn();
            const unobserve = jest.fn();
            const mockIntersectionObserver = jest.fn();
            mockIntersectionObserver.mockReturnValue({
                observe,
                unobserve,
            });
            window.IntersectionObserver = mockIntersectionObserver;

            setupPinnedButton(doc);

            expect(observe).toHaveBeenCalledWith(button);

            expect(section.classList.contains("pinned")).toBe(false);
            expect(unobserve).not.toHaveBeenCalled();
        });

        it("Marks the section as pinned if button is not in the viewport, and stop observing the button as soon as it is pinned", () => {
            const { doc, section, button, radio } = createDocumentExpectedFormStructure();

            const observe = jest.fn();
            const unobserve = jest.fn();
            const mockIntersectionObserver = jest.fn();
            mockIntersectionObserver.mockReturnValue({
                observe,
                unobserve,
            });
            window.IntersectionObserver = mockIntersectionObserver;

            setupPinnedButton(doc);

            expect(observe).toHaveBeenCalledWith(button);

            simulateBackgroundSelection(doc, radio);

            expect(section.classList.contains("pinned")).toBe(true);
            expect(unobserve).toHaveBeenCalledWith(button);
        });

        function createDocumentExpectedFormStructure(): {
            doc: Document;
            section: HTMLElement;
            button: HTMLButtonElement;
            radio: HTMLInputElement;
        } {
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
            return { doc, section, button, radio };
        }

        function simulateBackgroundSelection(doc: Document, radio: HTMLInputElement): void {
            const evt = doc.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            radio.dispatchEvent(evt);
        }
    });
});
