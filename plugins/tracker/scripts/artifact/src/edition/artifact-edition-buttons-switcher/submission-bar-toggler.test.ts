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

import {
    toggleSubmissionBarForCommentInCkeditor,
    toggleSubmitArtifactBar,
} from "./submission-bar-toggler";
import { submissionBarIsAlreadyActive } from "./submission-bar-status-checker";
import { somethingIsEdited } from "./is-edited-checker";

jest.mock("./submission-bar-status-checker", () => ({
    submissionBarIsAlreadyActive: jest.fn(),
}));

jest.mock("./is-edited-checker", () => ({
    somethingIsEdited: jest.fn(),
}));

describe("submission bar tooggler", () => {
    let doc: Document;
    let mock_editor_instance = {} as CKEDITOR.editor;
    let select_ckeditor_format: HTMLSelectElement;
    let textarea: HTMLTextAreaElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        textarea = doc.createElement("textarea");

        select_ckeditor_format = doc.createElement("select");
        select_ckeditor_format.id = "rte_format_selectboxnew";
    });

    describe("toggleSubmitArtifactBar", () => {
        it("should do nothing if the submission bar is already active and something is edited", () => {
            (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(true);
            (somethingIsEdited as jest.Mock).mockReturnValue(true);

            const button = doc.createElement("button");
            button.className = "tracker-artifact-submit-buttons-bar-container";
            doc.body.appendChild(button);

            toggleSubmitArtifactBar(mock_editor_instance, select_ckeditor_format, textarea, doc);

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

            toggleSubmitArtifactBar(mock_editor_instance, select_ckeditor_format, textarea, doc);

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

            toggleSubmitArtifactBar(mock_editor_instance, select_ckeditor_format, textarea, doc);

            // wait for the transaction css before performing assertions
            setTimeout(() => {
                expect(
                    button.classList.contains("tracker-artifact-submit-buttons-bar-display"),
                ).toBe(true);
            }, 250);
        });

        it("should do nothing if the submission bar is not active and nothing is edited", () => {
            (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(false);
            (somethingIsEdited as jest.Mock).mockReturnValue(false);

            const button = doc.createElement("button");
            button.className = "tracker-artifact-submit-buttons-bar-container";
            doc.body.appendChild(button);

            toggleSubmitArtifactBar(mock_editor_instance, select_ckeditor_format, textarea, doc);

            expect(button.classList.contains("tracker-artifact-submit-buttons-bar-display")).toBe(
                false,
            );
        });
    });

    describe("toggleSubmissionBarForCommentInCkeditor", () => {
        let mock_on_change: jest.Mock;

        beforeEach(() => {
            mock_on_change = jest.fn();
            mock_editor_instance = { on: mock_on_change } as unknown as CKEDITOR.editor;

            document.body.innerHTML = `
            <textarea id="tracker_followup_comment_new"></textarea>
            <button class="hidden-artifact-submit-button" style="display: none;"></button>
        `;
        });

        it("should do nothing if no editor instance exists", () => {
            toggleSubmissionBarForCommentInCkeditor(doc, null, select_ckeditor_format, textarea);

            expect(mock_on_change).not.toHaveBeenCalled();
        });

        it("should register a change event on the CKEDITOR instance", () => {
            toggleSubmissionBarForCommentInCkeditor(
                doc,
                mock_editor_instance,
                select_ckeditor_format,
                textarea,
            );

            expect(mock_on_change).toHaveBeenCalled();
            expect(mock_on_change).toHaveBeenCalledWith("change", expect.any(Function));
        });

        it("should do nothing if the comment field does not exist", () => {
            const button = doc.createElement("button");
            button.className = "hidden-artifact-submit-button";
            doc.body.appendChild(button);

            toggleSubmissionBarForCommentInCkeditor(
                doc,
                mock_editor_instance,
                select_ckeditor_format,
                textarea,
            );

            const change_callback = mock_on_change.mock.calls[0][1];
            change_callback();

            expect(button.style.transition).toBe("");
            expect(button.style.display).toBe("");
        });

        it("should display the button bar", () => {
            (submissionBarIsAlreadyActive as jest.Mock).mockReturnValue(false);
            (somethingIsEdited as jest.Mock).mockReturnValue(true);
            toggleSubmissionBarForCommentInCkeditor(
                doc,
                mock_editor_instance,
                select_ckeditor_format,
                textarea,
            );

            const button = doc.createElement("button");
            button.className = "hidden-artifact-submit-button";
            doc.body.appendChild(button);

            const editor = doc.createElement("textarea");
            editor.id = "tracker_followup_comment_new";
            doc.body.appendChild(editor);

            const change_callback = mock_on_change.mock.calls[0][1];
            change_callback();

            doc.getElementById("tracker_followup_comment_new");
            // wait for the transaction css before performing assertions
            setTimeout(() => {
                expect(
                    button.classList.contains("tracker-artifact-submit-buttons-bar-display"),
                ).toBe(true);
            }, 250);
        });
    });
});
