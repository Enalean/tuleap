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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { submissionBarIsAlreadyActive } from "./submission-bar-status-checker";

describe("submissionBarIsAlreadyActive", () => {
    let mockDocument: Document;

    beforeEach(() => {
        mockDocument = document.implementation.createHTMLDocument();
    });

    it("returns true if at least one hidden-artifact-submit-button is visible", () => {
        const button = mockDocument.createElement("button");
        button.className = "hidden-artifact-submit-button";
        Object.defineProperty(button, "offsetWidth", { value: 100 });
        Object.defineProperty(button, "offsetHeight", { value: 100 });
        mockDocument.body.appendChild(button);

        expect(submissionBarIsAlreadyActive(mockDocument)).toBe(true);
    });

    it("returns false if no hidden-artifact-submit-button elements are present", () => {
        expect(submissionBarIsAlreadyActive(mockDocument)).toBe(false);
    });

    it("returns false if hidden-artifact-submit-button elements are not visible", () => {
        const button = mockDocument.createElement("button");
        button.className = "hidden-artifact-submit-button";
        Object.defineProperty(button, "offsetWidth", { value: 0 });
        Object.defineProperty(button, "offsetHeight", { value: 0 });
        mockDocument.body.appendChild(button);

        expect(submissionBarIsAlreadyActive(mockDocument)).toBe(false);
    });
});
