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

import { toggleSubmitArtifactBar } from "./submission-bar-toggler";
import { submissionBarIsAlreadyActive } from "./submission-bar-status-checker";
import { somethingIsEdited } from "./is-edited-checker";

jest.mock("./submission-bar-status-checker", () => ({
    submissionBarIsAlreadyActive: jest.fn(),
}));

jest.mock("./is-edited-checker", () => ({
    somethingIsEdited: jest.fn(),
}));

describe("toggleSubmitArtifactBar", () => {
    let doc: Document;
    const mockEditorInstance = {} as CKEDITOR.editor;
    const mockCommentElement = {} as HTMLElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("should do nothing if the submission bar is already active and something is edited", () => {
        (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(true);
        (somethingIsEdited as jest.Mock).mockReturnValue(true);

        const button = doc.createElement("button");
        button.className = "tracker-artifact-submit-buttons-bar-container";
        doc.body.appendChild(button);

        toggleSubmitArtifactBar(mockEditorInstance, mockCommentElement, doc);

        expect(button.classList.contains("tracker-artifact-submit-buttons-bar-display")).toBe(
            false,
        );
    });

    it("should hide button if the submission bar is already active and nothing is edited", () => {
        (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(true);
        (somethingIsEdited as jest.Mock).mockReturnValue(false);

        const button = doc.createElement("button");
        button.className = "tracker-artifact-submit-buttons-bar-container";
        doc.body.appendChild(button);

        toggleSubmitArtifactBar(mockEditorInstance, mockCommentElement, doc);

        expect(button.classList.contains("tracker-artifact-submit-buttons-bar-display")).toBe(
            false,
        );
    });

    it("should call display button if the submission bar is not active and something is edited", () => {
        (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(false);
        (somethingIsEdited as jest.Mock).mockReturnValue(true);

        const button = doc.createElement("button");
        button.className = "tracker-artifact-submit-buttons-bar-container";
        doc.body.appendChild(button);

        toggleSubmitArtifactBar(mockEditorInstance, mockCommentElement, doc);

        // wait for the transaction css before performing assertions
        setTimeout(() => {
            expect(button.classList.contains("tracker-artifact-submit-buttons-bar-display")).toBe(
                true,
            );
        }, 250);
    });

    it("should do nothing if the submission bar is not active and nothing is edited", () => {
        (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(false);
        (somethingIsEdited as jest.Mock).mockReturnValue(false);

        const button = doc.createElement("button");
        button.className = "tracker-artifact-submit-buttons-bar-container";
        doc.body.appendChild(button);

        toggleSubmitArtifactBar(mockEditorInstance, mockCommentElement, doc);

        expect(button.classList.contains("tracker-artifact-submit-buttons-bar-display")).toBe(
            false,
        );
    });
});
