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

import { nothingIsEdited, somethingIsEdited } from "./is-edited-checker";
import { isFollowUpEmpty } from "./follow-up-checker";
import { noFieldIsSwitchedToEdit } from "./fields-checker";

jest.mock("./follow-up-checker");
jest.mock("./fields-checker");

describe("Is edited", () => {
    const mock_editor_instance = {
        getData: jest.fn().mockReturnValue("   "),
    } as unknown as CKEDITOR.editor;
    let doc: Document;
    let textarea: HTMLTextAreaElement;
    let select_ckeditor_format: HTMLSelectElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        textarea = doc.createElement("textarea");
        select_ckeditor_format = doc.createElement("select");
        select_ckeditor_format.id = "rte_format_selectboxnew";
        select_ckeditor_format.value = "HTML";
    });

    describe("nothingIsEdited", () => {
        it("should return true when follow-up is empty and no fields are in edition", () => {
            (isFollowUpEmpty as jest.Mock).mockReturnValue(true);
            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(true);

            expect(
                nothingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(true);
        });

        it("should return false when follow-up is not empty, regardless of fields status", () => {
            (isFollowUpEmpty as jest.Mock).mockReturnValue(false);
            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(true);

            expect(
                nothingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(false);

            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(false);
            expect(
                nothingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(false);
        });

        it("should return false when fields are in edition, regardless of follow-up status", () => {
            (isFollowUpEmpty as jest.Mock).mockReturnValue(true);
            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(false);

            expect(
                nothingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(false);

            (isFollowUpEmpty as jest.Mock).mockReturnValue(false);
            expect(
                nothingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(false);
        });
    });

    describe("somethingIsEdited", () => {
        it("should return true when nothing is edited", () => {
            (isFollowUpEmpty as jest.Mock).mockReturnValue(true);
            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(false);

            expect(
                somethingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(true);
        });

        it("should return false when a field is switch to edited", () => {
            (isFollowUpEmpty as jest.Mock).mockReturnValue(true);
            (noFieldIsSwitchedToEdit as jest.Mock).mockReturnValue(true);

            expect(
                somethingIsEdited(mock_editor_instance, select_ckeditor_format, textarea, doc),
            ).toBe(false);
        });
    });
});
