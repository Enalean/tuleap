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
import { bindSubmissionBarToFollowups } from "./bind-submission-bar-to-followups";

jest.mock("./submission-bar-toggler", () => ({
    toggleSubmissionBarForCommentInCkeditor: jest.fn(),
    toggleSubmitArtifactBar: jest.fn(),
}));

describe("bindSubmissionBarToFollowups", () => {
    let doc: Document;
    let mock_editor_instance = {} as CKEDITOR.editor;
    let select_ckeditor_format: HTMLSelectElement;
    let textarea: HTMLTextAreaElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        mock_editor_instance = {} as unknown as CKEDITOR.editor;

        select_ckeditor_format = doc.createElement("select");
        select_ckeditor_format.id = "rte_format_selectboxnew";

        textarea = doc.createElement("textarea");
        doc.body.appendChild(textarea);
    });

    it("should bind submission bar to ckeditor format selector (HTML/Markdown...)", () => {
        bindSubmissionBarToFollowups(doc, mock_editor_instance, select_ckeditor_format, textarea);

        const input_event = new Event("change");
        select_ckeditor_format.dispatchEvent(input_event);

        expect(toggleSubmissionBarForCommentInCkeditor).toHaveBeenCalledTimes(2);
    });

    it("should bind submission bar to followup comment", () => {
        const editor = doc.createElement("textarea");
        editor.id = "tracker_followup_comment_new";
        doc.body.appendChild(editor);

        bindSubmissionBarToFollowups(doc, mock_editor_instance, select_ckeditor_format, textarea);

        const input_event = new Event("input");
        editor.dispatchEvent(input_event);

        expect(toggleSubmissionBarForCommentInCkeditor).toHaveBeenCalled();
        expect(toggleSubmitArtifactBar).not.toHaveBeenCalled();
    });
});
