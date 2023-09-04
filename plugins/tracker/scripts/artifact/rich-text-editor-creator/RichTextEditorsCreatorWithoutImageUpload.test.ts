/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreatorWithoutImageUpload } from "./RichTextEditorsCreatorWithoutImageUpload";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`RichTextEditorsCreatorWithoutImageUpload`, () => {
    describe(`RichTextEditorsCreator`, () => {
        let doc: Document,
            editor_factory: RichTextEditorFactory,
            creator: RichTextEditorsCreatorWithoutImageUpload;
        beforeEach(() => {
            doc = createDocument();
            editor_factory = {
                createRichTextEditor: jest.fn(),
            } as unknown as RichTextEditorFactory;
            creator = new RichTextEditorsCreatorWithoutImageUpload(doc, editor_factory);
        });

        it(`throws an error if the follow up textarea is not found`, () => {
            doc.body.insertAdjacentHTML("beforeend", `<textarea id="kids_aint_alright"/>`);
            expect(() => creator.createTextFieldEditorForMassChange()).toThrow();
            expect(editor_factory.createRichTextEditor).not.toHaveBeenCalled();
        });

        it(`creates the text editor`, () => {
            doc.body.insertAdjacentHTML(
                "beforeend",
                `<textarea id="artifact_masschange_followup_comment"/>`,
            );
            creator.createTextFieldEditorForMassChange();
            expect(editor_factory.createRichTextEditor).toHaveBeenCalled();
        });
    });
});
