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

import { isFollowUpEmpty } from "./follow-up-checker";

describe("isFollowUpEmpty", () => {
    let doc: Document;
    let select_ckeditor_format: HTMLSelectElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();

        select_ckeditor_format = doc.createElement("select");
        select_ckeditor_format.id = "rte_format_selectboxnew";

        const html_option = document.createElement("option");
        html_option.value = "html";

        const markdown_option = document.createElement("option");
        markdown_option.value = "markdown";

        select_ckeditor_format.appendChild(html_option);
        select_ckeditor_format.appendChild(markdown_option);
    });

    it("should return true if the CKEDITOR instance has empty content", () => {
        const mock_editor_instance = {
            getData: jest.fn().mockReturnValue("   "),
        } as unknown as CKEDITOR.editor;

        expect(isFollowUpEmpty(mock_editor_instance, select_ckeditor_format, null)).toBe(true);
    });

    it("should return false if the CKEDITOR instance has content", () => {
        const mock_editor_instance = {
            getData: jest.fn().mockReturnValue("Some content"),
        } as unknown as CKEDITOR.editor;

        select_ckeditor_format.value = "html";
        expect(isFollowUpEmpty(mock_editor_instance, select_ckeditor_format, null)).toBe(false);
    });

    it("should return true if follow_up_new_comment is a textarea but empty", () => {
        const mock_comment = document.createElement("textarea");
        mock_comment.value = "   ";

        expect(isFollowUpEmpty(null, select_ckeditor_format, mock_comment)).toBe(true);
    });

    it("should return false if follow_up_new_comment is a textarea and has content", () => {
        const mock_comment = document.createElement("textarea");
        mock_comment.value = "Some content";

        expect(isFollowUpEmpty(null, select_ckeditor_format, mock_comment)).toBe(false);
    });

    it("should return true if both CKEDITOR instance and follow_up_new_comment are null", () => {
        expect(isFollowUpEmpty(null, select_ckeditor_format, null)).toBe(true);
    });
});
