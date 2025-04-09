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

import { isFollowUpEmpty, noFieldIsSwitchedToEdit } from "./artifact-edition-switcher";

describe("artifact edition switcher", () => {
    describe("noFieldIsSwitchedToEdit", () => {
        let doc: Document;

        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();
        });

        it("should return true when no fields are in edition", () => {
            expect(noFieldIsSwitchedToEdit(doc)).toBe(true);
        });

        it("should return false when there are fields in edition", () => {
            const field = doc.createElement("div");
            field.className = "tracker_artifact_field in-edition";
            doc.body.appendChild(field);

            expect(noFieldIsSwitchedToEdit(doc)).toBe(false);
        });
    });

    describe("isFollowUpEmpty", () => {
        it("should return true if the CKEDITOR instance has empty content", () => {
            const mock_editor_instance = {
                getData: jest.fn().mockReturnValue("   "),
            } as unknown as CKEDITOR.editor;

            expect(isFollowUpEmpty(mock_editor_instance, null)).toBe(true);
        });

        it("should return false if the CKEDITOR instance has content", () => {
            const mock_editor_instance = {
                getData: jest.fn().mockReturnValue("Some content"),
            } as unknown as CKEDITOR.editor;

            expect(isFollowUpEmpty(mock_editor_instance, null)).toBe(false);
        });

        it("should return true if follow_up_new_comment is a textarea but empty", () => {
            const mock_comment = document.createElement("textarea");
            mock_comment.value = "   ";

            expect(isFollowUpEmpty(null, mock_comment)).toBe(true);
        });

        it("should return false if follow_up_new_comment is a textarea and has content", () => {
            const mock_comment = document.createElement("textarea");
            mock_comment.value = "Some content";

            expect(isFollowUpEmpty(null, mock_comment)).toBe(false);
        });

        it("should return true if both CKEDITOR instance and follow_up_new_comment are null", () => {
            expect(isFollowUpEmpty(null, null)).toBe(true);
        });
    });
});
