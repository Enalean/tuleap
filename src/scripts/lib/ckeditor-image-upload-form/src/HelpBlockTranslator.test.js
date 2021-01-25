/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { HelpBlockTranslator } from "./HelpBlockTranslator";

function createDocument() {
    return document.implementation.createHTMLDocument();
}

describe(`HelpBlockTranslator`, () => {
    let doc, translator, element, gettext_provider;

    beforeEach(() => {
        doc = createDocument();
        element = doc.createElement("textarea");
        doc.body.append(element);
        gettext_provider = {
            gettext: (english) => english,
        };
        translator = new HelpBlockTranslator(doc, element, gettext_provider);
    });

    describe(`informUsersThatTheyCanPasteImagesInEditor`, () => {
        it(`creates a paragraph with some translated text and adds it to the help block`, () => {
            const help_block = doc.createElement("div");
            help_block.id = "help-block-id";
            element.dataset.helpId = "help-block-id";
            doc.body.append(help_block);

            translator.informUsersThatTheyCanPasteImagesInEditor();

            expect(help_block.innerHTML).toEqual(
                `<p>You can drag 'n drop or paste image directly in the editor.</p>`
            );
        });

        it(`does nothing when there is no "data-help-id" on the given element`, () => {
            const help_block = doc.createElement("div");
            help_block.id = "help-block-id";
            doc.body.append(help_block);

            translator.informUsersThatTheyCanPasteImagesInEditor();

            expect(help_block.innerHTML).toEqual("");
        });

        it(`does nothing when the referenced help block can't be found`, () => {
            const help_block = doc.createElement("div");
            help_block.id = "help-block-id";
            element.dataset.helpId = "NOT-help-block-id";
            doc.body.append(help_block);

            translator.informUsersThatTheyCanPasteImagesInEditor();

            expect(help_block.innerHTML).toEqual("");
        });

        it(`does nothing when the help block already has text content`, () => {
            const help_block = doc.createElement("div");
            help_block.id = "help-block-id";
            help_block.textContent = "Some other text";
            element.dataset.helpId = "help-block-id";
            doc.body.append(help_block);

            translator.informUsersThatTheyCanPasteImagesInEditor();

            expect(help_block.innerHTML).toEqual("Some other text");
        });
    });
});
